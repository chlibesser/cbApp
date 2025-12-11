<?php

namespace App\Infrastructure\Http\Middleware;

use App\Core\Auth\Enums\SystemRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AdminOnly Middleware - Restrict access to admins
 */
class AdminOnly
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if user has admin system role
        if (!$this->isAdmin($user)) {
            return response()->json([
                'message' => 'Keine Berechtigung. Nur Administratoren haben Zugriff.',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if account has admin privileges
     */
    private function isAdmin($account): bool
    {
        $systemRole = $account->system_role;
        
        // If it's an Enum, use the built-in isAdmin method
        if ($systemRole instanceof SystemRole) {
            return $systemRole->isAdmin() || $systemRole->isTenantAdmin();
        }
        
        // Fallback for string values (legacy)
        return in_array($systemRole, ['admin', 'tenant_admin']);
    }
}