<?php

namespace App\Infrastructure\Http\Controllers\Admin;

use App\Core\Auth\Models\Account;
use App\Core\Auth\Services\AuthService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * AccountController - Admin account management
 */
class AccountController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * List all accounts
     */
    public function index()
    {
        $accounts = Account::with('profiles.tenant')->paginate(20);

        return response()->json($accounts);
    }

    /**
     * Create a new account
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'unique:accounts', 'max:255'],
            'email' => ['required', 'email', 'unique:accounts'],
            'password' => ['required', 'min:8'],
        ]);

        $account = $this->authService->register($data);

        return response()->json([
            'message' => 'Account created successfully',
            'account' => $account,
        ], 201);
    }

    /**
     * Update an account
     */
    public function update(Request $request, Account $account)
    {
        $data = $request->validate([
            'username' => ['string', 'unique:accounts,username,' . $account->id, 'max:255'],
            'email' => ['email', 'unique:accounts,email,' . $account->id],
        ]);

        $account->update($data);

        return response()->json([
            'message' => 'Account updated successfully',
            'account' => $account,
        ]);
    }

    /**
     * Delete an account
     */
    public function destroy(Account $account)
    {
        $account->delete();

        return response()->json([
            'message' => 'Account deleted successfully',
        ]);
    }
}