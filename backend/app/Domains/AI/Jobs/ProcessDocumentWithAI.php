<?php

namespace App\Domains\AI\Jobs;

use App\Core\Document\Models\Document;
use App\Core\Document\Models\DocumentActivity;
use App\Core\AI\Services\AIService;
use App\Core\Category\Models\CategoryGroup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessDocumentWithAI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    public function __construct(
        private string $documentId
    ) {}

    public function handle(AIService $aiService): void
    {
        $document = Document::find($this->documentId);
        
        if (!$document) {
            Log::warning("Document not found for AI processing", ['document_id' => $this->documentId]);
            return;
        }

        try {
            // Mark as processing
            $document->updateAIProcessingStatus('processing');

            // Step 1: Extract content from document
            $extractedContent = $this->extractContent($document);
            
            if (empty($extractedContent)) {
                $this->markAsCompleted($document, ['extracted_text' => '']);
                return;
            }

            // Step 2: Get available category groups for tenant
            $categoryGroups = CategoryGroup::where('tenant_id', $document->tenant_id)
                                         ->where('ai_enabled', true)
                                         ->where('is_active', true)
                                         ->with('categories')
                                         ->get();

            // Step 3: Process with AI
            $aiResults = [];

            // Language detection
            $language = $this->detectLanguage($aiService, $extractedContent);
            $aiResults['language'] = $language;

            // Content analysis
            $contentAnalysis = $this->analyzeContent($aiService, $extractedContent, $document);
            $aiResults = array_merge($aiResults, $contentAnalysis);

            // Category classification if groups available
            if ($categoryGroups->isNotEmpty()) {
                $classification = $this->classifyDocument($aiService, $extractedContent, $document, $categoryGroups);
                $aiResults['classification'] = $classification;
                
                // Auto-assign categories with high confidence
                $this->autoAssignCategories($document, $classification);
            }

            // Step 4: Update document with AI results
            $this->markAsCompleted($document, [
                'extracted_text' => $extractedContent,
                'ai_metadata' => $aiResults['metadata'] ?? [],
                'ai_tags' => $aiResults['tags'] ?? [],
                'ai_summary' => $aiResults['summary'] ?? null,
                'ai_classification' => $aiResults['classification'] ?? [],
                'language' => $language,
                'ai_confidence_score' => $this->calculateOverallConfidence($aiResults),
            ]);

            Log::info("Document AI processing completed", [
                'document_id' => $document->id,
                'confidence_score' => $this->calculateOverallConfidence($aiResults)
            ]);

        } catch (\Exception $e) {
            Log::error("Document AI processing failed", [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $document->updateAIProcessingStatus('failed');
            
            DocumentActivity::logError(
                $document,
                'ai_processing_failed',
                $e->getMessage(),
                ['exception' => get_class($e)]
            );

            throw $e;
        }
    }

    private function extractContent(Document $document): string
    {
        try {
            if (!Storage::exists($document->storage_path)) {
                throw new \Exception("Document file not found: {$document->storage_path}");
            }

            $content = Storage::get($document->storage_path);

            // Extract text based on MIME type
            return match (true) {
                $document->mime_type === 'application/pdf' => $this->extractFromPdf($content),
                str_starts_with($document->mime_type, 'image/') => $this->extractFromImage($content),
                str_starts_with($document->mime_type, 'text/') => $content,
                $document->mime_type === 'text/csv' => $content,
                $document->mime_type === 'application/msword' => $this->extractFromDoc($content),
                $document->mime_type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => $this->extractFromDocx($content),
                default => ''
            };
        } catch (\Exception $e) {
            Log::error("Content extraction failed", [
                'document_id' => $document->id,
                'mime_type' => $document->mime_type,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    private function extractFromPdf(string $content): string
    {
        // TODO: Implement PDF text extraction using a library like Smalot/PdfParser
        // For now, return mock content for AI testing
        return 'Rechnung Nr. 2024-001234 vom 15.12.2024. Betrag: CHF 150.00. Firma: Muster AG. MwSt.: 19%. Zahlungsziel: 30 Tage.';
    }

    private function extractFromImage(string $content): string
    {
        // TODO: Implement OCR using Tesseract or cloud OCR service
        // For now, return empty string
        return '';
    }

    private function extractFromDoc(string $content): string
    {
        // TODO: Implement .doc extraction using PhpOffice or similar
        return '';
    }

    private function extractFromDocx(string $content): string
    {
        // TODO: Implement .docx extraction using PhpOffice or similar
        return '';
    }

    private function detectLanguage(AIService $aiService, string $content): string
    {
        $prompt = "Detect the language of the following text and return only the ISO 639-1 language code (e.g., 'en', 'de', 'fr'). If unsure, return 'unknown'.\n\nText:\n" . substr($content, 0, 500);

        try {
            $response = $aiService->processWithTemplate('language_detection', [
                'content' => substr($content, 0, 500)
            ]);

            $language = trim(strtolower($response));
            return in_array($language, ['de', 'en', 'fr', 'it', 'es']) ? $language : 'unknown';
        } catch (\Exception $e) {
            Log::warning("Language detection failed", ['error' => $e->getMessage()]);
            return 'unknown';
        }
    }

    private function analyzeContent(AIService $aiService, string $content, Document $document): array
    {
        try {
            $response = $aiService->processWithTemplate('document_analysis', [
                'filename' => $document->original_filename,
                'mime_type' => $document->mime_type,
                'content' => $content
            ]);

            $analysis = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("Invalid JSON response from document analysis", [
                    'document_id' => $document->id,
                    'response' => $response
                ]);
                return [];
            }

            return $analysis;
        } catch (\Exception $e) {
            Log::error("Document content analysis failed", [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function classifyDocument(AIService $aiService, string $content, Document $document, $categoryGroups): array
    {
        try {
            // Build category context
            $categoryContext = $this->buildCategoryContext($categoryGroups);
            
            $response = $aiService->processWithTemplate('document_classification', [
                'filename' => $document->original_filename,
                'mime_type' => $document->mime_type,
                'content' => $content,
                'category_context' => $categoryContext,
                'tenant_name' => $document->tenant->name ?? 'Unknown'
            ]);

            $classification = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("Invalid JSON response from document classification", [
                    'document_id' => $document->id,
                    'response' => $response
                ]);
                return [];
            }

            return $classification['kategorisierungen'] ?? [];
        } catch (\Exception $e) {
            Log::error("Document classification failed", [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function buildCategoryContext($categoryGroups): string
    {
        $context = [];
        
        foreach ($categoryGroups as $group) {
            $groupContext = [
                "GRUPPE: {$group->name} ({$group->selection_type->value})",
                "Beschreibung: {$group->description}",
                ""
            ];
            
            foreach ($group->categories as $category) {
                $groupContext[] = "KATEGORIE: {$category->name}";
                $groupContext[] = "✅ GEHÖRT DAZU: {$category->ai_positive_description}";
                
                if ($category->ai_negative_description) {
                    $groupContext[] = "❌ GEHÖRT NICHT DAZU: {$category->ai_negative_description}";
                }
                
                if ($category->ai_keywords) {
                    $groupContext[] = "🔑 KEYWORDS: " . implode(', ', $category->ai_keywords);
                }
                
                $groupContext[] = "";
            }
            
            $context[] = implode("\n", $groupContext);
        }
        
        return implode("\n---\n", $context);
    }

    private function autoAssignCategories(Document $document, array $classifications): void
    {
        foreach ($classifications as $classification) {
            $categorySlug = $classification['kategorie'] ?? null;
            $confidence = $classification['konfidenz'] ?? 0;
            $reason = $classification['begründung'] ?? 'AI-Klassifikation';
            
            if (!$categorySlug || $confidence < 0.7) {
                continue; // Skip low confidence classifications
            }

            // Find category by slug within the tenant
            $category = \App\Core\Category\Models\Category::whereHas('categoryGroup', function ($q) use ($document) {
                        $q->where('tenant_id', $document->tenant_id);
                    })
                    ->where('slug', $categorySlug)
                    ->first();

            if (!$category) {
                Log::warning("Category not found for AI classification", [
                    'document_id' => $document->id,
                    'category_slug' => $categorySlug
                ]);
                continue;
            }

            try {
                $document->assignCategory(
                    $category,
                    'ai_auto',
                    $confidence,
                    null,
                    $reason,
                    [
                        'ai_classification' => $classification,
                        'auto_assigned' => true
                    ]
                );

                Log::info("Auto-assigned category to document", [
                    'document_id' => $document->id,
                    'category_id' => $category->id,
                    'confidence' => $confidence
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to auto-assign category", [
                    'document_id' => $document->id,
                    'category_id' => $category->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function calculateOverallConfidence(array $aiResults): float
    {
        $confidenceScores = [];
        
        // Add classification confidence scores
        if (isset($aiResults['classification'])) {
            foreach ($aiResults['classification'] as $classification) {
                if (isset($classification['konfidenz'])) {
                    $confidenceScores[] = $classification['konfidenz'];
                }
            }
        }
        
        // Add other confidence indicators
        if (!empty($aiResults['summary'])) {
            $confidenceScores[] = 0.8; // Assume good confidence if summary generated
        }
        
        if (!empty($aiResults['tags'])) {
            $confidenceScores[] = 0.7; // Assume good confidence if tags generated
        }

        return empty($confidenceScores) ? 0.5 : array_sum($confidenceScores) / count($confidenceScores);
    }

    private function markAsCompleted(Document $document, array $data): void
    {
        $document->updateAIProcessingStatus('completed', $data);
        
        DocumentActivity::logAIProcessing(
            $document,
            'classification_and_analysis',
            true,
            [
                'categories_assigned' => count($data['ai_classification'] ?? []),
                'confidence_score' => $data['ai_confidence_score'] ?? 0,
                'language' => $data['language'] ?? 'unknown',
                'content_length' => strlen($data['extracted_text'] ?? ''),
                'tags_count' => count($data['ai_tags'] ?? [])
            ]
        );
    }

    public function failed(\Throwable $exception): void
    {
        $document = Document::find($this->documentId);
        
        if ($document) {
            $document->updateAIProcessingStatus('failed');
            
            DocumentActivity::logError(
                $document,
                'ai_processing_job_failed',
                $exception->getMessage(),
                [
                    'exception' => get_class($exception),
                    'job_attempts' => $this->attempts()
                ]
            );
        }

        Log::error("ProcessDocumentWithAI job failed permanently", [
            'document_id' => $this->documentId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage()
        ]);
    }
}