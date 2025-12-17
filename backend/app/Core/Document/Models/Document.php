<?php

namespace App\Core\Document\Models;

use App\Core\Shared\Models\BaseModel;
use App\Core\Auth\Models\Account;
use App\Core\Tenant\Models\Tenant;
use App\Core\Category\Models\Category;
use App\Core\Category\Enums\SelectionType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Document extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'uploaded_by',
        'original_filename',
        'stored_filename',
        'mime_type',
        'file_size',
        'file_hash',
        'storage_path',
        'title',
        'description',
        'metadata',
        'ai_classification',
        'ai_metadata',
        'ai_tags',
        'ai_summary',
        'ai_confidence_score',
        'ai_processed_at',
        'ai_processing_status',
        'extracted_text',
        'structured_data',
        'language',
        'page_count',
        'visibility',
        'access_permissions',
        'requires_approval',
        'is_encrypted',
        'status',
        'expires_at',
        'archived_at',
        'archived_by',
        'parent_document_id',
        'version',
        'is_latest_version',
        'workflow_data',
        'workflow_status',
        'workflow_started_at',
        'workflow_completed_at',
        'download_count',
        'view_count',
        'last_accessed_at',
        'last_accessed_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'ai_classification' => 'array',
        'ai_metadata' => 'array',
        'ai_tags' => 'array',
        'ai_confidence_score' => 'float',
        'ai_processed_at' => 'datetime',
        'structured_data' => 'array',
        'access_permissions' => 'array',
        'requires_approval' => 'boolean',
        'is_encrypted' => 'boolean',
        'expires_at' => 'datetime',
        'archived_at' => 'datetime',
        'is_latest_version' => 'boolean',
        'workflow_data' => 'array',
        'workflow_started_at' => 'datetime',
        'workflow_completed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    // Relationships

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'uploaded_by');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'archived_by');
    }

    public function lastAccessedBy(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'last_accessed_by');
    }

    public function parentDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'parent_document_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(Document::class, 'parent_document_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(DocumentActivity::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'document_category_assignments')
                    ->withPivot([
                        'assignment_type',
                        'confidence_score',
                        'assigned_by',
                        'assignment_reason',
                        'assignment_context',
                        'assigned_at'
                    ])
                    ->withTimestamps();
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeLatestVersions($query)
    {
        return $query->where('is_latest_version', true);
    }

    public function scopeWithAIProcessing($query, string $status = null)
    {
        $query = $query->whereNotNull('ai_processed_at');
        
        if ($status) {
            $query->where('ai_processing_status', $status);
        }
        
        return $query;
    }

    public function scopeByMimeType($query, string $mimeType)
    {
        return $query->where('mime_type', $mimeType);
    }

    public function scopeByVisibility($query, string $visibility)
    {
        return $query->where('visibility', $visibility);
    }

    public function scopeInWorkflow($query)
    {
        return $query->whereNotNull('workflow_status')
                    ->whereNull('workflow_completed_at');
    }

    // Accessors & Mutators

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function getIsProcessableAttribute(): bool
    {
        $processableMimes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        return in_array($this->mime_type, $processableMimes);
    }

    public function getAiProcessingCompleteAttribute(): bool
    {
        return $this->ai_processing_status === 'completed';
    }

    public function getHasHighConfidenceAiAttribute(): bool
    {
        return $this->ai_confidence_score && $this->ai_confidence_score >= 0.8;
    }

    // Helper Methods

    public function markAsViewed(Account $user = null): void
    {
        $this->increment('view_count');
        $this->update([
            'last_accessed_at' => now(),
            'last_accessed_by' => $user?->id
        ]);
    }

    public function markAsDownloaded(): void
    {
        $this->increment('download_count');
    }

    public function hasCategory(Category $category): bool
    {
        return $this->categories()->where('categories.id', $category->id)->exists();
    }

    public function getCategoriesForGroup(string $groupId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->categories()
                   ->whereHas('categoryGroup', fn($q) => $q->where('id', $groupId))
                   ->get();
    }

    public function assignCategory(
        Category $category,
        string $assignmentType = 'manual',
        float $confidenceScore = null,
        Account $assignedBy = null,
        string $reason = null,
        array $context = []
    ): void {
        try {
            // Handle single-select groups - remove existing categories from same group
            if ($category->categoryGroup->selection_type === SelectionType::SINGLE) {
                // Get IDs of categories in the same group to detach
                $categoriesToDetach = $this->categories()
                    ->whereHas('categoryGroup', fn($q) => $q->where('id', $category->category_group_id))
                    ->pluck('categories.id')
                    ->toArray();
                
                if (!empty($categoriesToDetach)) {
                    $this->categories()->detach($categoriesToDetach);
                }
            }

            // Check if category is already assigned to avoid duplicate key errors
            if (!$this->hasCategory($category)) {
                // Attach new category with pivot data
                $this->categories()->attach($category->id, [
                    'tenant_id' => $this->tenant_id,
                    'assignment_type' => $assignmentType,
                    'confidence_score' => $confidenceScore,
                    'assigned_by' => $assignedBy?->id,
                    'assignment_reason' => $reason,
                    'assignment_context' => json_encode($context),
                    'assigned_at' => now(),
                ]);

                // Record usage in category
                $category->recordUsage();

                // Log the categorization
                DocumentActivity::logCategorization(
                    $this,
                    $category->name,
                    $assignedBy,
                    [
                        'assignment_type' => $assignmentType,
                        'confidence_score' => $confidenceScore,
                        'category_group' => $category->categoryGroup->name,
                    ]
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to assign category to document", [
                'document_id' => $this->id,
                'category_id' => $category->id,
                'error' => $e->getMessage(),
                'assignment_type' => $assignmentType,
                'confidence_score' => $confidenceScore
            ]);
            throw $e;
        }
    }

    public function updateAIProcessingStatus(string $status, array $data = []): void
    {
        $updateData = ['ai_processing_status' => $status];
        
        if ($status === 'completed') {
            $updateData['ai_processed_at'] = now();
            $updateData = array_merge($updateData, $data);
        }
        
        $this->update($updateData);

        // Log AI processing completion
        DocumentActivity::logAIProcessing(
            $this,
            'classification',
            $status === 'completed',
            $data
        );
    }

    public function removeCategory(Category $category): void
    {
        $this->categories()->detach($category->id);
        
        // Log the removal
        DocumentActivity::log(
            $this,
            'category_removed',
            "Category '{$category->name}' was removed from document",
            ['category_name' => $category->name, 'category_group' => $category->categoryGroup->name],
            null,
            'system',
            'modification'
        );
    }

    public function getCategoryAssignmentData(Category $category): ?array
    {
        $pivot = $this->categories()->where('categories.id', $category->id)->first()?->pivot;
        
        if (!$pivot) {
            return null;
        }

        return [
            'assignment_type' => $pivot->assignment_type,
            'confidence_score' => $pivot->confidence_score,
            'assigned_by' => $pivot->assigned_by,
            'assignment_reason' => $pivot->assignment_reason,
            'assignment_context' => $pivot->assignment_context,
            'assigned_at' => $pivot->assigned_at,
        ];
    }

    public function createNewVersion(): Document
    {
        // Mark current as not latest
        $this->update(['is_latest_version' => false]);
        
        // Create new version
        $newVersion = $this->replicate();
        $newVersion->version = $this->version + 1;
        $newVersion->is_latest_version = true;
        $newVersion->parent_document_id = $this->parent_document_id ?: $this->id;
        $newVersion->save();
        
        // Copy category assignments to new version
        foreach ($this->categories as $category) {
            $assignmentData = $this->getCategoryAssignmentData($category);
            $newVersion->assignCategory(
                $category,
                $assignmentData['assignment_type'] ?? 'manual',
                $assignmentData['confidence_score'],
                $assignmentData['assigned_by'] ? Account::find($assignmentData['assigned_by']) : null,
                'Inherited from previous version',
                array_merge($assignmentData['assignment_context'] ?? [], ['inherited_from_version' => $this->version])
            );
        }
        
        return $newVersion;
    }
}