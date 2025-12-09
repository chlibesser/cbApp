<?php

namespace App\Infrastructure\Http\Controllers\Auth;

use App\Core\Auth\Services\AuthService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * LoginController - Handle authentication
 */
class LoginController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Handle login request with username or email
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string'], // username or email
            'password' => ['required', 'string'],
        ]);

        $account = $this->authService->attempt($credentials);

        if (!$account) {
            return response()->json([
                'message' => 'Invalid credentials or account inactive',
            ], 401);
        }

        // Create API token for SPA authentication
        $token = $this->authService->createToken($account);

        return response()->json([
            'message' => 'Successfully logged in',
            'token' => $token,
            'account' => [
                'id' => $account->id,
                'username' => $account->username,
                'email' => $account->email,
                'is_active' => $account->is_active,
                'email_verified_at' => $account->email_verified_at,
            ],
        ]);
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        $account = $request->user();
        
        if ($account) {
            $this->authService->logout($account);
        }

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Get current authenticated account
     */
    public function me(Request $request)
    {
        $account = $request->user();

        if (!$account) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        return response()->json([
            'account' => [
                'id' => $account->id,
                'username' => $account->username,
                'email' => $account->email,
                'is_active' => $account->is_active,
                'email_verified_at' => $account->email_verified_at,
            ],
        ]);
    }
}