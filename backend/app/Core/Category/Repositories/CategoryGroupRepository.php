<?php

namespace App\Core\Category\Repositories;

use App\Core\Category\Models\CategoryGroup;
use App\Core\Shared\Repositories\BaseRepository;

class CategoryGroupRepository extends BaseRepository
{
    /**
     * Get the model class for this repository
     */
    protected function getModelClass(): string
    {
        return CategoryGroup::class;
    }

    /**
     * Find all active groups for a tenant with categories
     */
    public function findActiveWithCategories(string $tenantId)
    {
        return $this->query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['categories' => function ($query) {
                $query->where('is_active', true)
                      ->orderBy('display_order')
                      ->orderBy('name');
            }])
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Find groups by AI status
     */
    public function findByAiStatus(string $tenantId, bool $aiEnabled)
    {
        return $this->query()
            ->where('tenant_id', $tenantId)
            ->where('ai_enabled', $aiEnabled)
            ->where('is_active', true)
            ->get();
    }
}