<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Core\Auth\Models\Account;
use App\Core\Shared\Enums\SystemRole;
use Illuminate\Support\Facades\Hash;

class AccountTest extends TestCase
{
    /** @test */
    public function it_creates_account_with_valid_data(): void
    {
        $account = Account::factory()->create([
            'email' => 'test@example.com'
        ]);

        $this->assertDatabaseHas('accounts', [
            'email' => 'test@example.com'
        ]);
        $this->assertInstanceOf(Account::class, $account);
    }

    /** @test */
    public function it_hashes_password_automatically(): void
    {
        $account = Account::factory()->create([
            'password' => 'plaintext-password'
        ]);

        $this->assertTrue(Hash::check('plaintext-password', $account->password));
    }

    /** @test */
    public function it_casts_system_role_to_enum(): void
    {
        $account = Account::factory()->globalAdmin()->create();

        $this->assertInstanceOf(SystemRole::class, $account->system_role);
        $this->assertEquals(SystemRole::GLOBAL_ADMIN, $account->system_role);
    }

    /** @test */
    public function it_allows_null_system_role_for_regular_accounts(): void
    {
        $account = Account::factory()->create();

        $this->assertNull($account->system_role);
    }

    /** @test */
    public function it_validates_email_uniqueness(): void
    {
        Account::factory()->create(['email' => 'test@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Account::factory()->create(['email' => 'test@example.com']);
    }

    /** @test */
    public function it_has_tenants_relationship(): void
    {
        $account = Account::factory()->create();
        $tenant = \App\Core\Tenant\Models\Tenant::factory()->create();

        $account->tenants()->attach($tenant->id);

        $this->assertTrue($account->tenants->contains($tenant));
    }

    /** @test */
    public function it_checks_if_account_is_global_admin(): void
    {
        $globalAdmin = Account::factory()->globalAdmin()->create();
        $regularAccount = Account::factory()->create();

        $this->assertTrue($globalAdmin->isGlobalAdmin());
        $this->assertFalse($regularAccount->isGlobalAdmin());
    }
}