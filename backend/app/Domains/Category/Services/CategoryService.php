<?php

namespace App\Domains\Category\Services;

use App\Core\Category\Models\Category;
use App\Core\Category\Models\CategoryGroup;
use App\Core\Category\Repositories\CategoryRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CategoryService
{
    public function __construct(
        private CategoryRepository $repository,
        private CategoryGroupService $groupService
    ) {}

    /**
     * Get all categories for a specific group
     */
    public function getAllForGroup(string $tenantId, string $groupId, bool $includeInactive = false): Collection
    {
        // Verify group belongs to tenant
        $this->groupService->findForTenant($tenantId, $groupId);
        
        $query = $this->repository->query()
            ->where('category_group_id', $groupId)
            ->orderBy('display_order')
            ->orderBy('name');

        if (!$includeInactive) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * Find a specific category for a group
     */
    public function findForGroup(string $tenantId, string $groupId, string $id): Category
    {
        // Verify group belongs to tenant
        $this->groupService->findForTenant($tenantId, $groupId);
        
        return $this->repository->query()
            ->where('category_group_id', $groupId)
            ->where('id', $id)
            ->firstOrFail();
    }

    /**
     * Create a new category
     */
    public function create(string $tenantId, string $groupId, array $data): Category
    {
        // Verify group belongs to tenant and get it
        $group = $this->groupService->findForTenant($tenantId, $groupId);
        
        $data['category_group_id'] = $groupId;
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        
        // Get next display order
        $maxOrder = $this->repository->query()
            ->where('category_group_id', $groupId)
            ->max('display_order') ?? -1;
        
        $data['display_order'] = $maxOrder + 1;
        
        // Handle AI fields
        if (isset($data['ai_keywords']) && is_string($data['ai_keywords'])) {
            $data['ai_keywords'] = array_map('trim', explode(',', $data['ai_keywords']));
        }

        return $this->repository->create($data);
    }

    /**
     * Update a category
     */
    public function update(string $tenantId, string $groupId, string $id, array $data): Category
    {
        $category = $this->findForGroup($tenantId, $groupId, $id);
        
        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        
        // Handle AI fields
        if (isset($data['ai_keywords']) && is_string($data['ai_keywords'])) {
            $data['ai_keywords'] = array_map('trim', explode(',', $data['ai_keywords']));
        }
        
        $category->update($data);
        
        return $category->fresh();
    }

    /**
     * Delete a category
     */
    public function delete(string $tenantId, string $groupId, string $id): bool
    {
        $category = $this->findForGroup($tenantId, $groupId, $id);
        
        // Check if it's the default category
        if ($category->is_default) {
            throw new \Exception('Standard-Kategorie kann nicht gelöscht werden.');
        }
        
        return $category->delete();
    }

    /**
     * Toggle active status
     */
    public function toggleActive(string $tenantId, string $groupId, string $id): Category
    {
        $category = $this->findForGroup($tenantId, $groupId, $id);
        
        // Don't deactivate default category
        if ($category->is_default && $category->is_active) {
            throw new \Exception('Standard-Kategorie kann nicht deaktiviert werden.');
        }
        
        $category->update([
            'is_active' => !$category->is_active
        ]);
        
        return $category->fresh();
    }

    /**
     * Set a category as default for the group
     */
    public function setAsDefault(string $tenantId, string $groupId, string $id): Category
    {
        $category = $this->findForGroup($tenantId, $groupId, $id);
        
        // Remove default from other categories in the group
        $this->repository->query()
            ->where('category_group_id', $groupId)
            ->where('id', '!=', $id)
            ->update(['is_default' => false]);
        
        // Set this category as default and ensure it's active
        $category->update([
            'is_default' => true,
            'is_active' => true
        ]);
        
        return $category->fresh();
    }

    /**
     * Reorder categories within a group
     */
    public function reorder(string $tenantId, string $groupId, array $categories): void
    {
        // Verify group belongs to tenant
        $this->groupService->findForTenant($tenantId, $groupId);
        
        foreach ($categories as $categoryData) {
            $this->repository->query()
                ->where('category_group_id', $groupId)
                ->where('id', $categoryData['id'])
                ->update(['display_order' => $categoryData['display_order']]);
        }
    }
}