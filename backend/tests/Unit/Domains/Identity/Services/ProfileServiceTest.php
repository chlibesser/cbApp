<?php

namespace Tests\Unit\Domains\Identity\Services;

use Tests\TestCase;
use App\Domains\Identity\Services\ProfileService;
use App\Domains\Identity\Models\Profile;
use App\Core\Auth\Models\Account;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProfileService $profileService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->profileService = app(ProfileService::class);
    }

    /** @test */
    public function it_creates_profile_for_account_and_tenant(): void
    {
        $account = Account::factory()->create();
        $tenant = Tenant::factory()->create();

        $profileData = [
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'display_name' => 'Max M.',
            'preferences' => [
                'language' => 'de',
                'theme' => 'light'
            ]
        ];

        $profile = $this->profileService->createProfile($account, $tenant, $profileData);

        $this->assertInstanceOf(Profile::class, $profile);
        $this->assertEquals($account->id, $profile->account_id);
        $this->assertEquals($tenant->id, $profile->tenant_id);
        $this->assertEquals('Max', $profile->first_name);
        $this->assertEquals('Mustermann', $profile->last_name);
        $this->assertEquals('Max M.', $profile->display_name);
        $this->assertEquals(['language' => 'de', 'theme' => 'light'], $profile->preferences);
    }

    /** @test */
    public function it_gets_all_profiles_for_account(): void
    {
        $account = Account::factory()->create();
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $profile1 = Profile::factory()->create([
            'account_id' => $account->id,
            'tenant_id' => $tenant1->id
        ]);
        $profile2 = Profile::factory()->create([
            'account_id' => $account->id,
            'tenant_id' => $tenant2->id
        ]);

        // Anderer Account - sollte nicht in Ergebnis sein
        $otherAccount = Account::factory()->create();
        Profile::factory()->create(['account_id' => $otherAccount->id]);

        $profiles = $this->profileService->getProfilesForAccount($account);

        $this->assertCount(2, $profiles);
        $this->assertTrue($profiles->contains($profile1));
        $this->assertTrue($profiles->contains($profile2));
    }

    /** @test */
    public function it_updates_profile_data(): void
    {
        $profile = Profile::factory()->create([
            'first_name' => 'Max',
            'preferences' => ['theme' => 'light']
        ]);

        $updateData = [
            'first_name' => 'Maximilian',
            'preferences' => ['theme' => 'dark', 'language' => 'en']
        ];

        $updatedProfile = $this->profileService->updateProfile($profile, $updateData);

        $this->assertEquals('Maximilian', $updatedProfile->first_name);
        $this->assertEquals(['theme' => 'dark', 'language' => 'en'], $updatedProfile->preferences);
    }

    /** @test */
    public function it_switches_active_profile_for_account(): void
    {
        $account = Account::factory()->create();
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $profile1 = Profile::factory()->create([
            'account_id' => $account->id,
            'tenant_id' => $tenant1->id
        ]);
        $profile2 = Profile::factory()->create([
            'account_id' => $account->id,
            'tenant_id' => $tenant2->id
        ]);

        $result = $this->profileService->switchProfile($account, $profile2);

        $this->assertTrue($result);
        // Hier würde normalerweise Session-Storage getested werden
        // Das ist aber infrastruktur-abhängig
    }

    /** @test */
    public function it_rejects_profile_switch_for_different_account(): void
    {
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();
        $tenant = Tenant::factory()->create();

        $profile = Profile::factory()->create([
            'account_id' => $account2->id,
            'tenant_id' => $tenant->id
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Profile gehört nicht zu diesem Account');

        $this->profileService->switchProfile($account1, $profile);
    }

    /** @test */
    public function it_gets_current_profile_from_session(): void
    {
        $account = Account::factory()->create();
        $profile = Profile::factory()->create(['account_id' => $account->id]);

        // Mock session storage
        session(['current_profile_id' => $profile->id]);

        $currentProfile = $this->profileService->getCurrentProfile($account);

        $this->assertInstanceOf(Profile::class, $currentProfile);
        $this->assertEquals($profile->id, $currentProfile->id);
    }

    /** @test */
    public function it_returns_null_when_no_current_profile(): void
    {
        $account = Account::factory()->create();

        $currentProfile = $this->profileService->getCurrentProfile($account);

        $this->assertNull($currentProfile);
    }

    /** @test */
    public function it_activates_profile(): void
    {
        $profile = Profile::factory()->inactive()->create();

        $activatedProfile = $this->profileService->activateProfile($profile);

        $this->assertTrue($activatedProfile->is_active);
    }

    /** @test */
    public function it_deactivates_profile(): void
    {
        $profile = Profile::factory()->create(['is_active' => true]);

        $deactivatedProfile = $this->profileService->deactivateProfile($profile);

        $this->assertFalse($deactivatedProfile->is_active);
    }

    /** @test */
    public function it_validates_profile_data(): void
    {
        $account = Account::factory()->create();
        $tenant = Tenant::factory()->create();

        $invalidData = [
            'first_name' => '', // Leer
            'last_name' => 'M', // Zu kurz
            'preferences' => 'invalid' // Nicht array
        ];

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->profileService->createProfile($account, $tenant, $invalidData);
    }
}