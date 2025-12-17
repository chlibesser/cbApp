<?php

namespace App\Domains\Partner\Models;

use App\Core\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerContact extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'name',
        'position',
        'email',
        'phone',
        'is_primary',
        'responsibilities',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    protected $attributes = [
        'is_primary' => false,
    ];

    /**
     * Get the partner that the contact belongs to.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Set this contact as primary and unset others.
     */
    public function makePrimary()
    {
        // Unset all other primary contacts for this partner
        self::where('partner_id', $this->partner_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Set this contact as primary
        $this->update(['is_primary' => true]);
    }

}