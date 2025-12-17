<?php

namespace App\Infrastructure\Http\Controllers\Tenant;

use App\Infrastructure\Http\Controllers\Controller;
use App\Domains\Document\Services\DocumentService;
use App\Domains\Document\Services\DocumentAIService;
use App\Domains\Document\Services\DocumentSignedUrlService;
use App\Domains\AI\Jobs\ProcessDocumentWithAI;
use App\Core\Document\Models\Document;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documentService,
        private DocumentSignedUrlService $signedUrlService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = $this->getCurrentTenant();
        
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'mime_type' => 'nullable|string',
            'visibility' => 'nullable|in:public,internal,confidential,restricted',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'uploaded_by' => 'nullable|uuid|exists:accounts,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = $request->only([
            'mime_type', 'visibility', 'category_id', 
            'uploaded_by', 'date_from', 'date_to'
        ]);

        $documents = $this->documentService->searchDocuments(
            $tenant,
            $request->get('search'),
            $filters
        );

        // Paginate results if requested
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);
        
        $total = $documents->count();
        $items = $documents->forPage($page, $perPage)->values();

        return response()->json([
            'data' => $items->load(['uploader', 'categories.categoryGroup']),
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB max
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'visibility' => 'nullable|in:public,internal,confidential,restricted',
            'expires_at' => 'nullable|date|after:now',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validierungsfehler beim Dokumenten-Upload',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenant = $this->getCurrentTenant();
        $file = $request->file('file');
        
        $metadata = array_merge(
            $request->get('metadata', []),
            $request->only(['title', 'description', 'visibility', 'expires_at'])
        );

        try {
            $document = $this->documentService->uploadDocument(
                $file,
                $tenant,
                auth()->user(),
                $metadata
            );

            return response()->json([
                'message' => 'Dokument erfolgreich hochgeladen',
                'data' => $document->load(['uploader', 'categories.categoryGroup'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Dokumenten-Upload: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|max:10240', // 10MB max per file
            'common_settings.visibility' => 'nullable|in:public,internal,confidential,restricted',
            'common_settings.expires_at' => 'nullable|date|after:now',
            'file_settings' => 'nullable|array',
            'file_settings.*.title' => 'nullable|string|max:255',
            'file_settings.*.description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validierungsfehler beim Bulk-Upload',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenant = $this->getCurrentTenant();
        $files = $request->file('files');
        $commonSettings = $request->get('common_settings', []);
        $fileSettings = $request->get('file_settings', []);

        // Prepare files array for service
        $filesForUpload = [];
        foreach ($files as $index => $file) {
            $metadata = array_merge(
                $commonSettings,
                $fileSettings[$index] ?? []
            );
            
            $filesForUpload[] = [
                'file' => $file,
                'metadata' => $metadata
            ];
        }

        try {
            $results = $this->documentService->uploadMultipleDocuments(
                $filesForUpload,
                $tenant,
                auth()->user(),
                $commonSettings
            );

            // Generate response message
            $summary = $results['summary'];
            $message = '';
            
            if ($summary['success_count'] > 0 && $summary['error_count'] > 0) {
                $message = "{$summary['success_count']} von {$summary['total']} Dateien erfolgreich hochgeladen";
            } elseif ($summary['success_count'] === $summary['total']) {
                $message = "Alle {$summary['total']} Dateien erfolgreich hochgeladen";
            } else {
                $message = "Upload fehlgeschlagen: {$summary['error_count']} von {$summary['total']} Dateien konnten nicht hochgeladen werden";
            }

            // Load relationships for successful documents
            $successfulDocuments = collect($results['successful'])->map(function ($item) {
                return $item['document']->load(['uploader', 'categories.categoryGroup']);
            });

            return response()->json([
                'message' => $message,
                'data' => [
                    'successful' => $successfulDocuments,
                    'failed' => $results['failed'],
                    'summary' => $results['summary']
                ]
            ], $summary['error_count'] > 0 ? 207 : 201); // 207 Multi-Status or 201 Created
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Bulk-Upload: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Document $document): JsonResponse
    {
        $this->authorizeDocumentAccess($document);

        // Record view activity
        $document->markAsViewed(auth()->user());

        return response()->json([
            'data' => $document->load([
                'uploader',
                'archivedBy',
                'lastAccessedBy',
                'categories.categoryGroup',
                'shares' => function ($query) {
                    $query->active()->notExpired();
                },
                'activities' => function ($query) {
                    $query->orderBy('occurred_at', 'desc')->limit(20);
                }
            ])
        ]);
    }

    public function update(Request $request, Document $document): JsonResponse
    {
        $this->authorizeDocumentAccess($document, 'edit');

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'visibility' => 'nullable|in:public,internal,confidential,restricted',
            'expires_at' => 'nullable|date|after:now',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validierungsfehler bei Dokumenten-Update',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updatedDocument = $this->documentService->updateDocument(
                $document,
                $request->validated()
            );

            return response()->json([
                'message' => 'Dokument erfolgreich aktualisiert',
                'data' => $updatedDocument->load(['uploader', 'categories.categoryGroup'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Dokumenten-Update: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Document $document): JsonResponse
    {
        $this->authorizeDocumentAccess($document, 'delete');

        try {
            $this->documentService->deleteDocument($document, auth()->user());

            return response()->json([
                'message' => 'Dokument erfolgreich gelöscht'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Löschen des Dokuments: ' . $e->getMessage()
            ], 500);
        }
    }

    public function download(Document $document)
    {
        $this->authorizeDocumentAccess($document, 'download');

        try {
            return $this->documentService->downloadDocument($document, auth()->user());
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Download: ' . $e->getMessage()
            ], 500);
        }
    }

    public function restore(Document $document): JsonResponse
    {
        $this->authorizeDocumentAccess($document, 'edit');

        if ($document->status !== 'deleted') {
            return response()->json([
                'message' => 'Dokument ist nicht gelöscht'
            ], 400);
        }

        try {
            $restoredDocument = $this->documentService->restoreDocument($document, auth()->user());

            return response()->json([
                'message' => 'Dokument erfolgreich wiederhergestellt',
                'data' => $restoredDocument->load(['uploader', 'categories.categoryGroup'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei der Wiederherstellung: ' . $e->getMessage()
            ], 500);
        }
    }

    public function assignCategory(Request $request, Document $document): JsonResponse
    {
        $this->authorizeDocumentAccess($document, 'edit');

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|uuid|exists:categories,id',
            'assignment_type' => 'nullable|in:manual,ai_auto,ai_assisted,rule_based,bulk',
            'confidence_score' => 'nullable|numeric|min:0|max:1',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validierungsfehler bei Kategorie-Zuweisung',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $category = \App\Core\Category\Models\Category::findOrFail($request->get('category_id'));
            
            // Verify category belongs to current tenant
            if ($category->categoryGroup->tenant_id !== $this->getCurrentTenant()->id) {
                return response()->json([
                    'message' => 'Kategorie gehört nicht zu diesem Mandanten'
                ], 403);
            }

            $document->assignCategory(
                $category,
                $request->get('assignment_type', 'manual'),
                $request->get('confidence_score'),
                auth()->user(),
                $request->get('reason'),
                ['source' => 'manual_assignment']
            );

            return response()->json([
                'message' => 'Kategorie erfolgreich zugewiesen',
                'data' => $document->fresh()->load(['categories.categoryGroup'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei Kategorie-Zuweisung: ' . $e->getMessage()
            ], 500);
        }
    }

    public function removeCategory(Request $request, Document $document): JsonResponse
    {
        $this->authorizeDocumentAccess($document, 'edit');

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|uuid|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $category = \App\Core\Category\Models\Category::findOrFail($request->get('category_id'));
            $document->removeCategory($category);

            return response()->json([
                'message' => 'Kategorie erfolgreich entfernt',
                'data' => $document->fresh()->load(['categories.categoryGroup'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Entfernen der Kategorie: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkCategorize(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'uuid|exists:documents,id',
            'category_id' => 'required|uuid|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validierungsfehler bei Bulk-Kategorisierung',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $results = $this->documentService->bulkCategorizeDocuments(
                $request->get('document_ids'),
                $request->get('category_id')
            );

            $message = "Kategorisierung abgeschlossen: {$results['success']} erfolgreich";
            if ($results['failed'] > 0) {
                $message .= ", {$results['failed']} fehlgeschlagen";
            }

            return response()->json([
                'message' => $message,
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei Bulk-Kategorisierung: ' . $e->getMessage()
            ], 500);
        }
    }

    public function statistics(): JsonResponse
    {
        $tenant = $this->getCurrentTenant();
        
        try {
            $stats = $this->documentService->getDocumentStatistics($tenant);

            return response()->json([
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Laden der Statistiken: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verarbeite mehrere Dokumente mit AI für Kategorisierung
     */
    public function bulkAiCategorize(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'document_ids' => 'required|array|min:1|max:50',
            'document_ids.*' => 'uuid|exists:documents,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenant = $this->getCurrentTenant();
        $documentIds = $request->get('document_ids');
        $processedCount = 0;

        try {
            // Prüfe ob alle Dokumente zu diesem Tenant gehören
            foreach ($documentIds as $documentId) {
                $document = Document::find($documentId);
                
                if ($document && $document->tenant_id === $tenant->id) {
                    // Dispatch AI processing job
                    ProcessDocumentWithAI::dispatch($documentId);
                    $processedCount++;
                }
            }

            return response()->json([
                'message' => "$processedCount Dokumente werden im Hintergrund mit AI verarbeitet",
                'data' => [
                    'processed' => $processedCount,
                    'total' => count($documentIds)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei AI-Verarbeitung: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verarbeite einzelnes Dokument mit AI
     */
    public function processWithAI(Document $document): JsonResponse
    {
        $this->authorizeDocumentAccess($document, 'edit');

        try {
            // Dispatch AI processing job
            ProcessDocumentWithAI::dispatch($document->id);

            return response()->json([
                'message' => 'Dokument wird im Hintergrund mit AI verarbeitet',
                'data' => $document->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei AI-Verarbeitung: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get signed URLs for document preview and download
     */
    public function getSignedUrls(Document $document): JsonResponse
    {
        $this->authorizeDocumentAccess($document, 'view');

        try {
            $data = [
                'preview_url' => null,
                'download_url' => $this->signedUrlService->getDownloadUrl($document),
                'supports_preview' => $this->signedUrlService->supportsPreview($document)
            ];

            // Only generate preview URL for supported file types
            if ($this->signedUrlService->supportsPreview($document)) {
                $data['preview_url'] = $this->signedUrlService->getPreviewUrl($document);
            }

            return response()->json([
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Generieren der Signed URLs: ' . $e->getMessage()
            ], 500);
        }
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


    protected function authorizeDocumentAccess(Document $document, string $action = 'view'): void
    {
        $tenant = $this->getCurrentTenant();
        
        if ($document->tenant_id !== $tenant->id) {
            abort(403, 'Kein Zugriff auf dieses Dokument');
        }

        // TODO: Implement more granular permission checks based on document visibility and user roles
        // For now, basic tenant membership check is sufficient
    }
}