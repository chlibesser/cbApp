<?php

namespace App\Domains\Partner\Models;

use App\Core\Shared\Models\BaseModel;
use App\Core\Auth\Models\Account;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerInteraction extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'user_id',
        'type',
        'subject',
        'content',
        'interaction_date',
        'participants',
        'attachments',
        'follow_up_date',
    ];

    protected $casts = [
        'interaction_date' => 'datetime',
        'follow_up_date' => 'date',
        'participants' => 'array',
        'attachments' => 'array',
    ];

    protected $attributes = [
        'type' => 'note',
        'participants' => '[]',
        'attachments' => '[]',
    ];

    /**
     * Get the partner that the interaction belongs to.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the user that created the interaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'user_id');
    }

    /**
     * Scope a query to only include interactions with follow-ups.
     */
    public function scopeWithFollowUp($query)
    {
        return $query->whereNotNull('follow_up_date');
    }

    /**
     * Scope a query to only include overdue follow-ups.
     */
    public function scopeOverdueFollowUps($query)
    {
        return $query->whereNotNull('follow_up_date')
                    ->where('follow_up_date', '<', now());
    }

}