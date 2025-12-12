<?php

namespace App\Domains\Category\Models;

use App\Core\Category\Enums\SelectionType;
use App\Core\Shared\Models\BaseModel;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CategoryGroup extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'selection_type',
        'is_required',
        'ai_enabled',
        'ai_prompt_context',
        'ai_confidence_threshold',
        'display_order',
        'icon',
        'color',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'selection_type' => SelectionType::class,
        'is_required' => 'boolean',
        'ai_enabled' => 'boolean',
        'ai_confidence_threshold' => 'float',
        'display_order' => 'integer',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    protected $attributes = [
        'selection_type' => 'single',
        'is_required' => false,
        'ai_enabled' => true,
        'ai_confidence_threshold' => 0.7,
        'display_order' => 0,
        'is_active' => true,
        'is_system' => false,
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('display_order');
    }

    public function activeCategories(): HasMany
    {
        return $this->categories()->where('is_active', true);
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

    public function scopeOrderedForDisplay(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    public function scopeAiEnabled(Builder $query): Builder
    {
        return $query->where('ai_enabled', true);
    }

    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_required', true);
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

    // Methods
    public function isSingleSelect(): bool
    {
        return $this->selection_type === SelectionType::SINGLE;
    }

    public function isMultiSelect(): bool
    {
        return $this->selection_type === SelectionType::MULTI;
    }

    public function getCategoriesCount(): int
    {
        return $this->categories()->count();
    }

    public function getActiveCategoriesCount(): int
    {
        return $this->activeCategories()->count();
    }

    public function getDefaultCategory(): ?Category
    {
        return $this->categories()
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
    }

    public function hasAiCapabilities(): bool
    {
        return $this->ai_enabled && $this->getActiveCategoriesCount() > 0;
    }

    public function canBeDeleted(): bool
    {
        return !$this->is_system;
    }

    public function getDisplayName(): string
    {
        return $this->name;
    }

    public function getDisplayIcon(): string
    {
        return $this->icon ?? 'mdi-tag-multiple';
    }

    public function getDisplayColor(): string
    {
        return $this->color ?? 'primary';
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Add computed properties
        $array['categories_count'] = $this->getCategoriesCount();
        $array['active_categories_count'] = $this->getActiveCategoriesCount();
        $array['can_be_deleted'] = $this->canBeDeleted();
        $array['has_ai_capabilities'] = $this->hasAiCapabilities();
        $array['selection_type_label'] = $this->selection_type->label();
        
        return $array;
    }
}