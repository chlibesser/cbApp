<?php

namespace App\Infrastructure\Http\Controllers\Documents;

use App\Infrastructure\Http\Controllers\Controller;
use App\Core\Document\Models\Document;
use App\Core\Document\Models\DocumentActivity;
use App\Domains\Document\Services\DocumentSignedUrlService;
use Illuminate\Http\Request;

class SignedDocumentController extends Controller
{
    public function __construct(
        private DocumentSignedUrlService $signedUrlService
    ) {}

    /**
     * Serve a document via signed URL (public route)
     */
    public function download(Request $request, Document $document)
    {
        // Laravel automatically validates the signature
        // If we reach here, the signature is valid and not expired
        
        try {
            // Log access for analytics (without user context since this is public)
            DocumentActivity::log(
                $document,
                'viewed',
                "Document '{$document->original_filename}' accessed via signed URL",
                [
                    'access_type' => 'signed_url',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ],
                null, // No user context
                'system',
                'access'
            );

            // Update view count
            $document->increment('view_count');
            $document->update(['last_accessed_at' => now()]);

            // Serve the file
            return $this->signedUrlService->serveDocument($document);
            
        } catch (\Exception $e) {
            // Log error
            \Log::error('Signed document access failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);
            
            abort(500, 'Fehler beim Laden des Dokuments');
        }
    }
}