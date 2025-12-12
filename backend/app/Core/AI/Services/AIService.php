<?php

namespace App\Core\AI\Services;

use App\Core\AI\Contracts\AIProviderInterface;
use App\Core\AI\ValueObjects\AIRequest;
use App\Core\AI\ValueObjects\AIResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AIService
{
    public function __construct(
        private AIProviderInterface $provider,
        private AITemplateService $templateService
    ) {}

    public function processWithTemplate(string $templateName, array $variables = [], array $options = []): string
    {
        // Load template
        $template = $this->templateService->getTemplate($templateName);
        
        // Replace variables in template
        $prompt = $this->templateService->processTemplate($template, $variables);
        
        // Create AI request
        $request = new AIRequest(
            id: Str::uuid()->toString(),
            prompt: $prompt,
            model: $options['model'] ?? config('ai.openai.default_model'),
            parameters: array_merge(
                config('ai.openai.parameters', []),
                $options['parameters'] ?? []
            )
        );

        // Log request
        Log::info('Processing AI request with template', [
            'template' => $templateName,
            'request_id' => $request->getId(),
            'model' => $request->getModel(),
            'variables' => array_keys($variables),
        ]);

        // Process with provider
        $response = $this->provider->process($request);

        // Log response
        if ($response->isSuccess()) {
            Log::info('AI request successful', [
                'template' => $templateName,
                'request_id' => $request->getId(),
                'processing_time_ms' => $response->getProcessingTimeMs(),
                'provider' => $response->getProvider(),
            ]);
        } else {
            Log::error('AI request failed', [
                'template' => $templateName,
                'request_id' => $request->getId(),
                'error' => $response->getError(),
                'provider' => $response->getProvider(),
            ]);
        }

        return $response->getContent();
    }

    public function process(string $prompt, array $options = []): AIResponse
    {
        $request = new AIRequest(
            id: Str::uuid()->toString(),
            prompt: $prompt,
            model: $options['model'] ?? config('ai.openai.default_model'),
            parameters: array_merge(
                config('ai.openai.parameters', []),
                $options['parameters'] ?? []
            )
        );

        return $this->provider->process($request);
    }

    public function getProvider(): AIProviderInterface
    {
        return $this->provider;
    }

    public function isAvailable(): bool
    {
        return $this->provider->isAvailable();
    }

    public function getAvailableModels(): array
    {
        return $this->provider->getAvailableModels();
    }

    public function validateRequest(string $prompt, array $options = []): array
    {
        $request = new AIRequest(
            id: 'validation',
            prompt: $prompt,
            model: $options['model'] ?? config('ai.openai.default_model'),
            parameters: $options['parameters'] ?? []
        );

        return $this->provider->validateRequest($request);
    }

    public function getCostEstimate(string $prompt, array $options = []): array
    {
        $request = new AIRequest(
            id: 'cost-estimation',
            prompt: $prompt,
            model: $options['model'] ?? config('ai.openai.default_model'),
            parameters: $options['parameters'] ?? []
        );

        return $this->provider->getCostEstimate($request);
    }

    public function getHealthStatus(): array
    {
        return [
            'service' => 'AIService',
            'provider' => $this->provider->getHealthStatus(),
            'templates' => $this->templateService->getHealthStatus(),
        ];
    }
}