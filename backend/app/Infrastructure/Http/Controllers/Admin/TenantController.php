<?php

namespace App\Infrastructure\Http\Controllers\Admin;

use App\Core\Tenant\Models\Tenant;
use App\Core\Tenant\Services\TenantService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * TenantController - Admin tenant management
 */
class TenantController extends Controller
{
    public function __construct(
        private TenantService $tenantService
    ) {}

    /**
     * List all tenants
     */
    public function index()
    {
        $tenants = Tenant::with('profiles')->paginate(20);

        return response()->json($tenants);
    }

    /**
     * Create a new tenant
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'settings' => ['array'],
        ]);

        $tenant = $this->tenantService->create($data);

        return response()->json([
            'message' => 'Tenant created successfully',
            'tenant' => $tenant,
        ], 201);
    }

    /**
     * Update a tenant
     */
    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'name' => ['string', 'max:255'],
            'settings' => ['array'],
            'is_active' => ['boolean'],
        ]);

        $tenant->update($data);

        return response()->json([
            'message' => 'Tenant updated successfully',
            'tenant' => $tenant,
        ]);
    }

    /**
     * Delete a tenant
     */
    public function destroy(Tenant $tenant)
    {
        $tenant->delete();

        return response()->json([
            'message' => 'Tenant deleted successfully',
        ]);
    }
}