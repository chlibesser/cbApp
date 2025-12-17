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
            'nodes' => 'sometimes|present|array',
            'edges' => 'sometimes|present|array',
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
            ->firstOrFail();

        $executionId = Str::uuid();
        
        try {
            // Process workflow nodes
            $results = $this->processWorkflowNodes($workflow->nodes, $workflow->edges, $executionId);
            
            return response()->json([
                'message' => "Workflow '{$workflow->name}' erfolgreich ausgeführt",
                'execution_id' => $executionId,
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => "Workflow '{$workflow->name}' fehlgeschlagen: " . $e->getMessage(),
                'execution_id' => $executionId,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Process workflow nodes
     */
    private function processWorkflowNodes(array $nodes, array $edges, string $executionId): array
    {
        $results = [];
        
        // Find the trigger node (starting point)
        $triggerNode = collect($nodes)->firstWhere('type', 'trigger');
        
        if (!$triggerNode) {
            throw new \Exception('Kein Trigger-Node gefunden');
        }
        
        $results[] = [
            'node_id' => $triggerNode['id'],
            'type' => 'trigger',
            'status' => 'success',
            'message' => 'Trigger ausgeführt: ' . ($triggerNode['data']['triggerType'] ?? 'manual')
        ];
        
        // Process connected nodes
        $this->processConnectedNodes($triggerNode['id'], $nodes, $edges, $results, $executionId);
        
        return $results;
    }
    
    /**
     * Process nodes connected to a given node
     */
    private function processConnectedNodes(string $nodeId, array $nodes, array $edges, array &$results, string $executionId): void
    {
        // Find edges starting from this node
        $outgoingEdges = collect($edges)->where('source', $nodeId);
        
        foreach ($outgoingEdges as $edge) {
            $targetNodeId = $edge['target'];
            $targetNode = collect($nodes)->firstWhere('id', $targetNodeId);
            
            if (!$targetNode) continue;
            
            // Execute the target node
            try {
                $nodeResult = $this->executeNode($targetNode, $executionId);
                $results[] = $nodeResult;
                
                // Continue processing if node succeeded
                if ($nodeResult['status'] === 'success') {
                    $this->processConnectedNodes($targetNodeId, $nodes, $edges, $results, $executionId);
                }
                
            } catch (\Exception $e) {
                $results[] = [
                    'node_id' => $targetNode['id'],
                    'type' => $targetNode['type'],
                    'status' => 'error',
                    'message' => 'Fehler: ' . $e->getMessage()
                ];
                // Stop execution on error
                break;
            }
        }
    }
    
    /**
     * Execute a specific node based on its type
     */
    private function executeNode(array $node, string $executionId): array
    {
        $nodeType = $node['type'];
        $nodeData = $node['data'];
        
        switch ($nodeType) {
            case 'action':
                return $this->executeActionNode($node, $executionId);
            case 'condition':
                return $this->executeConditionNode($node, $executionId);
            case 'end':
                return $this->executeEndNode($node, $executionId);
            default:
                return [
                    'node_id' => $node['id'],
                    'type' => $nodeType,
                    'status' => 'skipped',
                    'message' => "Unbekannter Node-Typ: {$nodeType}"
                ];
        }
    }
    
    /**
     * Execute an action node
     */
    private function executeActionNode(array $node, string $executionId): array
    {
        $data = $node['data'];
        $actionType = $data['actionType'] ?? 'api';
        
        switch ($actionType) {
            case 'api':
                return $this->executeApiCall($node, $executionId);
            case 'email':
                return $this->executeEmailAction($node, $executionId);
            case 'database':
                return $this->executeDatabaseAction($node, $executionId);
            default:
                return [
                    'node_id' => $node['id'],
                    'type' => 'action',
                    'action_type' => $actionType,
                    'status' => 'success',
                    'message' => "Action ausgeführt: {$actionType}"
                ];
        }
    }
    
    /**
     * Execute API call action
     */
    private function executeApiCall(array $node, string $executionId): array
    {
        $data = $node['data'];
        $method = strtoupper($data['method'] ?? 'GET');
        $endpoint = $data['endpoint'] ?? '';
        $timeout = $data['timeout'] ?? 30;
        
        if (empty($endpoint)) {
            throw new \Exception('Kein API-Endpoint konfiguriert');
        }
        
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => $timeout,
                'verify' => false // For development - should be true in production
            ]);
            
            $options = [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'cbApp-Workflow/' . $executionId
                ]
            ];
            
            // Add request body for POST/PUT/PATCH requests
            if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $options['json'] = $data['parameters'] ?? [];
            }
            
            $response = $client->request($method, $endpoint, $options);
            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();
            
            return [
                'node_id' => $node['id'],
                'type' => 'action',
                'action_type' => 'api',
                'status' => $statusCode >= 200 && $statusCode < 300 ? 'success' : 'error',
                'message' => "API-Aufruf erfolgreich: {$method} {$endpoint} (Status: {$statusCode})",
                'details' => [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status_code' => $statusCode,
                    'response' => json_decode($responseBody, true) ?: $responseBody
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'node_id' => $node['id'],
                'type' => 'action',
                'action_type' => 'api',
                'status' => 'error',
                'message' => "API-Aufruf fehlgeschlagen: " . $e->getMessage(),
                'details' => [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage()
                ]
            ];
        }
    }
    
    /**
     * Execute email action (placeholder)
     */
    private function executeEmailAction(array $node, string $executionId): array
    {
        $data = $node['data'];
        $to = $data['parameters']['to'] ?? '';
        $subject = $data['parameters']['subject'] ?? '';
        
        return [
            'node_id' => $node['id'],
            'type' => 'action',
            'action_type' => 'email',
            'status' => 'success',
            'message' => "E-Mail gesendet an: {$to} (Betreff: {$subject})",
            'details' => [
                'to' => $to,
                'subject' => $subject,
                'note' => 'E-Mail-Versendung ist noch nicht implementiert'
            ]
        ];
    }
    
    /**
     * Execute database action (placeholder)
     */
    private function executeDatabaseAction(array $node, string $executionId): array
    {
        $data = $node['data'];
        $operation = $data['parameters']['operation'] ?? '';
        $table = $data['parameters']['table'] ?? '';
        
        return [
            'node_id' => $node['id'],
            'type' => 'action',
            'action_type' => 'database',
            'status' => 'success',
            'message' => "Datenbank-Operation: {$operation} auf Tabelle {$table}",
            'details' => [
                'operation' => $operation,
                'table' => $table,
                'note' => 'Datenbank-Operationen sind noch nicht implementiert'
            ]
        ];
    }
    
    /**
     * Execute condition node
     */
    private function executeConditionNode(array $node, string $executionId): array
    {
        return [
            'node_id' => $node['id'],
            'type' => 'condition',
            'status' => 'success',
            'message' => 'Bedingung ausgewertet (Implementierung folgt)'
        ];
    }
    
    /**
     * Execute end node
     */
    private function executeEndNode(array $node, string $executionId): array
    {
        $data = $node['data'];
        $endType = $data['endType'] ?? 'success';
        $message = $data['message'] ?? 'Workflow beendet';
        
        return [
            'node_id' => $node['id'],
            'type' => 'end',
            'status' => 'success',
            'message' => "Workflow beendet: {$message} (Typ: {$endType})"
        ];
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