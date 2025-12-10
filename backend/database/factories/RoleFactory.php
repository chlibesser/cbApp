<?php

namespace Database\Factories;

use App\Core\Tenant\Models\Role;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->randomElement(['admin', 'manager', 'member', 'viewer']),
            'label' => $this->faker->jobTitle(),
            'description' => $this->faker->sentence(),
            'is_system_role' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function systemRole(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system_role' => true,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'admin',
            'label' => 'Administrator',
            'is_system_role' => true,
        ]);
    }

    public function member(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'member',
            'label' => 'Mitarbeiter',
        ]);
    }
}