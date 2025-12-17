<?php

namespace App\Domains\Partner\Models;

use App\Core\Shared\Models\BaseModel;
use App\Core\Category\Models\Category;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'website',
        'email',
        'phone',
        'status',
        'tags',
        'custom_fields',
        'notes',
    ];

    protected $casts = [
        'tags' => 'array',
        'custom_fields' => 'array',
    ];

    protected $attributes = [
        'status' => 'active',
        'tags' => '[]',
        'custom_fields' => '{}',
    ];

    /**
     * Get the category that the partner belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the contacts for the partner.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(PartnerContact::class);
    }

    /**
     * Get the interactions for the partner.
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(PartnerInteraction::class)->orderBy('interaction_date', 'desc');
    }

    /**
     * Get the primary contact for the partner.
     */
    public function primaryContact()
    {
        return $this->contacts()->where('is_primary', true)->first();
    }

    /**
     * Scope a query to only include active partners.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * Scope a query to search partners.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('notes', 'like', "%{$search}%");
        });
    }

}