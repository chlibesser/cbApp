<?php

namespace App\Domains\Document\Services;

use App\Core\Document\Models\Document;
use App\Core\Document\Models\DocumentActivity;
use App\Core\Auth\Models\Account;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Contracts\Auth\Authenticatable;
// use App\Core\AI\Services\AIService;
use App\Domains\AI\Jobs\ProcessDocumentWithAI;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;

class DocumentService
{
    public function __construct()
    {
        // AI Service is injected in the Job, not here
    }

    public function uploadDocument(
        UploadedFile $file,
        Tenant $tenant,
        Authenticatable $uploader,
        array $metadata = []
    ): Document {
        // Generate file hash for deduplication
        $fileHash = hash_file('sha256', $file->getRealPath());
        
        // Check for duplicate
        $existing = Document::where('file_hash', $fileHash)
                           ->where('tenant_id', $tenant->id)
                           ->first();
                           
        if ($existing) {
            throw new \Exception('Dokument bereits vorhanden: ' . $existing->original_filename);
        }

        // Generate storage filename
        $storedFilename = $this->generateStorageFilename($file);
        $storagePath = "documents/{$tenant->id}/{$storedFilename}";

        // Store file
        $file->storeAs(
            dirname($storagePath),
            basename($storagePath),
            ['disk' => 'local']
        );

        // Create document record
        $document = Document::create([
            'tenant_id' => $tenant->id,
            'uploaded_by' => $uploader->id,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => $storedFilename,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_hash' => $fileHash,
            'storage_path' => $storagePath,
            'title' => $metadata['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'description' => $metadata['description'] ?? null,
            'metadata' => $metadata,
            'visibility' => $metadata['visibility'] ?? 'internal',
            'ai_processing_status' => 'pending',
            'status' => 'active',
        ]);

        // Log upload activity
        DocumentActivity::logUpload($document, $uploader, [
            'original_path' => $file->getRealPath(),
            'storage_path' => $storagePath,
        ]);

        // Queue AI processing if document is processable
        if ($document->is_processable) {
            $this->queueAIProcessing($document);
        }

        return $document;
    }

    public function updateDocument(Document $document, array $data): Document
    {
        $allowedFields = [
            'title',
            'description', 
            'metadata',
            'visibility',
            'expires_at',
        ];

        $updateData = array_intersect_key($data, array_flip($allowedFields));
        $document->update($updateData);

        // Log the update
        DocumentActivity::log(
            $document,
            'updated',
            "Document '{$document->original_filename}' was updated",
            ['updated_fields' => array_keys($updateData)],
            auth()->user(),
            'user',
            'modification'
        );

        return $document->fresh();
    }

    public function deleteDocument(Document $document, Authenticatable $deletedBy): void
    {
        // Soft delete document
        $document->update([
            'status' => 'deleted',
            'archived_at' => now(),
            'archived_by' => $deletedBy->id,
        ]);

        // Log deletion
        DocumentActivity::log(
            $document,
            'deleted',
            "Document '{$document->original_filename}' was deleted",
            [],
            $deletedBy,
            'user',
            'modification'
        );
    }

    public function permanentlyDeleteDocument(Document $document): void
    {
        // Delete physical file
        if (Storage::exists($document->storage_path)) {
            Storage::delete($document->storage_path);
        }

        // Log permanent deletion
        DocumentActivity::log(
            $document,
            'permanently_deleted',
            "Document '{$document->original_filename}' was permanently deleted",
            ['storage_path' => $document->storage_path],
            null,
            'system',
            'modification',
            'warning'
        );

        // Delete from database
        $document->forceDelete();
    }

    public function restoreDocument(Document $document, Authenticatable $restoredBy): Document
    {
        $document->update([
            'status' => 'active',
            'archived_at' => null,
            'archived_by' => null,
        ]);

        // Log restoration
        DocumentActivity::log(
            $document,
            'restored',
            "Document '{$document->original_filename}' was restored",
            [],
            $restoredBy,
            'user',
            'modification'
        );

        return $document;
    }

    public function getDocumentContent(Document $document): string
    {
        if (!Storage::exists($document->storage_path)) {
            throw new \Exception('Dokument-Datei nicht gefunden');
        }

        return Storage::get($document->storage_path);
    }

    public function downloadDocument(Document $document, Authenticatable $downloader = null): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        Log::info('Download attempt', [
            'document_id' => $document->id,
            'storage_path' => $document->storage_path,
            'file_exists' => Storage::exists($document->storage_path),
            'storage_disk' => config('filesystems.default'),
            'full_path' => Storage::path($document->storage_path)
        ]);

        if (!Storage::exists($document->storage_path)) {
            Log::error('Document file not found', [
                'document_id' => $document->id,
                'storage_path' => $document->storage_path,
                'full_path' => Storage::path($document->storage_path),
                'disk' => config('filesystems.default')
            ]);
            throw new \Exception('Dokument-Datei nicht gefunden: ' . $document->storage_path);
        }

        // Record download activity
        $document->markAsDownloaded();
        DocumentActivity::logDownload($document, $downloader);

        // Sanitize filename for HTTP headers - remove newlines and control characters
        $safeFilename = preg_replace('/[\r\n\t\x00-\x1F\x7F]/', '', $document->original_filename);
        $safeFilename = trim($safeFilename);
        
        Log::info('Download starting', [
            'document_id' => $document->id,
            'original_filename' => $document->original_filename,
            'safe_filename' => $safeFilename,
            'storage_path' => $document->storage_path
        ]);
        
        return Storage::download($document->storage_path, $safeFilename);
    }

    public function searchDocuments(
        Tenant $tenant,
        string $query = null,
        array $filters = []
    ): Collection {
        $documentsQuery = Document::forTenant($tenant->id)
                                ->active()
                                ->latestVersions();

        // Text search
        if ($query) {
            $documentsQuery->where(function ($q) use ($query) {
                $q->where('title', 'ILIKE', "%{$query}%")
                  ->orWhere('description', 'ILIKE', "%{$query}%")
                  ->orWhere('original_filename', 'ILIKE', "%{$query}%")
                  ->orWhere('extracted_text', 'ILIKE', "%{$query}%");
            });
        }

        // Apply filters
        if (isset($filters['mime_type'])) {
            $documentsQuery->byMimeType($filters['mime_type']);
        }

        if (isset($filters['visibility'])) {
            $documentsQuery->byVisibility($filters['visibility']);
        }

        if (isset($filters['category_id'])) {
            $documentsQuery->whereHas('categories', function ($q) use ($filters) {
                $q->where('categories.id', $filters['category_id']);
            });
        }

        if (isset($filters['uploaded_by'])) {
            $documentsQuery->where('uploaded_by', $filters['uploaded_by']);
        }

        if (isset($filters['date_from'])) {
            $documentsQuery->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $documentsQuery->where('created_at', '<=', $filters['date_to']);
        }

        return $documentsQuery->with(['uploader', 'categories.categoryGroup'])
                             ->orderBy('created_at', 'desc')
                             ->get();
    }

    public function getDocumentsForCategory(string $categoryId): Collection
    {
        return Document::whereHas('categories', function ($q) use ($categoryId) {
                    $q->where('categories.id', $categoryId);
                })
                ->active()
                ->latestVersions()
                ->with(['uploader', 'categories.categoryGroup'])
                ->orderBy('created_at', 'desc')
                ->get();
    }

    public function getDocumentStatistics(Tenant $tenant): array
    {
        try {
            $baseQuery = Document::forTenant($tenant->id)->active();

            return [
                'total_documents' => $baseQuery->count(),
                'total_size' => $baseQuery->sum('file_size') ?: 0,
                'documents_by_type' => $baseQuery->selectRaw('mime_type, count(*) as count')
                                               ->groupBy('mime_type')
                                               ->pluck('count', 'mime_type')
                                               ->toArray(),
                'documents_by_month' => $baseQuery->selectRaw('EXTRACT(YEAR_MONTH FROM created_at) as month, count(*) as count')
                                                 ->groupBy('month')
                                                 ->orderBy('month')
                                                 ->pluck('count', 'month')
                                                 ->toArray(),
                'ai_processing_status' => $baseQuery->selectRaw('ai_processing_status, count(*) as count')
                                                  ->groupBy('ai_processing_status')
                                                  ->pluck('count', 'ai_processing_status')
                                                  ->toArray(),
            ];
        } catch (\Exception $e) {
            // Return empty statistics if there's an error
            Log::error('Error generating document statistics', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'total_documents' => 0,
                'total_size' => 0,
                'documents_by_type' => [],
                'documents_by_month' => [],
                'ai_processing_status' => [],
            ];
        }
    }

    public function bulkCategorizeDocuments(array $documentIds, string $categoryId): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];
        
        $documents = Document::whereIn('id', $documentIds)->get();
        $category = \App\Core\Category\Models\Category::findOrFail($categoryId);

        foreach ($documents as $document) {
            try {
                $document->assignCategory(
                    $category,
                    'bulk',
                    null,
                    auth()->user(),
                    'Bulk categorization'
                );
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'document_id' => $document->id,
                    'filename' => $document->original_filename,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    protected function generateStorageFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return Str::uuid() . ($extension ? ".$extension" : '');
    }

    protected function queueAIProcessing(Document $document): void
    {
        // Dispatch AI processing job
        ProcessDocumentWithAI::dispatch($document->id)
            ->onQueue(config('ai.content_processing.queue', 'ai-processing'));
        
        Log::info('Queued document for AI processing', [
            'document_id' => $document->id,
            'filename' => $document->original_filename
        ]);
    }
}