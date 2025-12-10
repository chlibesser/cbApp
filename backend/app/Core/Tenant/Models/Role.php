<?php

namespace App\Core\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Role extends Model
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
        'tenant_id',
        'name',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
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
     * Get the tenant that owns this role
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get all permissions for this role
     */
    public function permissions()
    {
        return $this->belongsToMany(\App\Core\Shared\Models\Permission::class, 'role_permissions');
    }

    /**
     * Get all accounts with this role
     */
    public function accounts()
    {
        return $this->belongsToMany(\App\Core\Auth\Models\Account::class, 'tenant_user');
    }

    /**
     * Scope to get only system roles
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to get only custom roles
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }
}
