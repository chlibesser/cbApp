<?php

namespace Tests\Feature\Domains\Identity\Controllers;

use Tests\TestCase;
use App\Domains\Identity\Models\Profile;
use App\Core\Auth\Models\Account;
use App\Core\Tenant\Models\Tenant;

class ProfileControllerTest extends TestCase
{
    /** @test */
    public function it_gets_profiles_for_authenticated_account(): void
    {
        $account = Account::factory()->create();
        $tenant = Tenant::factory()->create();
        
        $profile = Profile::factory()->create([
            'account_id' => $account->id,
            'tenant_id' => $tenant->id
        ]);

        $response = $this->actingAsAccount($account)
                         ->getJson('/api/profile/profiles');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'profiles' => [
                         '*' => [
                             'id',
                             'first_name',
                             'last_name',
                             'display_name',
                             'tenant' => [
                                 'id',
                                 'name'
                             ]
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function it_switches_to_valid_profile(): void
    {
        $account = Account::factory()->create();
        $tenant = Tenant::factory()->create();
        
        $profile = Profile::factory()->create([
            'account_id' => $account->id,
            'tenant_id' => $tenant->id
        ]);

        $response = $this->actingAsAccount($account)
                         ->postJson('/api/profile/switch', [
                             'profile_id' => $profile->id
                         ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Profil erfolgreich gewechselt',
                     'profile' => [
                         'id' => $profile->id,
                         'first_name' => $profile->first_name,
                         'last_name' => $profile->last_name
                     ]
                 ]);
    }

    /** @test */
    public function it_rejects_switch_to_invalid_profile(): void
    {
        $account = Account::factory()->create();
        $otherAccount = Account::factory()->create();
        $tenant = Tenant::factory()->create();
        
        $otherProfile = Profile::factory()->create([
            'account_id' => $otherAccount->id,
            'tenant_id' => $tenant->id
        ]);

        $response = $this->actingAsAccount($account)
                         ->postJson('/api/profile/switch', [
                             'profile_id' => $otherProfile->id
                         ]);

        $response->assertStatus(403)
                 ->assertJson([
                     'message' => 'Zugriff auf dieses Profil nicht erlaubt'
                 ]);
    }

    /** @test */
    public function it_returns_current_profile(): void
    {
        $account = Account::factory()->create();
        $tenant = Tenant::factory()->create();
        
        $profile = Profile::factory()->create([
            'account_id' => $account->id,
            'tenant_id' => $tenant->id
        ]);

        // Mock session with current profile
        session(['current_profile_id' => $profile->id]);

        $response = $this->actingAsAccount($account)
                         ->getJson('/api/profile/current');

        $response->assertStatus(200)
                 ->assertJson([
                     'profile' => [
                         'id' => $profile->id,
                         'first_name' => $profile->first_name,
                         'last_name' => $profile->last_name,
                         'tenant' => [
                             'id' => $tenant->id,
                             'name' => $tenant->name
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function it_returns_null_when_no_current_profile(): void
    {
        $account = Account::factory()->create();

        $response = $this->actingAsAccount($account)
                         ->getJson('/api/profile/current');

        $response->assertStatus(200)
                 ->assertJson([
                     'profile' => null
                 ]);
    }

    /** @test */
    public function it_updates_profile_information(): void
    {
        $account = Account::factory()->create();
        $tenant = Tenant::factory()->create();
        
        $profile = Profile::factory()->create([
            'account_id' => $account->id,
            'tenant_id' => $tenant->id,
            'first_name' => 'Max'
        ]);

        $updateData = [
            'first_name' => 'Maximilian',
            'last_name' => 'Mustermann',
            'display_name' => 'Max M.',
            'preferences' => [
                'theme' => 'dark',
                'language' => 'en'
            ]
        ];

        $response = $this->actingAsAccount($account)
                         ->putJson("/api/profile/{$profile->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Profil erfolgreich aktualisiert',
                     'profile' => [
                         'id' => $profile->id,
                         'first_name' => 'Maximilian',
                         'last_name' => 'Mustermann',
                         'display_name' => 'Max M.'
                     ]
                 ]);

        $this->assertDatabaseHas('profiles', [
            'id' => $profile->id,
            'first_name' => 'Maximilian'
        ]);
    }

    /** @test */
    public function it_validates_profile_update_data(): void
    {
        $account = Account::factory()->create();
        $profile = Profile::factory()->create(['account_id' => $account->id]);

        $response = $this->actingAsAccount($account)
                         ->putJson("/api/profile/{$profile->id}", [
                             'first_name' => '', // Invalid
                             'last_name' => 'M' // Too short
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['first_name', 'last_name']);
    }

    /** @test */
    public function it_requires_authentication_for_all_endpoints(): void
    {
        // Test profiles endpoint
        $response = $this->getJson('/api/profile/profiles');
        $response->assertStatus(401);

        // Test current profile endpoint
        $response = $this->getJson('/api/profile/current');
        $response->assertStatus(401);

        // Test switch endpoint
        $response = $this->postJson('/api/profile/switch', ['profile_id' => 'uuid']);
        $response->assertStatus(401);

        // Test update endpoint
        $response = $this->putJson('/api/profile/uuid', []);
        $response->assertStatus(401);
    }
}