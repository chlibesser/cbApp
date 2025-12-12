<?php

namespace App\Core\Category\Repositories;

use App\Core\Category\Models\Category;
use App\Core\Shared\Repositories\BaseRepository;

class CategoryRepository extends BaseRepository
{
    /**
     * Get the model class for this repository
     */
    protected function getModelClass(): string
    {
        return Category::class;
    }

    /**
     * Find the default category for a group
     */
    public function findDefaultForGroup(string $groupId): ?Category
    {
        return $this->query()
            ->where('category_group_id', $groupId)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Find active categories for a group
     */
    public function findActiveForGroup(string $groupId)
    {
        return $this->query()
            ->where('category_group_id', $groupId)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Search categories by keywords
     */
    public function searchByKeywords(string $groupId, array $keywords)
    {
        $query = $this->query()
            ->where('category_group_id', $groupId)
            ->where('is_active', true);

        foreach ($keywords as $keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'ilike', "%{$keyword}%")
                  ->orWhere('description', 'ilike', "%{$keyword}%")
                  ->orWhereJsonContains('ai_keywords', $keyword);
            });
        }

        return $query->get();
    }
}