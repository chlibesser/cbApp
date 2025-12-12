<?php

namespace App\Domains\Tenant\Models;

use App\Core\Auth\Models\Account;
use App\Core\Shared\Models\BaseModel;
use App\Domains\Tenant\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invitation extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'profile_id',
        'email',
        'token',
        'status',
        'invited_by',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'status' => InvitationStatus::class,
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $invitation) {
            if (!$invitation->token) {
                $invitation->token = Str::random(64);
            }
            
            if (!$invitation->expires_at) {
                $invitation->expires_at = now()->addDays(7);
            }
            
            if (!$invitation->status) {
                $invitation->status = InvitationStatus::PENDING;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === InvitationStatus::PENDING && !$this->isExpired();
    }

    public function isAccepted(): bool
    {
        return $this->status === InvitationStatus::ACCEPTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === InvitationStatus::CANCELLED;
    }

    public function accept(): void
    {
        $this->update([
            'status' => InvitationStatus::ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => InvitationStatus::CANCELLED,
        ]);
    }

    public function expire(): void
    {
        $this->update([
            'status' => InvitationStatus::EXPIRED,
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', InvitationStatus::PENDING)
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
            ->where('status', InvitationStatus::PENDING);
    }

    public function getInvitationUrlAttribute(): string
    {
        return config('app.frontend_url') . '/invitations/' . $this->token;
    }
}