<?php

namespace App\Domains\Workflow\Listeners;

use App\Domains\Document\Events\DocumentUploaded;
use App\Domains\Workflow\Models\Workflow;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WorkflowTriggerListener
{
    /**
     * Handle the document uploaded event
     */
    public function handle(DocumentUploaded $event): void
    {
        Log::info('Processing document upload for workflow triggers', [
            'document_id' => $event->document->id,
            'filename' => $event->document->original_filename,
            'tenant_id' => $event->document->tenant_id
        ]);

        // Debug: List all workflows found
        $workflows = Workflow::where('tenant_id', $event->document->tenant_id)
            ->where('status', 'active')
            ->get();
            
        Log::info('Found workflows for trigger evaluation', [
            'tenant_id' => $event->document->tenant_id,
            'workflow_count' => $workflows->count(),
            'workflows' => $workflows->pluck('name', 'id')->toArray()
        ]);

        try {
            // Find all active workflows for this tenant
            $workflows = Workflow::where('tenant_id', $event->document->tenant_id)
                ->where('status', 'active')
                ->get();

            foreach ($workflows as $workflow) {
                Log::info('Evaluating workflow for trigger', [
                    'workflow_id' => $workflow->id,
                    'workflow_name' => $workflow->name,
                    'document_filename' => $event->document->original_filename
                ]);
                
                if ($this->shouldTriggerWorkflow($workflow, $event)) {
                    Log::info('Workflow trigger conditions met, executing...', [
                        'workflow_id' => $workflow->id,
                        'workflow_name' => $workflow->name
                    ]);
                    $this->executeWorkflow($workflow, $event);
                } else {
                    Log::info('Workflow trigger conditions not met', [
                        'workflow_id' => $workflow->id,
                        'workflow_name' => $workflow->name
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing workflow triggers for document upload', [
                'document_id' => $event->document->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Check if workflow should be triggered by this document upload
     */
    private function shouldTriggerWorkflow(Workflow $workflow, DocumentUploaded $event): bool
    {
        $nodes = $workflow->nodes;
        
        // Find trigger nodes with file upload types
        foreach ($nodes as $node) {
            if ($node['type'] === 'trigger' && 
                isset($node['data']['triggerType']) && 
                in_array($node['data']['triggerType'], ['Datei-Upload', 'file', 'document-upload'])) {
                
                // Check if event types match
                $eventTypes = $node['data']['eventTypes'] ?? '';
                
                if (empty($eventTypes) || $eventTypes === 'none') {
                    // No specific pattern - trigger for all uploads
                    Log::info('Workflow trigger matched (no pattern)', [
                        'workflow_id' => $workflow->id,
                        'workflow_name' => $workflow->name,
                        'filename' => $event->document->original_filename,
                        'trigger_type' => $node['data']['triggerType']
                    ]);
                    return true;
                }
                
                // Split patterns by comma and check each
                $patterns = array_map('trim', explode(',', $eventTypes));
                
                foreach ($patterns as $pattern) {
                    if ($event->matchesPattern($pattern)) {
                        Log::info('Workflow trigger matched', [
                            'workflow_id' => $workflow->id,
                            'workflow_name' => $workflow->name,
                            'pattern' => $pattern,
                            'filename' => $event->document->original_filename
                        ]);
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Execute the workflow
     */
    private function executeWorkflow(Workflow $workflow, DocumentUploaded $event): void
    {
        try {
            $executionId = Str::uuid();
            
            Log::info('Auto-executing workflow from document upload', [
                'workflow_id' => $workflow->id,
                'workflow_name' => $workflow->name,
                'document_id' => $event->document->id,
                'execution_id' => $executionId,
                'trigger_type' => 'document_upload'
            ]);

            // Create context for workflow execution
            $context = [
                'trigger_document' => [
                    'id' => $event->document->id,
                    'filename' => $event->document->original_filename,
                    'mime_type' => $event->document->mime_type,
                    'size' => $event->document->file_size,
                    'uploader_id' => $event->uploader->id
                ],
                'execution_type' => 'auto_trigger',
                'trigger_event' => 'document_uploaded'
            ];

            // Execute workflow with context
            $this->processWorkflowNodes(
                $workflow->nodes, 
                $workflow->edges, 
                $executionId, 
                $context
            );
            
        } catch (\Exception $e) {
            Log::error('Failed to execute workflow from document upload trigger', [
                'workflow_id' => $workflow->id,
                'document_id' => $event->document->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process workflow nodes (simplified version of WorkflowController logic)
     */
    private function processWorkflowNodes(array $nodes, array $edges, string $executionId, array $context = []): void
    {
        // Find the trigger node (starting point)
        $triggerNode = collect($nodes)->firstWhere('type', 'trigger');
        
        if (!$triggerNode) {
            throw new \Exception('Kein Trigger-Node gefunden');
        }
        
        Log::info('Auto-workflow trigger executed', [
            'execution_id' => $executionId,
            'trigger_type' => $triggerNode['data']['triggerType'] ?? 'unknown',
            'context' => $context
        ]);
        
        // Process connected nodes
        $this->processConnectedNodes($triggerNode['id'], $nodes, $edges, $executionId, $context);
    }

    /**
     * Process nodes connected to a given node
     */
    private function processConnectedNodes(string $nodeId, array $nodes, array $edges, string $executionId, array $context = []): void
    {
        // Find edges starting from this node
        $outgoingEdges = collect($edges)->where('source', $nodeId);
        
        foreach ($outgoingEdges as $edge) {
            $targetNodeId = $edge['target'];
            $targetNode = collect($nodes)->firstWhere('id', $targetNodeId);
            
            if (!$targetNode) continue;
            
            try {
                $this->executeNode($targetNode, $executionId, $context);
                
                // Continue processing connected nodes
                $this->processConnectedNodes($targetNodeId, $nodes, $edges, $executionId, $context);
                
            } catch (\Exception $e) {
                Log::error('Auto-workflow node execution failed', [
                    'node_id' => $targetNode['id'],
                    'node_type' => $targetNode['type'],
                    'execution_id' => $executionId,
                    'error' => $e->getMessage()
                ]);
                // Stop execution on error
                break;
            }
        }
    }

    /**
     * Execute a specific node
     */
    private function executeNode(array $node, string $executionId, array $context = []): void
    {
        $nodeType = $node['type'];
        
        switch ($nodeType) {
            case 'action':
                $this->executeActionNode($node, $executionId, $context);
                break;
            case 'condition':
                // Skip for now - conditions need more complex logic
                Log::info('Skipping condition node in auto-execution', [
                    'node_id' => $node['id']
                ]);
                break;
            case 'end':
                Log::info('Auto-workflow completed', [
                    'execution_id' => $executionId,
                    'end_type' => $node['data']['endType'] ?? 'success'
                ]);
                break;
            default:
                Log::info('Unknown node type in auto-execution', [
                    'node_id' => $node['id'],
                    'node_type' => $nodeType
                ]);
        }
    }

    /**
     * Execute an action node (simplified version)
     */
    private function executeActionNode(array $node, string $executionId, array $context = []): void
    {
        $data = $node['data'];
        $actionType = $data['actionType'] ?? 'api';
        
        switch ($actionType) {
            case 'api':
                $this->executeApiCall($node, $executionId, $context);
                break;
            default:
                Log::info('Action executed in auto-workflow', [
                    'node_id' => $node['id'],
                    'action_type' => $actionType,
                    'execution_id' => $executionId
                ]);
        }
    }

    /**
     * Execute API call action (simplified version)
     */
    private function executeApiCall(array $node, string $executionId, array $context = []): void
    {
        $data = $node['data'];
        $method = strtoupper($data['method'] ?? 'GET');
        $endpoint = $data['endpoint'] ?? '';
        $timeout = $data['timeout'] ?? 30;
        
        if (empty($endpoint)) {
            Log::warning('API call skipped - no endpoint configured', [
                'node_id' => $node['id'],
                'execution_id' => $executionId
            ]);
            return;
        }
        
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => $timeout,
                'verify' => false
            ]);
            
            $options = [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'cbApp-AutoWorkflow/' . $executionId
                ]
            ];
            
            // Add request body with context for POST/PUT/PATCH requests
            if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $parameters = $data['parameters'] ?? [];
                // Merge context data into parameters
                $options['json'] = array_merge($parameters, [
                    'workflow_context' => $context
                ]);
            }
            
            $response = $client->request($method, $endpoint, $options);
            $statusCode = $response->getStatusCode();
            
            Log::info('Auto-workflow API call executed', [
                'node_id' => $node['id'],
                'method' => $method,
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
                'execution_id' => $executionId
            ]);
            
        } catch (\Exception $e) {
            Log::error('Auto-workflow API call failed', [
                'node_id' => $node['id'],
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'execution_id' => $executionId
            ]);
        }
    }
}