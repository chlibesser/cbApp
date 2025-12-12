<?php

namespace App\Core\Document\Models;

use App\Core\Shared\Models\BaseModel;
use App\Core\Auth\Models\Account;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable;

class DocumentActivity extends Model
{
    use HasUuids;

    /**
     * Indicates if the model's ID is auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     */
    protected $keyType = 'string';
    protected $fillable = [
        'document_id',
        'tenant_id',
        'actor_id',
        'actor_type',
        'actor_name',
        'activity_type',
        'activity_description',
        'activity_data',
        'ip_address',
        'user_agent',
        'session_id',
        'request_metadata',
        'activity_category',
        'activity_level',
        'related_entity_type',
        'related_entity_id',
        'related_entities',
        'processing_time_ms',
        'was_successful',
        'error_code',
        'error_message',
        'occurred_at',
    ];

    protected $casts = [
        'activity_data' => 'array',
        'request_metadata' => 'array',
        'related_entities' => 'array',
        'was_successful' => 'boolean',
        'occurred_at' => 'datetime',
    ];

    // Relationships

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'actor_id');
    }

    // Scopes

    public function scopeForDocument($query, string $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByActor($query, string $actorId)
    {
        return $query->where('actor_id', $actorId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('activity_category', $category);
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('activity_level', $level);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('was_successful', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('was_successful', false);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('occurred_at', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('occurred_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('occurred_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('occurred_at', now()->month)
                    ->whereYear('occurred_at', now()->year);
    }

    // Accessors

    public function getActorDisplayNameAttribute(): string
    {
        if ($this->actor) {
            return $this->actor->full_name;
        }

        if ($this->actor_name) {
            return $this->actor_name;
        }

        return ucfirst($this->actor_type);
    }

    public function getIsSystemActionAttribute(): bool
    {
        return in_array($this->actor_type, ['system', 'ai']);
    }

    public function getIsUserActionAttribute(): bool
    {
        return $this->actor_type === 'user' && $this->actor_id;
    }

    public function getIsExternalActionAttribute(): bool
    {
        return $this->actor_type === 'external';
    }

    public function getFormattedOccurredAtAttribute(): string
    {
        return $this->occurred_at->diffForHumans();
    }

    public function getActivityIconAttribute(): string
    {
        return match($this->activity_type) {
            'uploaded' => 'mdi-upload',
            'viewed' => 'mdi-eye',
            'downloaded' => 'mdi-download',
            'shared' => 'mdi-share-variant',
            'categorized' => 'mdi-tag',
            'edited' => 'mdi-pencil',
            'deleted' => 'mdi-delete',
            'archived' => 'mdi-archive',
            'restored' => 'mdi-restore',
            'ai_processed' => 'mdi-robot',
            'workflow_started' => 'mdi-play-circle',
            'workflow_completed' => 'mdi-check-circle',
            'comment_added' => 'mdi-comment',
            'permission_changed' => 'mdi-lock',
            default => 'mdi-information'
        };
    }

    public function getActivityColorAttribute(): string
    {
        return match($this->activity_level) {
            'info' => 'blue',
            'warning' => 'orange',
            'error' => 'red',
            'critical' => 'red darken-2',
            default => 'grey'
        };
    }

    // Helper Methods

    public static function log(
        Document $document,
        string $activityType,
        string $description,
        array $data = [],
        Authenticatable $actor = null,
        string $actorType = 'user',
        string $category = 'access',
        string $level = 'info'
    ): self {
        $activity = static::create([
            'document_id' => $document->id,
            'tenant_id' => $document->tenant_id,
            'actor_id' => $actor?->id,
            'actor_type' => $actorType,
            'actor_name' => $actor ? null : ($data['actor_name'] ?? null),
            'activity_type' => $activityType,
            'activity_description' => $description,
            'activity_data' => $data,
            'activity_category' => $category,
            'activity_level' => $level,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'session_id' => session()?->getId(),
            'request_metadata' => [
                'url' => request()?->url(),
                'method' => request()?->method(),
                'route' => request()?->route()?->getName(),
            ],
            'occurred_at' => now(),
        ]);

        return $activity;
    }

    public static function logUpload(Document $document, Authenticatable $uploader, array $metadata = []): self
    {
        return static::log(
            $document,
            'uploaded',
            "Document '{$document->original_filename}' was uploaded",
            array_merge($metadata, [
                'file_size' => $document->file_size,
                'mime_type' => $document->mime_type,
            ]),
            $uploader,
            'user',
            'modification'
        );
    }

    public static function logView(Document $document, Authenticatable $viewer = null, array $metadata = []): self
    {
        return static::log(
            $document,
            'viewed',
            "Document '{$document->original_filename}' was viewed",
            $metadata,
            $viewer,
            $viewer ? 'user' : 'external',
            'access'
        );
    }

    public static function logDownload(Document $document, Authenticatable $downloader = null, array $metadata = []): self
    {
        return static::log(
            $document,
            'downloaded',
            "Document '{$document->original_filename}' was downloaded",
            $metadata,
            $downloader,
            $downloader ? 'user' : 'external',
            'access'
        );
    }

    public static function logShare(Document $document, Authenticatable $sharer, string $shareType, array $metadata = []): self
    {
        return static::log(
            $document,
            'shared',
            "Document '{$document->original_filename}' was shared ({$shareType})",
            array_merge($metadata, ['share_type' => $shareType]),
            $sharer,
            'user',
            'sharing'
        );
    }

    public static function logCategorization(Document $document, string $categoryName, Authenticatable $categorizer = null, array $metadata = []): self
    {
        return static::log(
            $document,
            'categorized',
            "Document '{$document->original_filename}' was categorized as '{$categoryName}'",
            array_merge($metadata, ['category' => $categoryName]),
            $categorizer,
            $categorizer ? 'user' : 'ai',
            'modification'
        );
    }

    public static function logAIProcessing(Document $document, string $processType, bool $successful, array $metadata = []): self
    {
        return static::log(
            $document,
            'ai_processed',
            "AI processing ({$processType}) " . ($successful ? 'completed' : 'failed') . " for '{$document->original_filename}'",
            array_merge($metadata, [
                'process_type' => $processType,
                'successful' => $successful
            ]),
            null,
            'ai',
            'ai_processing',
            $successful ? 'info' : 'error'
        );
    }

    public static function logWorkflow(Document $document, string $workflowAction, Authenticatable $actor = null, array $metadata = []): self
    {
        return static::log(
            $document,
            "workflow_{$workflowAction}",
            "Workflow {$workflowAction} for document '{$document->original_filename}'",
            array_merge($metadata, ['workflow_action' => $workflowAction]),
            $actor,
            $actor ? 'user' : 'system',
            'workflow'
        );
    }

    public static function logError(Document $document, string $errorType, string $errorMessage, array $metadata = []): self
    {
        $activity = static::log(
            $document,
            'error',
            "Error occurred: {$errorMessage}",
            array_merge($metadata, ['error_type' => $errorType]),
            null,
            'system',
            'security',
            'error'
        );

        $activity->update([
            'was_successful' => false,
            'error_code' => $errorType,
            'error_message' => $errorMessage,
        ]);

        return $activity;
    }
}