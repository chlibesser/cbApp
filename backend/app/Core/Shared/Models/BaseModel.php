<?php

namespace App\Core\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BaseModel - Base model for all entities
 */
abstract class BaseModel extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be cast to dates.
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}