<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Core\Auth\Models\Account;
use Illuminate\Support\Facades\Hash;

class LoginTest extends TestCase
{
    /** @test */
    public function it_logs_in_with_valid_credentials(): void
    {
        $account = Account::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'token',
                     'account' => ['id', 'email', 'system_role']
                 ]);
    }

    /** @test */
    public function it_rejects_invalid_credentials(): void
    {
        Account::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password'
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'message' => 'Die Anmeldedaten sind ungültig.'
                 ]);
    }

    /** @test */
    public function it_validates_required_fields(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email', 'password']);
    }

    /** @test */
    public function it_validates_email_format(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_rejects_unverified_email(): void
    {
        Account::factory()->unverified()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'message' => 'E-Mail-Adresse muss verifiziert werden.'
                 ]);
    }

    /** @test */
    public function it_logs_out_authenticated_user(): void
    {
        $account = Account::factory()->create();
        $token = $account->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Erfolgreich abgemeldet.']);

        // Token sollte gelöscht sein
        $this->assertCount(0, $account->tokens);
    }

    /** @test */
    public function it_returns_authenticated_user_info(): void
    {
        $account = Account::factory()->create();

        $response = $this->actingAsAccount($account)
                         ->getJson('/api/auth/user');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'id', 'email', 'system_role', 'email_verified_at'
                 ]);
    }
}