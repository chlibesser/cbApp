<?php

namespace App\Domains\Category\Services;

use App\Core\Category\Models\CategoryGroup;
use App\Core\Category\Repositories\CategoryGroupRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CategoryGroupService
{
    public function __construct(
        private CategoryGroupRepository $repository
    ) {}

    /**
     * Get all category groups for a tenant
     */
    public function getAllForTenant(string $tenantId, bool $includeInactive = false): Collection
    {
        $query = $this->repository->query()
            ->where('tenant_id', $tenantId)
            ->with(['categories' => function ($q) use ($includeInactive) {
                if (!$includeInactive) {
                    $q->where('is_active', true);
                }
                $q->orderBy('display_order')->orderBy('name');
            }])
            ->orderBy('display_order')
            ->orderBy('name');

        if (!$includeInactive) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * Find a specific category group for a tenant
     */
    public function findForTenant(string $tenantId, string $id): CategoryGroup
    {
        return $this->repository->query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->with('categories')
            ->firstOrFail();
    }

    /**
     * Create a new category group
     */
    public function create(string $tenantId, array $data): CategoryGroup
    {
        $data['tenant_id'] = $tenantId;
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        
        // Get next display order
        $maxOrder = $this->repository->query()
            ->where('tenant_id', $tenantId)
            ->max('display_order') ?? -1;
        
        $data['display_order'] = $maxOrder + 1;

        return $this->repository->create($data);
    }

    /**
     * Update a category group
     */
    public function update(string $tenantId, string $id, array $data): CategoryGroup
    {
        $group = $this->findForTenant($tenantId, $id);
        
        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        
        $group->update($data);
        
        return $group->fresh('categories');
    }

    /**
     * Delete a category group
     */
    public function delete(string $tenantId, string $id): bool
    {
        $group = $this->findForTenant($tenantId, $id);
        
        // Check if group has categories
        if ($group->categories()->exists()) {
            throw new \Exception('Kategorie-Gruppe kann nicht gelöscht werden, da sie noch Kategorien enthält.');
        }
        
        return $group->delete();
    }

    /**
     * Toggle active status
     */
    public function toggleActive(string $tenantId, string $id): CategoryGroup
    {
        $group = $this->findForTenant($tenantId, $id);
        
        $group->update([
            'is_active' => !$group->is_active
        ]);
        
        return $group->fresh('categories');
    }

    /**
     * Reorder category groups
     */
    public function reorder(string $tenantId, array $groups): void
    {
        foreach ($groups as $groupData) {
            $this->repository->query()
                ->where('tenant_id', $tenantId)
                ->where('id', $groupData['id'])
                ->update(['display_order' => $groupData['display_order']]);
        }
    }
}