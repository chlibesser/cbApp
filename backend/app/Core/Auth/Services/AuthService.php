<?php

namespace App\Core\Auth\Services;

use App\Core\Auth\Models\Account;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

        // Versuche Account zu finden
        $account = $this->findAccountByIdentifier($identifier);

        if (!$account) {
            return null;
        }

        // Prüfe Account Status
        if (!$account->is_active) {
            return null;
        }

        // Prüfe E-Mail Verifizierung
        if (!$account->email_verified_at) {
            return null;
        }

        // Prüfe Passwort
        if (!Hash::check($password, $account->password)) {
            return null;
        }

        return $account;
    }

    /**
     * Create API token for account
     */
    public function createToken(Account $account, string $tokenName = 'API Token'): string
    {
        return $account->createToken($tokenName)->plainTextToken;
    }

    /**
     * Logout account (revoke current token)
     */
    public function logout(Account $account): void
    {
        // Revoke only the current access token
        $account->currentAccessToken()?->delete();
    }

    /**
     * Revoke all tokens for account
     */
    public function revokeAllTokens(Account $account): void
    {
        $account->tokens()->delete();
    }

    /**
     * Create a new account
     */
    public function register(array $data): Account
    {
        // Validiere die Registrierungsdaten
        $validator = Validator::make($data, [
            'username' => 'required|string|min:3|unique:accounts,username',
            'email' => 'required|email|unique:accounts,email',
            'password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return Account::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
        ]);
    }

    /**
     * Find account by username or email
     */
    public function findAccountByIdentifier(string $identifier): ?Account
    {
        return Account::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();
    }

    /**
     * Check if password is strong enough
     */
    public function isStrongPassword(string $password): bool
    {
        // Mindestens 8 Zeichen
        if (strlen($password) < 8) {
            return false;
        }

        // Mindestens ein Großbuchstabe, ein Kleinbuchstabe und eine Zahl
        $hasUpperCase = preg_match('/[A-Z]/', $password);
        $hasLowerCase = preg_match('/[a-z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);

        return $hasUpperCase && $hasLowerCase && $hasNumber;
    }

    /**
     * Generate a secure random token
     */
    public function generateSecureToken(): string
    {
        return Str::random(64);
    }

    /**
     * Check if account is active
     */
    public function isAccountActive(Account $account): bool
    {
        return $account->is_active;
    }
}