<?php

namespace App\Core\Auth\Services;

use App\Core\Auth\Models\Account;
use Illuminate\Support\Facades\Hash;

/**
 * AuthService - Core Authentication Service
 */
class AuthService
{
    /**
     * Attempt to authenticate an account with username or email
     */
    public function attempt(array $credentials): ?Account
    {
        $identifier = $credentials['username'] ?? $credentials['email'] ?? $credentials['identifier'];
        $password = $credentials['password'];

        $account = Account::findByUsernameOrEmail($identifier);

        if ($account && $account->isActive() && Hash::check($password, $account->password)) {
            return $account;
        }

        return null;
    }

    /**
     * Create API token for account
     */
    public function createToken(Account $account, string $tokenName = 'API Token'): string
    {
        return $account->createToken($tokenName)->plainTextToken;
    }

    /**
     * Revoke all tokens for account
     */
    public function logout(Account $account): void
    {
        $account->tokens()->delete();
    }

    /**
     * Create a new account
     */
    public function register(array $data): Account
    {
        return Account::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }
}