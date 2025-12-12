<?php

namespace App\Core\Document\Models;

use App\Core\Shared\Models\BaseModel;
use App\Core\Identity\Models\Account;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DocumentShare extends BaseModel
{
    protected $fillable = [
        'document_id',
        'shared_by',
        'tenant_id',
        'shared_with_user',
        'shared_with_email',
        'permission_level',
        'specific_permissions',
        'share_token',
        'requires_password',
        'password_hash',
        'allow_public_access',
        'expires_at',
        'download_limit',
        'view_limit',
        'downloads_used',
        'views_used',
        'notify_on_access',
        'notify_on_download',
        'notification_email',
        'is_active',
        'first_accessed_at',
        'last_accessed_at',
        'access_log',
        'share_message',
        'share_context',
    ];

    protected $casts = [
        'specific_permissions' => 'array',
        'requires_password' => 'boolean',
        'allow_public_access' => 'boolean',
        'expires_at' => 'datetime',
        'notify_on_access' => 'boolean',
        'notify_on_download' => 'boolean',
        'is_active' => 'boolean',
        'first_accessed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'access_log' => 'array',
        'share_context' => 'array',
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

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'shared_by');
    }

    public function sharedWithUser(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'shared_with_user');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeForToken($query, string $token)
    {
        return $query->where('share_token', $token);
    }

    public function scopePublic($query)
    {
        return $query->where('allow_public_access', true);
    }

    public function scopeInternal($query)
    {
        return $query->whereNotNull('shared_with_user');
    }

    public function scopeExternal($query)
    {
        return $query->whereNotNull('shared_with_email')
                    ->whereNull('shared_with_user');
    }

    // Accessors & Mutators

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsLimitReachedAttribute(): bool
    {
        if ($this->download_limit && $this->downloads_used >= $this->download_limit) {
            return true;
        }

        if ($this->view_limit && $this->views_used >= $this->view_limit) {
            return true;
        }

        return false;
    }

    public function getIsAccessibleAttribute(): bool
    {
        return $this->is_active && 
               !$this->is_expired && 
               !$this->is_limit_reached;
    }

    public function getShareUrlAttribute(): string
    {
        if (!$this->share_token) {
            return '';
        }

        return url("/shared/documents/{$this->share_token}");
    }

    public function getRecipientDisplayNameAttribute(): string
    {
        if ($this->sharedWithUser) {
            return $this->sharedWithUser->full_name;
        }

        return $this->shared_with_email ?: 'Anonymous';
    }

    public function getShareTypeAttribute(): string
    {
        if ($this->shared_with_user) {
            return 'internal';
        }

        if ($this->shared_with_email) {
            return 'external';
        }

        if ($this->allow_public_access) {
            return 'public';
        }

        return 'unknown';
    }

    // Helper Methods

    public function generateShareToken(): string
    {
        $this->share_token = Str::random(64);
        $this->save();
        
        return $this->share_token;
    }

    public function setPassword(string $password): void
    {
        $this->update([
            'requires_password' => true,
            'password_hash' => bcrypt($password)
        ]);
    }

    public function checkPassword(string $password): bool
    {
        if (!$this->requires_password) {
            return true;
        }

        return password_verify($password, $this->password_hash);
    }

    public function recordAccess(string $type = 'view', array $metadata = []): void
    {
        $accessData = [
            'type' => $type,
            'timestamp' => now()->toISOString(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata
        ];

        // Update access log
        $accessLog = $this->access_log ?: [];
        $accessLog[] = $accessData;
        
        // Keep only last 100 access records
        if (count($accessLog) > 100) {
            $accessLog = array_slice($accessLog, -100);
        }

        // Update counters and timestamps
        $updates = [
            'access_log' => $accessLog,
            'last_accessed_at' => now()
        ];

        if (!$this->first_accessed_at) {
            $updates['first_accessed_at'] = now();
        }

        if ($type === 'view') {
            $updates['views_used'] = $this->views_used + 1;
        } elseif ($type === 'download') {
            $updates['downloads_used'] = $this->downloads_used + 1;
        }

        $this->update($updates);

        // Send notifications if enabled
        if (
            ($type === 'view' && $this->notify_on_access) ||
            ($type === 'download' && $this->notify_on_download)
        ) {
            $this->sendAccessNotification($type, $accessData);
        }
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function extend(int $days): void
    {
        $newExpiration = $this->expires_at 
            ? $this->expires_at->addDays($days)
            : now()->addDays($days);

        $this->update(['expires_at' => $newExpiration]);
    }

    public function canAccess(string $type = 'view'): bool
    {
        if (!$this->is_accessible) {
            return false;
        }

        $permissions = $this->specific_permissions ?: [];
        
        // Check specific permissions
        if (!empty($permissions)) {
            return in_array($type, $permissions);
        }

        // Check permission level
        $permissionHierarchy = ['view', 'download', 'comment', 'edit'];
        $userLevel = array_search($this->permission_level, $permissionHierarchy);
        $requiredLevel = array_search($type, $permissionHierarchy);

        return $userLevel >= $requiredLevel;
    }

    protected function sendAccessNotification(string $type, array $accessData): void
    {
        $email = $this->notification_email ?: $this->sharedBy->email;
        
        if (!$email) {
            return;
        }

        // TODO: Implement notification system
        // This would typically dispatch a notification job
        // dispatch(new SendShareAccessNotification($this, $type, $accessData, $email));
    }

    // Boot method to automatically generate token
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($share) {
            if (!$share->share_token && ($share->allow_public_access || $share->shared_with_email)) {
                $share->share_token = Str::random(64);
            }
        });
    }
}