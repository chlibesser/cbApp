<?php

namespace App\Infrastructure\Http\Controllers\Admin;

use App\Core\Shared\Enums\SystemRole;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * SystemRoleController - API for system roles
 */
class SystemRoleController extends Controller
{
    /**
     * Get all system roles for select dropdown
     */
    public function index()
    {
        return response()->json([
            'roles' => SystemRole::forSelect()
        ]);
    }
}