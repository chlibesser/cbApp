<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default AI provider that will be used by the
    | AI service when no specific provider is requested.
    |
    */

    'default' => env('AI_DEFAULT_PROVIDER', 'claude'),

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the AI providers for your application. Each
    | provider can have its own settings and credentials.
    |
    */

    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'default_model' => env('OPENAI_DEFAULT_MODEL', 'gpt-4-turbo-preview'),
            'timeout' => env('OPENAI_TIMEOUT', 60),
            'max_retries' => env('OPENAI_MAX_RETRIES', 3),
            'retry_delay' => env('OPENAI_RETRY_DELAY', 1000), // milliseconds
        ],

        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
            'model' => env('ANTHROPIC_MODEL', 'claude-3-opus-20240229'),
            'timeout' => env('ANTHROPIC_TIMEOUT', 60),
        ],
        
        'claude' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
            'model' => env('ANTHROPIC_MODEL', 'claude-3-opus-20240229'),
            'timeout' => env('ANTHROPIC_TIMEOUT', 60),
        ],

        'azure_openai' => [
            'api_key' => env('AZURE_OPENAI_API_KEY'),
            'endpoint' => env('AZURE_OPENAI_ENDPOINT'),
            'deployment' => env('AZURE_OPENAI_DEPLOYMENT'),
            'api_version' => env('AZURE_OPENAI_API_VERSION', '2024-02-01'),
            'timeout' => env('AZURE_OPENAI_TIMEOUT', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Templates Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AI prompt templates used throughout the application.
    |
    */

    'templates' => [
        'storage_path' => storage_path('app/ai/templates'),
        'cache_enabled' => env('AI_TEMPLATES_CACHE', true),
        'cache_ttl' => env('AI_TEMPLATES_CACHE_TTL', 3600), // 1 hour
        
        // Default templates that come with the system
        'defaults' => [
            'document_classification',
            'document_analysis', 
            'content_extraction',
            'language_detection',
            'category_suggestion',
            'text_summarization',
            'keyword_extraction',
            'sentiment_analysis',
        ],

        // Template validation
        'validation' => [
            'max_template_size' => 50000, // 50KB
            'required_placeholders' => [], // Placeholders that must exist in templates
            'forbidden_patterns' => [], // Patterns not allowed in templates
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Processing Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for AI-powered content processing features.
    |
    */

    'content_processing' => [
        'enabled' => env('AI_CONTENT_PROCESSING', true),
        'auto_process_documents' => env('AI_AUTO_PROCESS_DOCUMENTS', true),
        'queue' => env('AI_PROCESSING_QUEUE', 'ai-processing'),
        
        // Processing limits
        'max_content_length' => env('AI_MAX_CONTENT_LENGTH', 100000), // 100KB
        'max_daily_requests' => env('AI_MAX_DAILY_REQUESTS', 10000),
        'rate_limit_per_minute' => env('AI_RATE_LIMIT_PER_MINUTE', 60),
        
        // Confidence thresholds
        'auto_assign_threshold' => env('AI_AUTO_ASSIGN_THRESHOLD', 0.8),
        'manual_review_threshold' => env('AI_MANUAL_REVIEW_THRESHOLD', 0.5),
        'reject_threshold' => env('AI_REJECT_THRESHOLD', 0.2),

        // Content extraction settings
        'extraction' => [
            'pdf_enabled' => env('AI_PDF_EXTRACTION', true),
            'ocr_enabled' => env('AI_OCR_EXTRACTION', true),
            'office_enabled' => env('AI_OFFICE_EXTRACTION', true),
            'max_pages' => env('AI_MAX_PAGES', 50),
            'ocr_languages' => explode(',', env('AI_OCR_LANGUAGES', 'deu,eng,fra,ita')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | AI responses and processing results caching settings.
    |
    */

    'caching' => [
        'enabled' => env('AI_CACHING_ENABLED', true),
        'default_ttl' => env('AI_CACHE_TTL', 3600), // 1 hour
        'long_ttl' => env('AI_CACHE_LONG_TTL', 86400), // 24 hours
        
        'cache_keys' => [
            'responses' => 'ai:responses',
            'models' => 'ai:models', 
            'templates' => 'ai:templates',
            'provider_health' => 'ai:provider_health',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cost Tracking Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for tracking AI usage and costs.
    |
    */

    'cost_tracking' => [
        'enabled' => env('AI_COST_TRACKING', true),
        'daily_budget' => env('AI_DAILY_BUDGET', 100), // USD
        'monthly_budget' => env('AI_MONTHLY_BUDGET', 2000), // USD
        'alert_threshold' => env('AI_ALERT_THRESHOLD', 0.8), // 80% of budget
        
        'cost_per_provider' => [
            'openai' => [
                'currency' => 'USD',
                'billing_unit' => '1k_tokens',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for AI processing and data handling.
    |
    */

    'security' => [
        'content_filtering' => [
            'enabled' => env('AI_CONTENT_FILTERING', true),
            'block_sensitive_data' => env('AI_BLOCK_SENSITIVE_DATA', true),
            'pii_detection' => env('AI_PII_DETECTION', true),
        ],
        
        'data_retention' => [
            'request_logs_days' => env('AI_REQUEST_LOGS_RETENTION', 30),
            'response_cache_days' => env('AI_RESPONSE_CACHE_RETENTION', 7),
            'error_logs_days' => env('AI_ERROR_LOGS_RETENTION', 90),
        ],
        
        'rate_limiting' => [
            'enabled' => env('AI_RATE_LIMITING', true),
            'global_per_minute' => env('AI_GLOBAL_RATE_LIMIT', 1000),
            'per_user_per_minute' => env('AI_USER_RATE_LIMIT', 10),
            'per_tenant_per_minute' => env('AI_TENANT_RATE_LIMIT', 100),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | AI-specific logging settings.
    |
    */

    'logging' => [
        'enabled' => env('AI_LOGGING_ENABLED', true),
        'level' => env('AI_LOG_LEVEL', 'info'),
        'log_requests' => env('AI_LOG_REQUESTS', true),
        'log_responses' => env('AI_LOG_RESPONSES', false), // May contain sensitive data
        'log_errors' => env('AI_LOG_ERRORS', true),
        'log_performance' => env('AI_LOG_PERFORMANCE', true),
        
        'channels' => [
            'default' => env('AI_LOG_CHANNEL', 'daily'),
            'errors' => env('AI_ERROR_LOG_CHANNEL', 'daily'),
            'performance' => env('AI_PERFORMANCE_LOG_CHANNEL', 'daily'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for development and testing environments.
    |
    */

    'development' => [
        'mock_responses' => env('AI_MOCK_RESPONSES', true), // Aktiviert für Demo
        'simulate_delays' => env('AI_SIMULATE_DELAYS', true), // Realistisches Verhalten
        'debug_mode' => env('AI_DEBUG_MODE', true), // Debugging aktiviert
        'test_prompts' => env('AI_TEST_PROMPTS', false),
        
        'mock_settings' => [
            'response_delay_ms' => env('AI_MOCK_DELAY', 2000), // 2 Sekunden für realistisches Verhalten
            'failure_rate' => env('AI_MOCK_FAILURE_RATE', 0.0), // 0.0-1.0
            'default_response' => 'Mock AI response for testing purposes.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific AI features.
    |
    */

    'features' => [
        'document_classification' => env('AI_FEATURE_DOCUMENT_CLASSIFICATION', true),
        'content_extraction' => env('AI_FEATURE_CONTENT_EXTRACTION', true),
        'text_analysis' => env('AI_FEATURE_TEXT_ANALYSIS', true),
        'language_detection' => env('AI_FEATURE_LANGUAGE_DETECTION', true),
        'summarization' => env('AI_FEATURE_SUMMARIZATION', true),
        'keyword_extraction' => env('AI_FEATURE_KEYWORD_EXTRACTION', true),
        'sentiment_analysis' => env('AI_FEATURE_SENTIMENT_ANALYSIS', false),
        'translation' => env('AI_FEATURE_TRANSLATION', false),
        'chat_assistant' => env('AI_FEATURE_CHAT_ASSISTANT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI Specific Configuration
    |--------------------------------------------------------------------------
    |
    | Additional configuration specifically for OpenAI provider.
    |
    */

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'default_model' => env('OPENAI_DEFAULT_MODEL', 'gpt-4-turbo-preview'),
        'timeout' => env('OPENAI_TIMEOUT', 60),
        'max_retries' => env('OPENAI_MAX_RETRIES', 3),
        'retry_delay' => env('OPENAI_RETRY_DELAY', 1000),
        
        'models' => [
            'chat' => env('OPENAI_CHAT_MODEL', 'gpt-4-turbo-preview'),
            'analysis' => env('OPENAI_ANALYSIS_MODEL', 'gpt-4'),
            'classification' => env('OPENAI_CLASSIFICATION_MODEL', 'gpt-3.5-turbo'),
            'summarization' => env('OPENAI_SUMMARIZATION_MODEL', 'gpt-3.5-turbo'),
            'extraction' => env('OPENAI_EXTRACTION_MODEL', 'gpt-3.5-turbo-16k'),
        ],
        
        'parameters' => [
            'temperature' => env('OPENAI_TEMPERATURE', 0.7),
            'max_tokens' => env('OPENAI_MAX_TOKENS', 2048),
            'top_p' => env('OPENAI_TOP_P', 1),
            'frequency_penalty' => env('OPENAI_FREQUENCY_PENALTY', 0),
            'presence_penalty' => env('OPENAI_PRESENCE_PENALTY', 0),
        ],
    ],
];