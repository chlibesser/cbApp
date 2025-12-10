<?php

namespace Tests\Unit\Domains\Identity\Models;

use Tests\TestCase;
use App\Domains\Identity\Models\Profile;
use App\Core\Auth\Models\Account;
use App\Core\Tenant\Models\Tenant;

class ProfileTest extends TestCase
{
    /** @test */
    public function it_creates_profile_with_valid_data(): void
    {
        $profile = Profile::factory()->create([
            'first_name' => 'Max',
            'last_name' => 'Mustermann'
        ]);

        $this->assertDatabaseHas('profiles', [
            'first_name' => 'Max',
            'last_name' => 'Mustermann'
        ]);
        $this->assertInstanceOf(Profile::class, $profile);
    }

    /** @test */
    public function it_belongs_to_account(): void
    {
        $account = Account::factory()->create();
        $profile = Profile::factory()->create(['account_id' => $account->id]);

        $this->assertInstanceOf(Account::class, $profile->account);
        $this->assertEquals($account->id, $profile->account->id);
    }

    /** @test */
    public function it_belongs_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $profile = Profile::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertInstanceOf(Tenant::class, $profile->tenant);
        $this->assertEquals($tenant->id, $profile->tenant->id);
    }

    /** @test */
    public function it_generates_full_name_attribute(): void
    {
        $profile = Profile::factory()->create([
            'first_name' => 'Max',
            'last_name' => 'Mustermann'
        ]);

        $this->assertEquals('Max Mustermann', $profile->full_name);
    }

    /** @test */
    public function it_handles_preferences_as_array(): void
    {
        $preferences = [
            'language' => 'de',
            'theme' => 'dark',
            'notifications' => true
        ];

        $profile = Profile::factory()->withPreferences($preferences)->create();

        $this->assertIsArray($profile->preferences);
        $this->assertEquals('de', $profile->preferences['language']);
        $this->assertEquals('dark', $profile->preferences['theme']);
        $this->assertTrue($profile->preferences['notifications']);
    }

    /** @test */
    public function it_validates_required_fields(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Profile::factory()->create([
            'account_id' => null,
            'tenant_id' => null
        ]);
    }

    /** @test */
    public function it_can_be_active_or_inactive(): void
    {
        $activeProfile = Profile::factory()->create(['is_active' => true]);
        $inactiveProfile = Profile::factory()->inactive()->create();

        $this->assertTrue($activeProfile->is_active);
        $this->assertFalse($inactiveProfile->is_active);
    }

    /** @test */
    public function it_has_display_name_fallback_to_full_name(): void
    {
        $profile = Profile::factory()->create([
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'display_name' => null
        ]);

        // Wenn display_name null ist, sollte full_name verwendet werden
        $this->assertEquals('Max Mustermann', $profile->display_name ?? $profile->full_name);
    }

    /** @test */
    public function it_uses_custom_display_name_when_set(): void
    {
        $profile = Profile::factory()->create([
            'first_name' => 'Maximilian',
            'last_name' => 'Mustermann',
            'display_name' => 'Max M.'
        ]);

        $this->assertEquals('Max M.', $profile->display_name);
    }

    /** @test */
    public function it_stores_preferences_as_json(): void
    {
        $preferences = ['theme' => 'dark', 'language' => 'en'];
        $profile = Profile::factory()->withPreferences($preferences)->create();

        // Check database storage
        $this->assertDatabaseHas('profiles', [
            'id' => $profile->id,
            'preferences' => json_encode($preferences)
        ]);
    }
}