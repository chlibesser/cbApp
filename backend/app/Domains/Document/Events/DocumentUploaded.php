<?php

namespace App\Domains\Document\Events;

use App\Core\Document\Models\Document;
use App\Core\Auth\Models\Account;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\ShouldNotQueue;

class DocumentUploaded implements ShouldNotQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Document $document,
        public Authenticatable $uploader,
        public array $metadata = []
    ) {}

    /**
     * Get event type for workflow triggers
     */
    public function getEventType(): string
    {
        $filename = $this->document->original_filename;
        $mimeType = $this->document->mime_type;
        
        // Create a pattern that workflows can match against
        return sprintf('%s|%s', $filename, $mimeType);
    }

    /**
     * Get document file extension
     */
    public function getFileExtension(): string
    {
        return strtolower(pathinfo($this->document->original_filename, PATHINFO_EXTENSION));
    }

    /**
     * Check if document matches a pattern
     */
    public function matchesPattern(string $pattern): bool
    {
        $filename = $this->document->original_filename;
        $extension = $this->getFileExtension();
        
        // Convert pattern to regex
        $regexPattern = str_replace(
            ['*', '.'], 
            ['.*', '\.'], 
            $pattern
        );
        
        // Check filename pattern
        if (preg_match('/^' . $regexPattern . '$/i', $filename)) {
            return true;
        }
        
        // Check extension pattern
        if (preg_match('/^' . $regexPattern . '$/i', "*.$extension")) {
            return true;
        }
        
        return false;
    }
}