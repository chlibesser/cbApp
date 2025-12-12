<?php

namespace App\Core\AI\ValueObjects;

class AIRequest
{
    public function __construct(
        private string $id,
        private string $prompt,
        private string $model,
        private array $parameters = []
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getPrompt(): string
    {
        return $this->prompt;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    public function withParameter(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->parameters[$key] = $value;
        return $clone;
    }

    public function withParameters(array $parameters): self
    {
        $clone = clone $this;
        $clone->parameters = array_merge($this->parameters, $parameters);
        return $clone;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'prompt' => $this->prompt,
            'model' => $this->model,
            'parameters' => $this->parameters,
        ];
    }
}