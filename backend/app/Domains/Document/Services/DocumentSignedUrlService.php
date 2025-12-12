<?php

namespace App\Domains\Document\Services;

use App\Core\Document\Models\Document;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DocumentSignedUrlService
{
    /**
     * Generate a signed URL for document preview/download
     */
    public function generateSignedUrl(Document $document, int $expiryMinutes = 120): string
    {
        $expiry = Carbon::now()->addMinutes($expiryMinutes);
        
        // Always use signed URL route (works in both development and production)
        return URL::temporarySignedRoute(
            'documents.signed-download',
            $expiry,
            [
                'document' => $document->id,
                'filename' => $document->original_filename
            ]
        );
    }

    /**
     * Validate and serve the document file
     */
    public function serveDocument(Document $document): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (!Storage::exists($document->storage_path)) {
            abort(404, 'Dokument-Datei nicht gefunden');
        }

        // Sanitize filename for HTTP headers
        $safeFilename = $this->sanitizeFilename($document->original_filename);
        
        // Get MIME type
        $mimeType = Storage::mimeType($document->storage_path) ?: $document->mime_type;
        
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Length' => Storage::size($document->storage_path),
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff'
            // Removed X-Frame-Options to allow iframe embedding
        ];

        // Only add Content-Disposition if filename is safe
        if (!empty($safeFilename)) {
            $headers['Content-Disposition'] = 'inline; filename="' . $safeFilename . '"';
        }

        return response()->stream(function () use ($document) {
            $stream = Storage::readStream($document->storage_path);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, $headers);
    }

    /**
     * Sanitize filename for HTTP headers
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove control characters, newlines, and problematic characters
        $safeFilename = preg_replace('/[\r\n\t\x00-\x1F\x7F"\\\\]/', '', $filename);
        $safeFilename = trim($safeFilename);
        
        // If filename becomes empty, return empty string to skip header
        if (empty($safeFilename)) {
            return '';
        }
        
        return $safeFilename;
    }

    /**
     * Get signed URL for preview (inline display)
     */
    public function getPreviewUrl(Document $document): string
    {
        return $this->generateSignedUrl($document, 120); // 2 hours
    }

    /**
     * Get signed URL for download (attachment)
     */
    public function getDownloadUrl(Document $document): string
    {
        return $this->generateSignedUrl($document, 60); // 1 hour
    }

    /**
     * Check if document supports inline preview
     */
    public function supportsPreview(Document $document): bool
    {
        $supportedTypes = [
            'application/pdf',
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif',
            'image/webp',
            'text/plain',
            'text/html',
            'text/markdown'
        ];

        return in_array($document->mime_type, $supportedTypes);
    }
}