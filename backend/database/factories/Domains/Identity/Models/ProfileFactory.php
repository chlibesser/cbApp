<?php

namespace Database\Factories\Domains\Identity\Models;

use App\Core\Auth\Models\Account;
use App\Domains\Identity\Models\Profile;
use App\Core\Tenant\Models\Tenant;
use App\Core\Tenant\Enums\TenantRole;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Profile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'account_id' => Account::factory(),
            'tenant_id' => Tenant::factory(),
            'tenant_role' => TenantRole::MEMBER->value,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'display_name' => null,
            'bio' => fake()->optional()->paragraph(),
            'avatar_url' => fake()->optional()->imageUrl(200, 200, 'people'),
            'preferences' => json_encode([
                'theme' => 'light',
                'language' => 'de',
                'notifications' => true
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the profile has admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_role' => TenantRole::ADMIN->value,
        ]);
    }

    /**
     * Indicate that the profile has owner role.
     */
    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_role' => TenantRole::OWNER->value,
        ]);
    }

    /**
     * Configure the factory to create a profile with specific preferences.
     */
    public function withPreferences(array $preferences): static
    {
        return $this->state(fn (array $attributes) => [
            'preferences' => json_encode(array_merge(
                json_decode($attributes['preferences'] ?? '{}', true),
                $preferences
            )),
        ]);
    }
}