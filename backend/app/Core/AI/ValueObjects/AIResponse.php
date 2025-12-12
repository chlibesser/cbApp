<?php

namespace App\Core\AI\ValueObjects;

class AIResponse
{
    public function __construct(
        private string $content,
        private bool $success,
        private array $metadata = [],
        private string $provider = '',
        private int $processingTimeMs = 0,
        private ?string $error = null
    ) {}

    public function getContent(): string
    {
        return $this->content;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getProcessingTimeMs(): int
    {
        return $this->processingTimeMs;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function hasError(): bool
    {
        return !$this->success && $this->error !== null;
    }

    public function getUsage(): array
    {
        return $this->getMetadataValue('usage', []);
    }

    public function getModel(): string
    {
        return $this->getMetadataValue('model', 'unknown');
    }

    public function getFinishReason(): ?string
    {
        return $this->getMetadataValue('finish_reason');
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'success' => $this->success,
            'metadata' => $this->metadata,
            'provider' => $this->provider,
            'processing_time_ms' => $this->processingTimeMs,
            'error' => $this->error,
        ];
    }
}