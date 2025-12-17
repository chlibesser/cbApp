<?php

namespace App\Domains\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Workflow\Models\Workflow;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WorkflowController extends Controller
{
    /**
     * Display a listing of workflows for the current tenant
     */
    public function index(): JsonResponse
    {
        $tenant = $this->getCurrentTenant();
        
        $workflows = Workflow::where('tenant_id', $tenant->id)
            ->with('creator:id,username')
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform data for frontend
        $transformedWorkflows = $workflows->map(function ($workflow) {
            return [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'description' => $workflow->description,
                'status' => $workflow->status,
                'metadata' => $workflow->metadata,
                'created_at' => $workflow->created_at->toISOString(),
                'updated_at' => $workflow->updated_at->toISOString(),
                'created_by' => $workflow->creator?->username ?? 'Unknown'
            ];
        });

        return response()->json([
            'data' => $transformedWorkflows,
            'total' => $workflows->count()
        ]);
    }

    /**
     * Store a newly created workflow
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'nodes' => 'present|array',
            'edges' => 'present|array',
            'metadata' => 'nullable|array'
        ]);

        $tenant = $this->getCurrentTenant();
        
        $workflow = Workflow::create([
            'id' => Str::uuid(),
            'name' => $request->name,
            'description' => $request->description,
            'tenant_id' => $tenant->id,
            'created_by' => Auth::id(),
            'nodes' => $request->nodes,
            'edges' => $request->edges,
            'metadata' => $request->metadata ?? [],
            'status' => 'draft'
        ]);

        return response()->json([
            'message' => 'Workflow erfolgreich erstellt',
            'data' => $workflow
        ], 201);
    }

    /**
     * Display the specified workflow
     */
    public function show(string $id): JsonResponse
    {
        $tenant = $this->getCurrentTenant();
        
        $workflow = Workflow::where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->with('creator:id,username')
            ->firstOrFail();

        return response()->json([
            'data' => $workflow
        ]);
    }

    /**
     * Update the specified workflow
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'nodes' => 'sometimes|required|array',
            'edges' => 'sometimes|required|array',
            'metadata' => 'nullable|array',
            'status' => 'sometimes|in:draft,active,inactive'
        ]);

        $tenant = $this->getCurrentTenant();
        
        $workflow = Workflow::where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->firstOrFail();

        $workflow->update($request->only([
            'name', 'description', 'nodes', 'edges', 'metadata', 'status'
        ]));

        return response()->json([
            'message' => 'Workflow erfolgreich aktualisiert',
            'data' => $workflow
        ]);
    }

    /**
     * Remove the specified workflow
     */
    public function destroy(string $id): JsonResponse
    {
        $tenant = $this->getCurrentTenant();
        
        $workflow = Workflow::where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->firstOrFail();

        $workflow->delete();

        return response()->json([
            'message' => 'Workflow erfolgreich gelöscht'
        ]);
    }

    /**
     * Execute/run a workflow
     */
    public function execute(string $id): JsonResponse
    {
        $tenant = $this->getCurrentTenant();
        
        $workflow = Workflow::where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->where('status', 'active')
            ->firstOrFail();

        // TODO: Implement workflow execution logic
        // For now, just return success message
        
        return response()->json([
            'message' => "Workflow '{$workflow->name}' wird ausgeführt",
            'execution_id' => Str::uuid()
        ]);
    }

    /**
     * Get the current tenant from request header
     */
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