<?php

namespace App\Core\Auth\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Custom PersonalAccessToken Model with UUID support
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasUuids;
}