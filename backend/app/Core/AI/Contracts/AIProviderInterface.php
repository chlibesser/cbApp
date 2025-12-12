<?php

namespace App\Core\AI\Contracts;

use App\Core\AI\ValueObjects\AIRequest;
use App\Core\AI\ValueObjects\AIResponse;

interface AIProviderInterface
{
    /**
     * Get the provider name
     */
    public function getName(): string;

    /**
     * Check if the provider is available
     */
    public function isAvailable(): bool;

    /**
     * Process an AI request
     */
    public function process(AIRequest $request): AIResponse;

    /**
     * Get available models for this provider
     */
    public function getAvailableModels(): array;

    /**
     * Validate a request before processing
     */
    public function validateRequest(AIRequest $request): array;

    /**
     * Get default parameters for this provider
     */
    public function getDefaultParameters(): array;

    /**
     * Get cost estimate for a request
     */
    public function getCostEstimate(AIRequest $request): array;

    /**
     * Get health status of the provider
     */
    public function getHealthStatus(): array;
}