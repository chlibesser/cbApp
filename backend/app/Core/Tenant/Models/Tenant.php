<?php

namespace App\Core\Tenant\Models;
use App\Core\Localization\Enums\SupportedLocale;
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
        'default_locale',      // Tenant's default language
        'allowed_locales',     // JSON array of allowed languages
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'is_personal' => 'boolean',
        'allowed_locales' => 'array',  // Auto JSON conversion
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
    /**
     * Get tenant's default locale with enum validation
     * Falls back to system default if invalid
     */
    public function getDefaultLocale(): string
    {
        if ($this->default_locale && SupportedLocale::isSupported($this->default_locale)) {
            return $this->default_locale;
        }

        return SupportedLocale::getDefault()->value;
    }

    /**
     * Check if a locale is allowed in this tenant
     * Uses enum validation + tenant allowed list
     */
    public function isLocaleAllowed(string $locale): bool
    {
        // First check if locale is supported by system
        if (!SupportedLocale::isSupported($locale)) {
            return false;
        }

        // If no allowed locales set, allow all supported locales
        if (empty($this->allowed_locales)) {
            return true;
        }

        // Check if locale is in tenant's allowed list
        return in_array($locale, $this->allowed_locales);
    }

    /**
     * Get tenant's allowed locales with display information
     * Returns only allowed locales with their config data
     */
    public function getAllowedLocalesWithInfo(): array
    {
        $allowedCodes = $this->allowed_locales ?? SupportedLocale::getCodes();
        $result = [];

        foreach ($allowedCodes as $code) {
            if (SupportedLocale::isSupported($code)) {
                $locale = SupportedLocale::from($code);
                $result[] = [
                    'code' => $locale->value,
                    'name' => $locale->getDisplayName(),
                    'native' => $locale->getNativeName(),
                    'flag' => $locale->getFlagCode(),
                ];
            }
        }

        return $result;
    }
}
