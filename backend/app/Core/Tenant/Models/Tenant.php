<?php

namespace App\Core\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

/**
 * Tenant Model - Multi-tenancy core
 */
class Tenant extends Model
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
        'name',
        'slug',
        'description',
        'logo_url',
        'is_personal',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'is_personal' => 'boolean',
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
     * Get all profiles belonging to this tenant
     */
    public function profiles()
    {
        return $this->hasMany(\App\Domains\Identity\Models\Profile::class);
    }

    /**
     * Get all accounts belonging to this tenant
     */
    public function accounts()
    {
        return $this->belongsToMany(\App\Core\Auth\Models\Account::class, 'tenant_user')
                    ->withPivot('role_id')
                    ->withTimestamps();
    }

    /**
     * Get all roles belonging to this tenant
     */
    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    /**
     * Scope to get only personal tenants
     */
    public function scopePersonal($query)
    {
        return $query->where('is_personal', true);
    }

    /**
     * Scope to get only company tenants
     */
    public function scopeCompany($query)
    {
        return $query->where('is_personal', false);
    }
}