<?php

namespace App\Domains\Document\Services;

use App\Core\AI\Services\AIServiceInterface;
use App\Core\Category\Models\Category;
use App\Core\Category\Models\CategoryGroup;
use App\Core\Document\Models\Document;
use App\Core\Document\Models\DocumentCategoryAssignment;
use App\Core\Tenant\Models\Tenant;
use App\Domains\Category\Services\CategoryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentAIService
{
    public function __construct(
        private AIServiceInterface $aiService,
        private CategoryService $categoryService
    ) {}

    /**
     * Kategorisiere ein Dokument mit AI basierend auf allen aktiven AI-fähigen Kategoriegruppen
     */
    public function categorizeDocument(Document $document): array
    {
        $tenant = Tenant::find($document->tenant_id);
        $results = [];

        // Hole alle AI-fähigen Kategoriegruppen für den Tenant
        $aiEnabledGroups = CategoryGroup::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where('ai_enabled', true)
            ->orderBy('display_order')
            ->get();

        foreach ($aiEnabledGroups as $group) {
            try {
                $result = $this->categorizeForGroup($document, $group);
                if ($result) {
                    $results[] = $result;
                }
            } catch (\Exception $e) {
                Log::error("AI Kategorisierung für Gruppe {$group->name} fehlgeschlagen", [
                    'document_id' => $document->id,
                    'group_id' => $group->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Kategorisiere ein Dokument für eine spezifische Kategoriegruppe
     */
    protected function categorizeForGroup(Document $document, CategoryGroup $group): ?array
    {
        // Hole alle aktiven Kategorien der Gruppe
        $categories = Category::where('category_group_id', $group->id)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        if ($categories->isEmpty()) {
            return null;
        }

        // Baue den Prompt mit den Kategoriedefinitionen
        $prompt = $this->buildCategorizationPrompt($document, $group, $categories);

        // Führe AI-Analyse aus
        $aiResponse = $this->aiService->analyze($prompt);

        // Parse die AI-Antwort und finde die beste(n) Kategorie(n)
        $selectedCategories = $this->parseAIResponse($aiResponse, $categories, $group);

        if (empty($selectedCategories)) {
            return null;
        }

        // Speichere die Zuordnungen
        $assignments = [];
        foreach ($selectedCategories as $categoryData) {
            $assignment = $this->assignCategory(
                $document, 
                $categoryData['category'], 
                $categoryData['confidence'],
                $categoryData['reasoning']
            );
            $assignments[] = $assignment;
        }

        return [
            'group' => $group,
            'assignments' => $assignments,
            'ai_response' => $aiResponse
        ];
    }

    /**
     * Erstelle den Kategorisierungs-Prompt
     */
    protected function buildCategorizationPrompt(Document $document, CategoryGroup $group, Collection $categories): string
    {
        $prompt = "Du bist ein Dokument-Kategorisierungs-Assistent. Analysiere das folgende Dokument und ordne es der passendsten Kategorie aus der Gruppe '{$group->name}' zu.\n\n";

        // Füge Gruppen-spezifischen Kontext hinzu
        if ($group->ai_prompt_context) {
            $prompt .= "Kontext: {$group->ai_prompt_context}\n\n";
        }

        // Füge Dokumentinformationen hinzu
        $prompt .= "DOKUMENT INFORMATIONEN:\n";
        $prompt .= "Titel: {$document->title}\n";
        if ($document->description) {
            $prompt .= "Beschreibung: {$document->description}\n";
        }
        if ($document->extracted_text) {
            $prompt .= "Inhalt (Auszug): " . substr($document->extracted_text, 0, 1000) . "...\n";
        }
        $prompt .= "\n";

        // Füge Kategorie-Definitionen hinzu
        $prompt .= "VERFÜGBARE KATEGORIEN:\n";
        foreach ($categories as $category) {
            $prompt .= "\n{$category->name}:\n";
            if ($category->description) {
                $prompt .= "- Beschreibung: {$category->description}\n";
            }
            if ($category->ai_positive_description) {
                $prompt .= "- Gehört dazu: {$category->ai_positive_description}\n";
            }
            if ($category->ai_negative_description) {
                $prompt .= "- Gehört NICHT dazu: {$category->ai_negative_description}\n";
            }
            if ($category->ai_keywords) {
                $prompt .= "- Schlüsselwörter: " . implode(', ', $category->ai_keywords) . "\n";
            }
            if ($category->ai_examples) {
                $prompt .= "- Beispiele: " . implode(', ', $category->ai_examples) . "\n";
            }
        }

        // Anweisungen für die AI
        $prompt .= "\nANWEISUNGEN:\n";
        
        if ($group->selection_type === 'single') {
            $prompt .= "1. Wähle GENAU EINE Kategorie aus\n";
        } else {
            $prompt .= "1. Wähle ALLE zutreffenden Kategorien aus (mehrere möglich)\n";
        }
        
        $prompt .= "2. Bewerte deine Zuversicht von 0.0 bis 1.0\n";
        $prompt .= "3. Erkläre kurz deine Entscheidung\n";
        $prompt .= "4. Antworte im folgenden JSON-Format:\n";
        $prompt .= "{\n";
        $prompt .= '  "categories": [';
        $prompt .= "\n";
        $prompt .= '    {';
        $prompt .= "\n";
        $prompt .= '      "name": "Kategoriename",';
        $prompt .= "\n";
        $prompt .= '      "confidence": 0.95,';
        $prompt .= "\n";
        $prompt .= '      "reasoning": "Kurze Begründung"';
        $prompt .= "\n";
        $prompt .= '    }';
        $prompt .= "\n";
        $prompt .= '  ]';
        $prompt .= "\n";
        $prompt .= "}\n";

        return $prompt;
    }

    /**
     * Parse die AI-Antwort und extrahiere die Kategorien
     */
    protected function parseAIResponse(string $aiResponse, Collection $categories, CategoryGroup $group): array
    {
        try {
            // Versuche JSON aus der Antwort zu extrahieren
            $jsonMatch = [];
            preg_match('/\{.*\}/s', $aiResponse, $jsonMatch);
            
            if (empty($jsonMatch)) {
                throw new \Exception("Keine JSON-Antwort gefunden");
            }

            $data = json_decode($jsonMatch[0], true);
            
            if (!isset($data['categories']) || !is_array($data['categories'])) {
                throw new \Exception("Ungültiges Antwortformat");
            }

            $selectedCategories = [];
            
            foreach ($data['categories'] as $categoryData) {
                // Finde die Kategorie nach Namen
                $category = $categories->first(function ($cat) use ($categoryData) {
                    return strtolower($cat->name) === strtolower($categoryData['name']);
                });

                if (!$category) {
                    continue;
                }

                $confidence = floatval($categoryData['confidence'] ?? 0.5);
                
                // Prüfe ob Confidence-Schwellwert erreicht wird
                if ($confidence < $group->ai_confidence_threshold) {
                    continue;
                }

                $selectedCategories[] = [
                    'category' => $category,
                    'confidence' => $confidence,
                    'reasoning' => $categoryData['reasoning'] ?? ''
                ];

                // Bei Single-Selection nur die erste nehmen
                if ($group->selection_type === 'single') {
                    break;
                }
            }

            return $selectedCategories;

        } catch (\Exception $e) {
            Log::error("Fehler beim Parsen der AI-Antwort", [
                'response' => $aiResponse,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Weise eine Kategorie einem Dokument zu
     */
    protected function assignCategory(Document $document, Category $category, float $confidence, string $reasoning): DocumentCategoryAssignment
    {
        // Prüfe ob bereits eine Zuordnung existiert
        $existing = DocumentCategoryAssignment::where('document_id', $document->id)
            ->where('category_id', $category->id)
            ->first();

        if ($existing) {
            // Update bestehende Zuordnung
            $existing->update([
                'ai_confidence' => $confidence,
                'ai_reasoning' => $reasoning,
                'assigned_by' => 'ai',
                'assigned_at' => now()
            ]);
            return $existing;
        }

        // Erstelle neue Zuordnung
        return DocumentCategoryAssignment::create([
            'document_id' => $document->id,
            'category_id' => $category->id,
            'category_group_id' => $category->category_group_id,
            'ai_confidence' => $confidence,
            'ai_reasoning' => $reasoning,
            'assigned_by' => 'ai',
            'assigned_at' => now()
        ]);
    }

    /**
     * Verarbeite mehrere Dokumente
     */
    public function categorizeMultipleDocuments(array $documentIds): array
    {
        $results = [];
        
        foreach ($documentIds as $documentId) {
            try {
                $document = Document::findOrFail($documentId);
                $categorizations = $this->categorizeDocument($document);
                
                $results[] = [
                    'document_id' => $documentId,
                    'success' => true,
                    'categorizations' => $categorizations
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'document_id' => $documentId,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}