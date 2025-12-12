<?php

namespace App\Infrastructure\Http\Controllers\Tenant;

use App\Core\Category\Models\CategoryGroup;
use App\Core\Tenant\Models\Tenant;
use App\Domains\Category\Services\CategoryGroupService;
use App\Infrastructure\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\Tenant\StoreCategoryGroupRequest;
use App\Infrastructure\Http\Requests\Tenant\UpdateCategoryGroupRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryGroupController extends Controller
{
    public function __construct(
        private CategoryGroupService $categoryGroupService
    ) {}

    /**
     * Display a listing of category groups for the current tenant
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = $this->getCurrentTenant();
        
        $groups = $this->categoryGroupService->getAllForTenant(
            $tenant->id,
            $request->get('include_inactive', false)
        );

        return response()->json([
            'data' => $groups,
            'meta' => [
                'total' => $groups->count()
            ]
        ]);
    }

    /**
     * Store a newly created category group
     */
    public function store(StoreCategoryGroupRequest $request): JsonResponse
    {
        $tenant = $this->getCurrentTenant();
        
        $group = $this->categoryGroupService->create(
            $tenant->id,
            $request->validated()
        );

        return response()->json([
            'data' => $group->load('categories'),
            'message' => 'Kategorie-Gruppe erfolgreich erstellt'
        ], 201);
    }

    /**
     * Display the specified category group
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $tenant = $this->getCurrentTenant();
        
        $group = $this->categoryGroupService->findForTenant($tenant->id, $id);

        return response()->json([
            'data' => $group->load('categories')
        ]);
    }

    /**
     * Update the specified category group
     */
    public function update(UpdateCategoryGroupRequest $request, string $id): JsonResponse
    {
        $tenant = $this->getCurrentTenant();
        
        $group = $this->categoryGroupService->update(
            $tenant->id,
            $id,
            $request->validated()
        );

        return response()->json([
            'data' => $group->load('categories'),
            'message' => 'Kategorie-Gruppe erfolgreich aktualisiert'
        ]);
    }

    /**
     * Remove the specified category group
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenant = $this->getCurrentTenant();
        
        $this->categoryGroupService->delete($tenant->id, $id);

        return response()->json([
            'message' => 'Kategorie-Gruppe erfolgreich gelöscht'
        ]);
    }

    /**
     * Toggle active status of a category group
     */
    public function toggle(Request $request, string $id): JsonResponse
    {
        $tenant = $this->getCurrentTenant();
        
        $group = $this->categoryGroupService->toggleActive($tenant->id, $id);

        return response()->json([
            'data' => $group->load('categories'),
            'message' => $group->is_active 
                ? 'Kategorie-Gruppe aktiviert' 
                : 'Kategorie-Gruppe deaktiviert'
        ]);
    }

    /**
     * Reorder category groups
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'groups' => 'required|array',
            'groups.*.id' => 'required|uuid|exists:category_groups,id',
            'groups.*.display_order' => 'required|integer|min:0'
        ]);

        $tenant = $this->getCurrentTenant();
        
        $this->categoryGroupService->reorder(
            $tenant->id,
            $request->input('groups')
        );

        return response()->json([
            'message' => 'Reihenfolge erfolgreich aktualisiert'
        ]);
    }

    protected function getCurrentTenant(): Tenant
    {
        // Get tenant ID from header (set by frontend)
        $tenantId = request()->header('X-Tenant-ID');
        
        if (!$tenantId) {
            abort(400, 'X-Tenant-ID Header fehlt');
        }
        
        $tenant = Tenant::find($tenantId);
        
        if (!$tenant) {
            abort(404, 'Tenant nicht gefunden');
        }
        
        return $tenant;
    }
}