<?php

namespace App\Infrastructure\Http\Controllers\Tenant;

use App\Core\Category\Models\Category;
use App\Core\Tenant\Models\Tenant;
use App\Domains\Category\Services\CategoryService;
use App\Infrastructure\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\Tenant\StoreCategoryRequest;
use App\Infrastructure\Http\Requests\Tenant\UpdateCategoryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categoryService
    ) {}

    /**
     * Display a listing of categories for a specific group
     */
    public function index(Request $request, string $categoryGroup): JsonResponse
    {
        $tenant = $this->getCurrentTenant();
        
        $categories = $this->categoryService->getAllForGroup(
            $tenant->id,
            $categoryGroup,
            $request->get('include_inactive', false)
        );

        return response()->json([
            'data' => $categories,
            'meta' => [
                'total' => $categories->count()
            ]
        ]);
    }

    /**
     * Store a newly created category
     */
    public function store(StoreCategoryRequest $request, string $categoryGroup): JsonResponse
    {
        $tenant = $this->getCurrentTenant();
        
        $category = $this->categoryService->create(
            $tenant->id,
            $categoryGroup,
            $request->validated()
        );

        return response()->json([
            'data' => $category,
            'message' => 'Kategorie erfolgreich erstellt'
        ], 201);
    }

    /**
     * Display the specified category
     */
    public function show(Request $request, string $categoryGroup, string $category): JsonResponse
    {
        $tenant = $this->getCurrentTenant();
        
        $category = $this->categoryService->findForGroup($tenant->id, $categoryGroup, $category);

        return response()->json([
            'data' => $category
        ]);
    }

    /**
     * Update the specified category
     */
    public function update(UpdateCategoryRequest $request, string $categoryGroup, string $category): JsonResponse
    {
        $tenant = $this->getCurrentTenant();
        
        $category = $this->categoryService->update(
            $tenant->id,
            $categoryGroup,
            $category,
            $request->validated()
        );

        return response()->json([
            'data' => $category,
            'message' => 'Kategorie erfolgreich aktualisiert'
        ]);
    }

    /**
     * Remove the specified category
     */
    public function destroy(Request $request, string $categoryGroup, string $category): JsonResponse
    {
        $tenant = $this->getCurrentTenant();
        
        $this->categoryService->delete($tenant->id, $categoryGroup, $category);

        return response()->json([
            'message' => 'Kategorie erfolgreich gelöscht'
        ]);
    }

    /**
     * Toggle active status of a category
     */
    public function toggle(Request $request, string $categoryGroup, string $category): JsonResponse
    {
        $tenant = $this->getCurrentTenant();
        
        $category = $this->categoryService->toggleActive($tenant->id, $categoryGroup, $category);

        return response()->json([
            'data' => $category,
            'message' => $category->is_active 
                ? 'Kategorie aktiviert' 
                : 'Kategorie deaktiviert'
        ]);
    }

    /**
     * Set a category as default for the group
     */
    public function setDefault(Request $request, string $categoryGroup, string $category): JsonResponse
    {
        $tenant = $this->getCurrentTenant();
        
        $category = $this->categoryService->setAsDefault($tenant->id, $categoryGroup, $category);

        return response()->json([
            'data' => $category,
            'message' => 'Standard-Kategorie festgelegt'
        ]);
    }

    /**
     * Reorder categories within a group
     */
    public function reorder(Request $request, string $categoryGroup): JsonResponse
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|uuid|exists:categories,id',
            'categories.*.display_order' => 'required|integer|min:0'
        ]);

        $tenant = $this->getCurrentTenant();
        
        $this->categoryService->reorder(
            $tenant->id,
            $categoryGroup,
            $request->input('categories')
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