<?php

namespace App\Infrastructure\Http\Middleware;

use App\Core\Shared\Enums\SystemRole;
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
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check if user has super admin or tenant admin role
        // This is a simplified check - you would implement proper role checking
        $currentProfile = session('current_profile');
        
        if (!$currentProfile || !$this->isAdmin($currentProfile)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        return $next($request);
    }

    /**
     * Check if profile has admin privileges
     */
    private function isAdmin($profile): bool
    {
        // Implement your admin check logic here
        // This is a placeholder implementation
        return $profile->roles()
            ->whereIn('slug', [
                SystemRole::SUPER_ADMIN->value,
                SystemRole::TENANT_ADMIN->value,
            ])
            ->exists();
    }
}