<?php

namespace App\Infrastructure\AI;

use App\Core\AI\Contracts\AIProviderInterface;
use App\Core\AI\ValueObjects\AIRequest;
use App\Core\AI\ValueObjects\AIResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OpenAIProvider implements AIProviderInterface
{
    private string $apiKey;
    private string $baseUrl;
    private string $defaultModel;
    private array $config;

    public function __construct()
    {
        $this->apiKey = config('ai.openai.api_key');
        $this->baseUrl = config('ai.openai.base_url', 'https://api.openai.com/v1');
        $this->defaultModel = config('ai.openai.default_model', 'gpt-4-turbo-preview');
        $this->config = config('ai.openai', []);

        if (empty($this->apiKey)) {
            throw new \Exception('OpenAI API key nicht konfiguriert');
        }
    }

    public function getName(): string
    {
        return 'openai';
    }

    public function isAvailable(): bool
    {
        try {
            // Simple availability check - try to list models
            $response = Http::withToken($this->apiKey)
                ->timeout(10)
                ->get($this->baseUrl . '/models');

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('OpenAI availability check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function process(AIRequest $request): AIResponse
    {
        $startTime = microtime(true);

        try {
            $response = $this->makeRequest($request);
            $processingTime = (microtime(true) - $startTime) * 1000;

            return new AIResponse(
                content: $response['choices'][0]['message']['content'] ?? '',
                success: true,
                metadata: [
                    'model' => $response['model'] ?? $request->getModel(),
                    'usage' => $response['usage'] ?? [],
                    'processing_time_ms' => round($processingTime),
                    'finish_reason' => $response['choices'][0]['finish_reason'] ?? null,
                    'provider' => $this->getName()
                ],
                provider: $this->getName(),
                processingTimeMs: round($processingTime)
            );

        } catch (\Exception $e) {
            $processingTime = (microtime(true) - $startTime) * 1000;

            Log::error('OpenAI request failed', [
                'error' => $e->getMessage(),
                'request_id' => $request->getId(),
                'model' => $request->getModel(),
                'processing_time' => round($processingTime)
            ]);

            return new AIResponse(
                content: '',
                success: false,
                metadata: [
                    'error' => $e->getMessage(),
                    'processing_time_ms' => round($processingTime),
                    'provider' => $this->getName()
                ],
                provider: $this->getName(),
                processingTimeMs: round($processingTime),
                error: $e->getMessage()
            );
        }
    }

    public function getAvailableModels(): array
    {
        $cacheKey = 'openai_models_' . md5($this->apiKey);
        
        return Cache::remember($cacheKey, 3600, function () {
            try {
                $response = Http::withToken($this->apiKey)
                    ->timeout(10)
                    ->get($this->baseUrl . '/models');

                if ($response->successful()) {
                    $models = collect($response->json('data', []))
                        ->where('object', 'model')
                        ->pluck('id')
                        ->filter(fn($id) => str_contains($id, 'gpt'))
                        ->sort()
                        ->values()
                        ->toArray();

                    return $models;
                }

                return $this->getDefaultModels();
            } catch (\Exception $e) {
                Log::warning('Failed to fetch OpenAI models', ['error' => $e->getMessage()]);
                return $this->getDefaultModels();
            }
        });
    }

    public function validateRequest(AIRequest $request): array
    {
        $errors = [];

        // Check if model is available
        if (!in_array($request->getModel(), $this->getAvailableModels())) {
            $errors[] = "Model '{$request->getModel()}' ist nicht verfügbar";
        }

        // Check content length
        $contentLength = strlen($request->getPrompt());
        $maxLength = $this->getModelMaxTokens($request->getModel()) * 3; // Rough estimate (1 token ≈ 3-4 chars)
        
        if ($contentLength > $maxLength) {
            $errors[] = "Prompt zu lang: {$contentLength} Zeichen (max: {$maxLength})";
        }

        // Check parameters
        $parameters = $request->getParameters();
        
        if (isset($parameters['temperature']) && ($parameters['temperature'] < 0 || $parameters['temperature'] > 2)) {
            $errors[] = "Temperature muss zwischen 0 und 2 liegen";
        }

        if (isset($parameters['max_tokens']) && ($parameters['max_tokens'] < 1 || $parameters['max_tokens'] > 4096)) {
            $errors[] = "max_tokens muss zwischen 1 und 4096 liegen";
        }

        return $errors;
    }

    public function getDefaultParameters(): array
    {
        return [
            'temperature' => 0.7,
            'max_tokens' => 2048,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ];
    }

    public function getCostEstimate(AIRequest $request): array
    {
        $model = $request->getModel();
        $promptTokens = $this->estimateTokens($request->getPrompt());
        $maxTokens = $request->getParameters()['max_tokens'] ?? 1000;
        
        // Pricing per 1K tokens (as of 2024)
        $pricing = $this->getModelPricing($model);
        
        $inputCost = ($promptTokens / 1000) * $pricing['input'];
        $outputCost = ($maxTokens / 1000) * $pricing['output'];
        $totalCost = $inputCost + $outputCost;

        return [
            'model' => $model,
            'estimated_prompt_tokens' => $promptTokens,
            'max_completion_tokens' => $maxTokens,
            'input_cost_usd' => round($inputCost, 6),
            'output_cost_usd' => round($outputCost, 6),
            'total_cost_usd' => round($totalCost, 6),
            'currency' => 'USD',
            'pricing_date' => '2024-01'
        ];
    }

    private function makeRequest(AIRequest $request): array
    {
        $payload = [
            'model' => $request->getModel() ?: $this->defaultModel,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $request->getPrompt()
                ]
            ]
        ];

        // Add parameters
        $parameters = array_merge($this->getDefaultParameters(), $request->getParameters());
        $payload = array_merge($payload, $parameters);

        // Log request (without sensitive data)
        Log::info('OpenAI API request', [
            'model' => $payload['model'],
            'prompt_length' => strlen($request->getPrompt()),
            'parameters' => $parameters,
            'request_id' => $request->getId()
        ]);

        $response = Http::withToken($this->apiKey)
            ->timeout($this->config['timeout'] ?? 60)
            ->retry(3, 1000, function ($exception, $request) {
                // Retry on rate limit or server errors
                return $exception instanceof \Illuminate\Http\Client\RequestException &&
                       in_array($exception->response?->status(), [429, 500, 502, 503, 504]);
            })
            ->post($this->baseUrl . '/chat/completions', $payload);

        if ($response->failed()) {
            $error = $response->json('error.message', 'Unknown error');
            throw new \Exception("OpenAI API Error: {$error} (Status: {$response->status()})");
        }

        $data = $response->json();

        // Log response metadata
        Log::info('OpenAI API response', [
            'usage' => $data['usage'] ?? [],
            'model' => $data['model'] ?? $payload['model'],
            'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
            'request_id' => $request->getId()
        ]);

        return $data;
    }

    private function getDefaultModels(): array
    {
        return [
            'gpt-4-turbo-preview',
            'gpt-4',
            'gpt-3.5-turbo',
            'gpt-3.5-turbo-16k'
        ];
    }

    private function getModelMaxTokens(string $model): int
    {
        return match (true) {
            str_contains($model, 'gpt-4-turbo') => 128000,
            str_contains($model, 'gpt-4') => 8192,
            str_contains($model, 'gpt-3.5-turbo-16k') => 16384,
            str_contains($model, 'gpt-3.5-turbo') => 4096,
            default => 4096
        };
    }

    private function getModelPricing(string $model): array
    {
        // Pricing per 1K tokens in USD (approximate as of 2024)
        return match (true) {
            str_contains($model, 'gpt-4-turbo') => ['input' => 0.01, 'output' => 0.03],
            str_contains($model, 'gpt-4') => ['input' => 0.03, 'output' => 0.06],
            str_contains($model, 'gpt-3.5-turbo') => ['input' => 0.001, 'output' => 0.002],
            default => ['input' => 0.001, 'output' => 0.002]
        };
    }

    private function estimateTokens(string $text): int
    {
        // Rough estimation: 1 token ≈ 4 characters for English
        // For German text, it's usually a bit more
        return max(1, intval(strlen($text) / 3.5));
    }

    public function supportsStreaming(): bool
    {
        return true;
    }

    public function processStream(AIRequest $request, callable $callback): AIResponse
    {
        // TODO: Implement streaming support for real-time responses
        // For now, fall back to regular processing
        return $this->process($request);
    }

    public function getHealthStatus(): array
    {
        try {
            $startTime = microtime(true);
            $available = $this->isAvailable();
            $responseTime = (microtime(true) - $startTime) * 1000;

            return [
                'provider' => $this->getName(),
                'available' => $available,
                'response_time_ms' => round($responseTime),
                'last_check' => now()->toISOString(),
                'api_key_configured' => !empty($this->apiKey),
                'base_url' => $this->baseUrl,
                'default_model' => $this->defaultModel
            ];
        } catch (\Exception $e) {
            return [
                'provider' => $this->getName(),
                'available' => false,
                'error' => $e->getMessage(),
                'last_check' => now()->toISOString(),
                'api_key_configured' => !empty($this->apiKey)
            ];
        }
    }
}