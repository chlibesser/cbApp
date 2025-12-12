<?php

namespace App\Core\Category\Models;

use App\Core\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends BaseModel
{
    protected $fillable = [
        'category_group_id',
        'tenant_id',
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'is_default',
        'is_active',
        'display_order',
        'ai_positive_description',
        'ai_negative_description',
        'ai_keywords',
        'ai_examples',
        'custom_prompt',
        'requires_approval',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'requires_approval' => 'boolean',
        'ai_keywords' => 'array',
        'ai_examples' => 'array',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
        'display_order' => 'integer',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_default' => false,
        'usage_count' => 0,
        'display_order' => 0,
    ];

    /**
     * Get the category group that owns this category
     */
    public function categoryGroup(): BelongsTo
    {
        return $this->belongsTo(CategoryGroup::class);
    }

    /**
     * Get the group (alias for categoryGroup)
     */
    public function group(): BelongsTo
    {
        return $this->categoryGroup();
    }

    /**
     * Increment usage count and update last used timestamp
     */
    public function recordUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get the full label including group name
     */
    public function getFullLabel(): string
    {
        return $this->categoryGroup->name . ': ' . $this->name;
    }

    /**
     * Check if this category has AI descriptions
     */
    public function hasAiDescriptions(): bool
    {
        return !empty($this->ai_positive_description) || !empty($this->ai_negative_description);
    }
}