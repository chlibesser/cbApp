<?php

namespace App\Core\AI\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AITemplateService
{
    private string $templatesPath;
    private bool $cacheEnabled;
    private int $cacheTtl;

    public function __construct()
    {
        $this->templatesPath = config('ai.templates.storage_path');
        $this->cacheEnabled = config('ai.templates.cache_enabled', true);
        $this->cacheTtl = config('ai.templates.cache_ttl', 3600);
        
        $this->ensureTemplatesDirectory();
    }

    public function getTemplate(string $name): string
    {
        $cacheKey = "ai_template_{$name}";
        
        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $templatePath = $this->getTemplatePath($name);
        
        if (!File::exists($templatePath)) {
            throw new \Exception("AI template '{$name}' not found at {$templatePath}");
        }

        $content = File::get($templatePath);
        
        if ($this->cacheEnabled) {
            Cache::put($cacheKey, $content, $this->cacheTtl);
        }

        return $content;
    }

    public function processTemplate(string $template, array $variables): string
    {
        $processed = $template;
        
        foreach ($variables as $key => $value) {
            $placeholder = '{' . $key . '}';
            $processed = str_replace($placeholder, $value, $processed);
        }

        // Check for unresolved placeholders
        if (preg_match_all('/\{([^}]+)\}/', $processed, $matches)) {
            $unresolved = array_unique($matches[1]);
            Log::warning('Unresolved placeholders in AI template', [
                'placeholders' => $unresolved,
                'provided_variables' => array_keys($variables)
            ]);
        }

        return $processed;
    }

    public function saveTemplate(string $name, string $content): void
    {
        $this->validateTemplate($content);
        
        $templatePath = $this->getTemplatePath($name);
        File::put($templatePath, $content);
        
        // Clear cache
        if ($this->cacheEnabled) {
            Cache::forget("ai_template_{$name}");
        }

        Log::info("AI template saved", ['name' => $name, 'path' => $templatePath]);
    }

    public function deleteTemplate(string $name): void
    {
        $templatePath = $this->getTemplatePath($name);
        
        if (File::exists($templatePath)) {
            File::delete($templatePath);
            
            // Clear cache
            if ($this->cacheEnabled) {
                Cache::forget("ai_template_{$name}");
            }

            Log::info("AI template deleted", ['name' => $name]);
        }
    }

    public function listTemplates(): array
    {
        $templates = [];
        
        if (!File::exists($this->templatesPath)) {
            return $templates;
        }

        $files = File::files($this->templatesPath);
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'txt') {
                $name = $file->getFilenameWithoutExtension();
                $templates[$name] = [
                    'name' => $name,
                    'path' => $file->getPathname(),
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                ];
            }
        }

        return $templates;
    }

    public function templateExists(string $name): bool
    {
        return File::exists($this->getTemplatePath($name));
    }

    public function getTemplateInfo(string $name): array
    {
        $templatePath = $this->getTemplatePath($name);
        
        if (!File::exists($templatePath)) {
            throw new \Exception("Template '{$name}' not found");
        }

        $content = $this->getTemplate($name);
        $placeholders = $this->extractPlaceholders($content);

        return [
            'name' => $name,
            'path' => $templatePath,
            'size' => File::size($templatePath),
            'modified' => File::lastModified($templatePath),
            'placeholders' => $placeholders,
            'content_preview' => substr($content, 0, 200),
        ];
    }

    public function getHealthStatus(): array
    {
        $templates = $this->listTemplates();
        $defaultTemplates = config('ai.templates.defaults', []);
        $missingDefaults = array_diff($defaultTemplates, array_keys($templates));

        return [
            'templates_path' => $this->templatesPath,
            'path_exists' => File::exists($this->templatesPath),
            'path_writable' => File::isWritable($this->templatesPath),
            'total_templates' => count($templates),
            'default_templates_count' => count($defaultTemplates),
            'missing_defaults' => $missingDefaults,
            'cache_enabled' => $this->cacheEnabled,
        ];
    }

    private function getTemplatePath(string $name): string
    {
        // Sanitize template name
        $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
        return $this->templatesPath . DIRECTORY_SEPARATOR . $sanitizedName . '.txt';
    }

    private function ensureTemplatesDirectory(): void
    {
        if (!File::exists($this->templatesPath)) {
            File::makeDirectory($this->templatesPath, 0755, true);
            Log::info("Created AI templates directory", ['path' => $this->templatesPath]);
        }
    }

    private function validateTemplate(string $content): void
    {
        $maxSize = config('ai.templates.validation.max_template_size', 50000);
        
        if (strlen($content) > $maxSize) {
            throw new \Exception("Template size exceeds maximum of {$maxSize} bytes");
        }

        // Check for forbidden patterns
        $forbiddenPatterns = config('ai.templates.validation.forbidden_patterns', []);
        foreach ($forbiddenPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new \Exception("Template contains forbidden pattern: {$pattern}");
            }
        }
    }

    private function extractPlaceholders(string $content): array
    {
        if (preg_match_all('/\{([^}]+)\}/', $content, $matches)) {
            return array_unique($matches[1]);
        }

        return [];
    }
}