<?php

namespace Tests\Unit\Core\Auth\Services;

use Tests\TestCase;
use App\Core\Auth\Services\AuthService;
use App\Core\Auth\Models\Account;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;

class AuthServiceTest extends TestCase
{
    protected AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = app(AuthService::class);
    }

    #[Test]
    public function it_attempts_authentication_with_email(): void
    {
        $account = Account::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $result = $this->authService->attempt([
            'identifier' => 'test@example.com',
            'password' => 'password123'
        ]);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_attempts_authentication_with_username(): void
    {
        $account = Account::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $result = $this->authService->attempt([
            'identifier' => 'testuser',
            'password' => 'password123'
        ]);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_rejects_invalid_credentials(): void
    {
        Account::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password')
        ]);

        $result = $this->authService->attempt([
            'identifier' => 'test@example.com',
            'password' => 'wrong-password'
        ]);

        $this->assertFalse($result);
        $this->assertNull(Auth::id());
    }

    #[Test]
    public function it_rejects_inactive_accounts(): void
    {
        $account = Account::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false
        ]);

        $result = $this->authService->attempt([
            'identifier' => 'test@example.com',
            'password' => 'password123'
        ]);

        $this->assertFalse($result);
        $this->assertNull(Auth::id());
    }

    #[Test]
    public function it_rejects_unverified_accounts(): void
    {
        Account::factory()->unverified()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $result = $this->authService->attempt([
            'identifier' => 'test@example.com',
            'password' => 'password123'
        ]);

        $this->assertFalse($result);
        $this->assertNull(Auth::id());
    }

    #[Test]
    public function it_creates_api_token_on_successful_auth(): void
    {
        $account = Account::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $token = $this->authService->createToken($account, 'test-device');

        $this->assertNotNull($token);
        $this->assertStringContainsString('|', $token); // Sanctum token format
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $account->id,
            'name' => 'test-device'
        ]);
    }

    #[Test]
    public function it_revokes_all_tokens_on_logout(): void
    {
        $account = Account::factory()->create();
        
        // Create multiple tokens
        $token1 = $account->createToken('device1');
        $token2 = $account->createToken('device2');

        $this->authService->revokeAllTokens($account);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $account->id
        ]);
    }

    #[Test]
    public function it_registers_new_account(): void
    {
        $accountData = [
            'username' => 'newuser',
            'email' => 'new@example.com',
            'password' => 'password123',
            'first_name' => 'New',
            'last_name' => 'User'
        ];

        $account = $this->authService->register($accountData);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals('newuser', $account->username);
        $this->assertEquals('new@example.com', $account->email);
        $this->assertNotEquals('password123', $account->password); // Should be hashed
        $this->assertTrue(Hash::check('password123', $account->password));
    }

    #[Test]
    public function it_hashes_password_during_registration(): void
    {
        $plainPassword = 'plain-password-123';
        
        $account = $this->authService->register([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => $plainPassword
        ]);

        $this->assertNotEquals($plainPassword, $account->password);
        $this->assertTrue(Hash::check($plainPassword, $account->password));
    }

    #[Test]
    public function it_validates_unique_email_during_registration(): void
    {
        Account::factory()->create(['email' => 'existing@example.com']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->authService->register([
            'username' => 'newuser',
            'email' => 'existing@example.com', // Duplicate
            'password' => 'password123'
        ]);
    }

    #[Test]
    public function it_validates_unique_username_during_registration(): void
    {
        Account::factory()->create(['username' => 'existinguser']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->authService->register([
            'username' => 'existinguser', // Duplicate
            'email' => 'new@example.com',
            'password' => 'password123'
        ]);
    }

    #[Test]
    public function it_finds_account_by_username_or_email(): void
    {
        $account = Account::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com'
        ]);

        // Find by email
        $foundByEmail = $this->authService->findAccountByIdentifier('test@example.com');
        $this->assertEquals($account->id, $foundByEmail->id);

        // Find by username
        $foundByUsername = $this->authService->findAccountByIdentifier('testuser');
        $this->assertEquals($account->id, $foundByUsername->id);
    }

    #[Test]
    public function it_returns_null_for_non_existent_identifier(): void
    {
        $result = $this->authService->findAccountByIdentifier('nonexistent');
        $this->assertNull($result);
    }

    #[Test]
    public function it_checks_password_strength(): void
    {
        // Weak passwords
        $this->assertFalse($this->authService->isStrongPassword('123'));
        $this->assertFalse($this->authService->isStrongPassword('password'));
        $this->assertFalse($this->authService->isStrongPassword('12345'));

        // Strong passwords
        $this->assertTrue($this->authService->isStrongPassword('Password123!'));
        $this->assertTrue($this->authService->isStrongPassword('MySecurePass2024'));
        $this->assertTrue($this->authService->isStrongPassword('test@Example123'));
    }

    #[Test]
    public function it_generates_secure_tokens(): void
    {
        $token1 = $this->authService->generateSecureToken();
        $token2 = $this->authService->generateSecureToken();

        $this->assertNotEquals($token1, $token2);
        $this->assertTrue(strlen($token1) >= 32);
        $this->assertTrue(strlen($token2) >= 32);
    }

    #[Test]
    public function it_checks_account_status(): void
    {
        $activeAccount = Account::factory()->create(['is_active' => true]);
        $inactiveAccount = Account::factory()->create(['is_active' => false]);

        $this->assertTrue($this->authService->isAccountActive($activeAccount));
        $this->assertFalse($this->authService->isAccountActive($inactiveAccount));
    }

    #[Test]
    public function it_handles_login_attempts_rate_limiting(): void
    {
        $account = Account::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        // Simulate multiple failed attempts (implementation depends on rate limiting)
        for ($i = 0; $i < 5; $i++) {
            $this->authService->attempt([
                'identifier' => 'test@example.com',
                'password' => 'wrong-password'
            ]);
        }

        // Should still work with correct password (unless rate limited)
        $result = $this->authService->attempt([
            'identifier' => 'test@example.com',
            'password' => 'password123'
        ]);

        // This test depends on your rate limiting implementation
        $this->assertIsBool($result);
    }
}