<?php

namespace App\Infrastructure\AI;

use App\Core\AI\Contracts\AIProviderInterface;
use App\Core\AI\ValueObjects\AIRequest;
use App\Core\AI\ValueObjects\AIResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeProvider implements AIProviderInterface
{
    private ?string $apiKey;
    private string $baseUrl = 'https://api.anthropic.com';
    private string $defaultModel = 'claude-3-haiku-20240307'; // Schneller und günstiger für Demo

    public function __construct()
    {
        $this->apiKey = config('ai.providers.claude.api_key', '');
    }

    public function getName(): string
    {
        return 'claude';
    }

    public function isAvailable(): bool
    {
        // Für Development: Immer verfügbar (Mock-System)
        return true;
    }

    public function process(AIRequest $request): AIResponse
    {
        $startTime = microtime(true);
        
        try {
            // Für Development: Mock-Antworten wenn kein API Key
            if (empty($this->apiKey) || config('ai.development.mock_responses', true)) {
                return $this->generateMockResponse($request, $startTime);
            }

            // Echte Claude API Anfrage
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(30)->post($this->baseUrl . '/v1/messages', [
                'model' => $request->getModel() ?: $this->defaultModel,
                'max_tokens' => $request->getParameter('max_tokens', 4000),
                'temperature' => $request->getParameter('temperature', 0.7),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $request->getPrompt()
                    ]
                ]
            ]);

            if ($response->failed()) {
                throw new \Exception('Claude API request failed: ' . $response->body());
            }

            $data = $response->json();
            $processingTime = intval((microtime(true) - $startTime) * 1000);

            return new AIResponse(
                content: $data['content'][0]['text'] ?? '',
                success: true,
                metadata: [
                    'model' => $data['model'] ?? $request->getModel(),
                    'usage' => $data['usage'] ?? [],
                    'finish_reason' => $data['stop_reason'] ?? null,
                ],
                provider: $this->getName(),
                processingTimeMs: $processingTime
            );

        } catch (\Exception $e) {
            Log::error('Claude API error', [
                'error' => $e->getMessage(),
                'request_id' => $request->getId()
            ]);

            // Fallback zu Mock bei Fehler
            return $this->generateMockResponse($request, $startTime, $e->getMessage());
        }
    }

    public function getAvailableModels(): array
    {
        return [
            'claude-3-opus-20240229' => ['name' => 'Claude 3 Opus', 'context_window' => 200000],
            'claude-3-sonnet-20240229' => ['name' => 'Claude 3 Sonnet', 'context_window' => 200000],
            'claude-3-haiku-20240307' => ['name' => 'Claude 3 Haiku', 'context_window' => 200000],
        ];
    }

    public function validateRequest(AIRequest $request): array
    {
        $errors = [];
        
        if (empty($request->getPrompt())) {
            $errors[] = 'Prompt darf nicht leer sein';
        }
        
        if (strlen($request->getPrompt()) > 150000) { // ~200k tokens context
            $errors[] = 'Prompt ist zu lang (max. 150.000 Zeichen)';
        }
        
        return $errors;
    }

    public function getDefaultParameters(): array
    {
        return [
            'model' => $this->defaultModel,
            'max_tokens' => 4000,
            'temperature' => 0.7,
            'top_p' => 1.0,
        ];
    }

    public function getCostEstimate(AIRequest $request): array
    {
        $inputTokens = intval(strlen($request->getPrompt()) / 4); // Grobe Schätzung
        $outputTokens = $request->getParameter('max_tokens', 1000);
        
        // Claude 3 Haiku Preise (März 2024)
        $inputCost = ($inputTokens / 1000000) * 0.25; // $0.25 per 1M tokens
        $outputCost = ($outputTokens / 1000000) * 1.25; // $1.25 per 1M tokens
        
        return [
            'estimated_input_tokens' => $inputTokens,
            'estimated_output_tokens' => $outputTokens,
            'estimated_cost_usd' => round($inputCost + $outputCost, 4),
            'currency' => 'USD'
        ];
    }

    public function getHealthStatus(): array
    {
        return [
            'provider' => $this->getName(),
            'available' => $this->isAvailable(),
            'api_key_configured' => !empty($this->apiKey),
            'models_available' => count($this->getAvailableModels()),
            'last_check' => now()->toISOString(),
            'status' => $this->isAvailable() ? 'healthy' : 'degraded',
        ];
    }

    private function generateMockResponse(AIRequest $request, float $startTime, ?string $error = null): AIResponse
    {
        // Simuliere Antwortzeit für Realismus
        if (config('ai.development.simulate_delays', true)) {
            usleep(config('ai.development.mock_settings.response_delay_ms', 1500) * 1000);
        }

        $processingTime = intval((microtime(true) - $startTime) * 1000);
        $content = $this->generateIntelligentMockContent($request);

        return new AIResponse(
            content: $content,
            success: true,
            metadata: [
                'model' => $request->getModel() ?: $this->defaultModel,
                'usage' => ['input_tokens' => 100, 'output_tokens' => 50],
                'finish_reason' => 'stop',
                'mock' => true,
            ],
            provider: $this->getName() . '-mock',
            processingTimeMs: $processingTime,
            error: $error
        );
    }

    private function generateIntelligentMockContent(AIRequest $request): string
    {
        $prompt = strtolower($request->getPrompt());
        
        // Sprach-Erkennung
        if (str_contains($prompt, 'sprache') || str_contains($prompt, 'language') || str_contains($prompt, 'iso 639')) {
            return 'de';
        }
        
        // Dokument-Klassifikation
        if (str_contains($prompt, 'kategorisierung') || str_contains($prompt, 'klassifiz')) {
            return $this->generateMockClassification($prompt);
        }
        
        // Dokument-Analyse  
        if (str_contains($prompt, 'analys') || str_contains($prompt, 'zusammenfassung')) {
            return json_encode([
                'summary' => 'Intelligente Mock-Zusammenfassung: Dieses Dokument wurde automatisch von der Claude AI Demo analysiert.',
                'metadata' => [
                    'datum' => now()->format('Y-m-d'),
                    'typ' => 'Mock-Dokument',
                    'confidence' => 0.95
                ],
                'tags' => ['Demo', 'AI-Integration', 'Mock-Analyse'],
                'hauptthemen' => ['Dokumentenmanagement', 'Automatisierung']
            ], JSON_UNESCAPED_UNICODE);
        }
        
        return 'Intelligente Mock-Antwort von Claude AI Demo-System.';
    }
    
    private function generateMockClassification(string $prompt): string
    {
        // Extrahiere verfügbare Kategorien aus dem Prompt
        $classifications = [];
        
        // Standard-Kategorien für Demo
        if (str_contains($prompt, 'dokumenttyp') || str_contains($prompt, 'document_type')) {
            $classifications[] = [
                'gruppe' => 'Dokumenttyp',
                'kategorie' => 'invoice',
                'konfidenz' => 0.89,
                'begründung' => 'Mock-AI erkennt typische Rechnungsmerkmale im Dokumenttext'
            ];
        }
        
        if (str_contains($prompt, 'priorität') || str_contains($prompt, 'priority')) {
            $classifications[] = [
                'gruppe' => 'Priorität', 
                'kategorie' => 'high',
                'konfidenz' => 0.75,
                'begründung' => 'Mock-AI identifiziert hohe Priorität basierend auf Inhaltsanalyse'
            ];
        }
        
        if (str_contains($prompt, 'abteilung') || str_contains($prompt, 'department')) {
            $classifications[] = [
                'gruppe' => 'Abteilung',
                'kategorie' => 'finance', 
                'konfidenz' => 0.82,
                'begründung' => 'Mock-AI ordnet Dokument der Finanzabteilung zu basierend auf Inhalt'
            ];
        }
        
        return json_encode([
            'kategorisierungen' => $classifications
        ], JSON_UNESCAPED_UNICODE);
    }
}