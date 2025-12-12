<?php

namespace App\Domains\Category\Models;

use App\Core\Shared\Models\BaseModel;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Category extends BaseModel
{
    protected $fillable = [
        'category_group_id',
        'tenant_id',
        'name',
        'slug',
        'description',
        'ai_positive_description',
        'ai_negative_description',
        'ai_keywords',
        'ai_examples',
        'is_default',
        'requires_approval',
        'display_order',
        'icon',
        'color',
        'is_active',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'ai_keywords' => 'array',
        'ai_examples' => 'array',
        'is_default' => 'boolean',
        'requires_approval' => 'boolean',
        'display_order' => 'integer',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    protected $attributes = [
        'is_default' => false,
        'requires_approval' => false,
        'display_order' => 0,
        'is_active' => true,
        'usage_count' => 0,
    ];

    // Relationships
    public function categoryGroup(): BelongsTo
    {
        return $this->belongsTo(CategoryGroup::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(CategoryRule::class);
    }

    public function activeRules(): HasMany
    {
        return $this->rules()->where('is_active', true);
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

    public function scopeForGroup(Builder $query, string $groupId): Builder
    {
        return $query->where('category_group_id', $groupId);
    }

    public function scopeOrderedForDisplay(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeRequiresApproval(Builder $query): Builder
    {
        return $query->where('requires_approval', true);
    }

    public function scopeRecentlyUsed(Builder $query, int $days = 30): Builder
    {
        return $query->where('last_used_at', '>=', now()->subDays($days));
    }

    public function scopeMostUsed(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    // Accessors & Mutators
    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value;
        
        // Auto-generate slug if not set
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    public function getAiKeywordsAttribute($value): array
    {
        if (is_null($value)) {
            return [];
        }
        
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        
        return $value;
    }

    public function getAiExamplesAttribute($value): array
    {
        if (is_null($value)) {
            return [];
        }
        
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        
        return $value;
    }

    // Methods
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    public function requiresApproval(): bool
    {
        return $this->requires_approval;
    }

    public function hasAiDescription(): bool
    {
        return !empty($this->ai_positive_description);
    }

    public function hasKeywords(): bool
    {
        return !empty($this->ai_keywords);
    }

    public function hasExamples(): bool
    {
        return !empty($this->ai_examples);
    }

    public function hasRules(): bool
    {
        return $this->activeRules()->count() > 0;
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    public function getDisplayName(): string
    {
        return $this->name;
    }

    public function getDisplayIcon(): string
    {
        return $this->icon ?? 'mdi-tag';
    }

    public function getDisplayColor(): string
    {
        return $this->color ?? 'grey';
    }

    public function getKeywordsString(): string
    {
        return implode(', ', $this->ai_keywords ?? []);
    }

    public function getExamplesString(): string
    {
        return implode(', ', $this->ai_examples ?? []);
    }

    public function getUsageFrequency(): string
    {
        if ($this->usage_count === 0) {
            return 'Nie verwendet';
        }
        
        if ($this->usage_count === 1) {
            return '1 mal verwendet';
        }
        
        return "{$this->usage_count} mal verwendet";
    }

    public function getLastUsedFormatted(): string
    {
        if (!$this->last_used_at) {
            return 'Nie verwendet';
        }
        
        return $this->last_used_at->format('d.m.Y H:i');
    }

    public function getLastUsedRelative(): string
    {
        if (!$this->last_used_at) {
            return 'Nie verwendet';
        }
        
        return $this->last_used_at->diffForHumans();
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Add computed properties
        $array['has_ai_description'] = $this->hasAiDescription();
        $array['has_keywords'] = $this->hasKeywords();
        $array['has_examples'] = $this->hasExamples();
        $array['has_rules'] = $this->hasRules();
        $array['keywords_string'] = $this->getKeywordsString();
        $array['examples_string'] = $this->getExamplesString();
        $array['usage_frequency'] = $this->getUsageFrequency();
        $array['last_used_formatted'] = $this->getLastUsedFormatted();
        $array['last_used_relative'] = $this->getLastUsedRelative();
        
        return $array;
    }
}