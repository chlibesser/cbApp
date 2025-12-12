<?php

namespace App\Providers;

use App\Core\AI\Services\AIService;
use App\Core\AI\Services\AITemplateService;
use App\Core\AI\Contracts\AIProviderInterface;
use App\Infrastructure\AI\OpenAIProvider;
use App\Infrastructure\AI\ClaudeProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register AI Template Service
        $this->app->singleton(AITemplateService::class, function ($app) {
            return new AITemplateService();
        });

        // Register AI Providers
        $this->app->bind('ai.provider.openai', OpenAIProvider::class);
        $this->app->bind('ai.provider.claude', ClaudeProvider::class);
        $this->app->bind('ai.provider.anthropic', ClaudeProvider::class); // Alias
        
        // TODO: Register additional providers
        // $this->app->bind('ai.provider.azure_openai', AzureOpenAIProvider::class);

        // Register default AI Provider based on config
        $this->app->singleton(AIProviderInterface::class, function ($app) {
            $defaultProvider = config('ai.default', 'openai');
            
            return match ($defaultProvider) {
                'openai' => $app->make('ai.provider.openai'),
                'claude', 'anthropic' => $app->make('ai.provider.claude'),
                // 'azure_openai' => $app->make('ai.provider.azure_openai'),
                default => throw new \InvalidArgumentException("Unsupported AI provider: {$defaultProvider}")
            };
        });

        // Register main AI Service
        $this->app->singleton(AIService::class, function ($app) {
            return new AIService(
                $app->make(AIProviderInterface::class),
                $app->make(AITemplateService::class)
            );
        });
    }

    public function boot(): void
    {
        // Publish AI configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/ai.php' => config_path('ai.php'),
            ], 'ai-config');

            // Publish AI templates
            $this->publishes([
                __DIR__.'/../../storage/app/ai/templates' => storage_path('app/ai/templates'),
            ], 'ai-templates');
        }

        // Validate AI configuration on boot
        $this->validateAIConfiguration();

        // Register AI health check
        if (config('ai.development.debug_mode')) {
            $this->registerHealthCheck();
        }
    }

    protected function validateAIConfiguration(): void
    {
        $defaultProvider = config('ai.default');
        $providerConfig = config("ai.providers.{$defaultProvider}");

        if (empty($providerConfig)) {
            throw new \Exception("AI provider '{$defaultProvider}' is not configured");
        }

        // Validate required provider settings
        if ($defaultProvider === 'openai') {
            if (empty($providerConfig['api_key'])) {
                \Log::warning('OpenAI API key is not configured. AI features will be disabled.');
            }
        }

        // Validate template directory
        $templatePath = config('ai.templates.storage_path');
        if (!is_dir($templatePath)) {
            \Log::info("Creating AI templates directory: {$templatePath}");
            mkdir($templatePath, 0755, true);
        }
    }

    protected function registerHealthCheck(): void
    {
        $this->app->booted(function () {
            if ($this->app->runningInConsole()) {
                return;
            }

            try {
                $aiService = $this->app->make(AIService::class);
                $provider = $this->app->make(AIProviderInterface::class);
                
                \Log::info('AI Service initialized', [
                    'provider' => $provider->getName(),
                    'available' => $provider->isAvailable(),
                    'models_count' => count($provider->getAvailableModels()),
                ]);
            } catch (\Exception $e) {
                \Log::error('AI Service initialization failed', [
                    'error' => $e->getMessage()
                ]);
            }
        });
    }

    public function provides(): array
    {
        return [
            AIService::class,
            AITemplateService::class,
            AIProviderInterface::class,
            'ai.provider.openai',
            'ai.provider.claude',
            'ai.provider.anthropic',
        ];
    }
}