<?php

namespace App\Domains\Identity\Models;

use App\Core\Auth\Models\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Support\Str;

/**
 * Profile Model - Tenant-specific identity
 */
class Profile extends Model
{
    use HasFactory;
    
    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The "type" of the auto-incrementing ID.
     */
    protected $keyType = 'string';
    
    protected $fillable = [
        'account_id',
        'tenant_id',
        'display_name',
        'first_name',
        'last_name',
        'avatar',
        'preferences',
    ];

    protected $casts = [
        'preferences' => 'array',
    ];

    /**
     * Generate UUID for new models
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the account this profile belongs to
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the tenant this profile belongs to
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the role assigned to the account for this tenant
     */
    public function role()
    {
        return $this->account->getRoleForTenant($this->tenant_id);
    }

    /**
     * Get the full name
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}