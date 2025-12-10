<?php

namespace Database\Factories;

use App\Domains\Identity\Models\Profile;
use App\Core\Auth\Models\Account;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'account_id' => Account::factory(),
            'tenant_id' => Tenant::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'display_name' => $this->faker->name(),
            'preferences' => json_encode([
                'language' => 'de',
                'timezone' => 'Europe/Zurich',
                'theme' => 'light'
            ]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withPreferences(array $preferences): static
    {
        return $this->state(fn (array $attributes) => [
            'preferences' => json_encode($preferences),
        ]);
    }
}