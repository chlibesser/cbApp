<?php

namespace App\Domains\Category\Models;

use App\Core\Category\Enums\CategoryRuleType;
use App\Core\Category\Enums\CategoryRuleOperator;
use App\Core\Shared\Models\BaseModel;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class CategoryRule extends BaseModel
{
    protected $fillable = [
        'category_id',
        'tenant_id',
        'rule_type',
        'rule_value',
        'rule_operator',
        'weight',
        'is_mandatory',
        'is_exclusion',
        'is_active',
    ];

    protected $casts = [
        'rule_type' => CategoryRuleType::class,
        'rule_operator' => CategoryRuleOperator::class,
        'weight' => 'float',
        'is_mandatory' => 'boolean',
        'is_exclusion' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'rule_operator' => 'contains',
        'weight' => 1.0,
        'is_mandatory' => false,
        'is_exclusion' => false,
        'is_active' => true,
    ];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForCategory(Builder $query, string $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByType(Builder $query, CategoryRuleType $type): Builder
    {
        return $query->where('rule_type', $type);
    }

    public function scopeMandatory(Builder $query): Builder
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeExclusion(Builder $query): Builder
    {
        return $query->where('is_exclusion', true);
    }

    public function scopeOrderedByWeight(Builder $query): Builder
    {
        return $query->orderBy('weight', 'desc');
    }

    // Methods
    public function isMandatory(): bool
    {
        return $this->is_mandatory;
    }

    public function isExclusion(): bool
    {
        return $this->is_exclusion;
    }

    public function isKeywordRule(): bool
    {
        return $this->rule_type === CategoryRuleType::KEYWORD;
    }

    public function isContentRule(): bool
    {
        return $this->rule_type === CategoryRuleType::CONTENT;
    }

    public function isFilenameRule(): bool
    {
        return $this->rule_type === CategoryRuleType::FILENAME;
    }

    public function isPatternRule(): bool
    {
        return $this->rule_type === CategoryRuleType::PATTERN;
    }

    public function isMetadataRule(): bool
    {
        return $this->rule_type === CategoryRuleType::METADATA;
    }

    public function isRegexOperator(): bool
    {
        return $this->rule_operator === CategoryRuleOperator::REGEX;
    }

    public function getFormattedValue(): string
    {
        if ($this->isRegexOperator()) {
            return $this->rule_value;
        }
        
        return $this->rule_operator->formatValue($this->rule_value);
    }

    public function getSqlOperator(): string
    {
        return $this->rule_operator->sqlOperator();
    }

    public function getDisplayDescription(): string
    {
        $typeLabel = $this->rule_type->label();
        $operatorLabel = $this->rule_operator->label();
        $value = $this->rule_value;
        
        return "{$typeLabel} {$operatorLabel} '{$value}'";
    }

    public function getWeightPercentage(): int
    {
        return (int) round($this->weight * 100);
    }

    public function evaluate(array $documentData): bool
    {
        $target = $this->getTargetValue($documentData);
        
        if (is_null($target)) {
            return false;
        }
        
        return $this->performComparison($target);
    }

    protected function getTargetValue(array $documentData): ?string
    {
        return match($this->rule_type) {
            CategoryRuleType::KEYWORD => $documentData['content'] ?? null,
            CategoryRuleType::CONTENT => $documentData['content'] ?? null,
            CategoryRuleType::FILENAME => $documentData['filename'] ?? null,
            CategoryRuleType::PATTERN => $documentData['content'] ?? $documentData['filename'] ?? null,
            CategoryRuleType::METADATA => $this->getMetadataValue($documentData),
        };
    }

    protected function getMetadataValue(array $documentData): ?string
    {
        // Extract specific metadata based on rule_value
        // This would be expanded based on specific metadata requirements
        return $documentData['metadata'][$this->rule_value] ?? null;
    }

    protected function performComparison(string $target): bool
    {
        $value = $this->rule_value;
        
        return match($this->rule_operator) {
            CategoryRuleOperator::CONTAINS => str_contains(strtolower($target), strtolower($value)),
            CategoryRuleOperator::NOT_CONTAINS => !str_contains(strtolower($target), strtolower($value)),
            CategoryRuleOperator::EQUALS => strtolower($target) === strtolower($value),
            CategoryRuleOperator::STARTS_WITH => str_starts_with(strtolower($target), strtolower($value)),
            CategoryRuleOperator::ENDS_WITH => str_ends_with(strtolower($target), strtolower($value)),
            CategoryRuleOperator::REGEX => preg_match("/{$value}/i", $target) === 1,
        };
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Add computed properties
        $array['rule_type_label'] = $this->rule_type->label();
        $array['rule_operator_label'] = $this->rule_operator->label();
        $array['display_description'] = $this->getDisplayDescription();
        $array['weight_percentage'] = $this->getWeightPercentage();
        $array['formatted_value'] = $this->getFormattedValue();
        
        return $array;
    }
}