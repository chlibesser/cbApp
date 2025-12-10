<?php

namespace Database\Factories\Core\Shared\Models;

use App\Core\Shared\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Permission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $resource = fake()->randomElement(['users', 'projects', 'reports', 'settings', 'dashboard']);
        $action = fake()->randomElement(['view', 'create', 'edit', 'delete', 'manage']);
        
        return [
            'id' => fake()->uuid(),
            'name' => "{$resource}.{$action}",
            'resource' => $resource,
            'action' => $action,
            'description' => fake()->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}