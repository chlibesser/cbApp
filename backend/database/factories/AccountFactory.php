<?php

namespace Database\Factories;

use App\Core\Auth\Models\Account;
use App\Core\Shared\Enums\SystemRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password123'),
            'system_role' => null, // Standard Account ohne System-Rolle
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function globalAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'system_role' => SystemRole::GLOBAL_ADMIN,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withEmail(string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => $email,
        ]);
    }
}