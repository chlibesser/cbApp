<?php

namespace App\Domains\Workflow\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Core\Auth\Models\Account;
use App\Core\Tenant\Models\Tenant;

class Workflow extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'description',
        'tenant_id',
        'created_by',
        'nodes',
        'edges',
        'metadata',
        'status'
    ];

    protected $casts = [
        'nodes' => 'array',
        'edges' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the tenant that owns the workflow
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the account that created the workflow
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'created_by');
    }

    /**
     * Scope a query to only include active workflows
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include draft workflows
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}