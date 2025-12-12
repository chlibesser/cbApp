<?php

namespace App\Core\Category\Models;

use App\Core\Category\Enums\SelectionType;
use App\Core\Shared\Models\BaseModel;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryGroup extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'selection_type',
        'is_required',
        'is_active',
        'display_order',
        'ai_enabled',
        'ai_prompt_context',
        'ai_confidence_threshold',
    ];

    protected $casts = [
        'selection_type' => SelectionType::class,
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'ai_enabled' => 'boolean',
        'ai_confidence_threshold' => 'float',
        'display_order' => 'integer',
    ];

    protected $attributes = [
        'is_active' => true,
        'ai_enabled' => true,
        'ai_confidence_threshold' => 0.7,
        'display_order' => 0,
    ];

    /**
     * Get the tenant that owns the category group
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the categories for this group
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)
            ->orderBy('display_order')
            ->orderBy('name');
    }

    /**
     * Get active categories
     */
    public function activeCategories(): HasMany
    {
        return $this->categories()->where('is_active', true);
    }

    /**
     * Check if this is a single selection group
     */
    public function isSingleSelection(): bool
    {
        return $this->selection_type === SelectionType::SINGLE;
    }

    /**
     * Check if this is a multi selection group
     */
    public function isMultiSelection(): bool
    {
        return $this->selection_type === SelectionType::MULTI;
    }
}