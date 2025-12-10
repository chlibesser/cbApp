<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Core\Auth\Models\Account;
use App\Core\Tenant\Models\Tenant;
use App\Domains\Identity\Models\Profile;

class QuickLoginTest extends TestCase
{
    /** @test */
    public function it_lists_available_quick_login_accounts(): void
    {
        // Erstelle Test-Accounts
        $globalAdmin = Account::factory()->globalAdmin()->create(['username' => 'admin']);
        $user = Account::factory()->create(['username' => 'testuser']);
        
        $response = $this->getJson('/api/auth/quick-login/accounts');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'accounts' => [
                         '*' => [
                             'username',
                             'email',
                             'system_role',
                             'tenants'
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function it_performs_quick_login_with_username(): void
    {
        $account = Account::factory()->globalAdmin()->create(['username' => 'admin']);

        $response = $this->postJson('/api/auth/quick-login', [
            'username' => 'admin'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'token',
                     'account' => ['id', 'email', 'system_role']
                 ]);
    }

    /** @test */
    public function it_rejects_quick_login_in_production(): void
    {
        config(['app.env' => 'production']);
        
        $response = $this->postJson('/api/auth/quick-login', [
            'username' => 'admin'
        ]);

        $response->assertStatus(403)
                 ->assertJson(['message' => 'Quick Login nur in Development verfügbar']);
    }
}