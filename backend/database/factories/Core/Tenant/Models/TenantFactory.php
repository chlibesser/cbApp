<?php

namespace Database\Factories\Core\Tenant\Models;

use App\Core\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $companyName = fake()->company();
        
        return [
            'id' => fake()->uuid(),
            'name' => $companyName,
            'slug' => Str::slug($companyName),
            'description' => fake()->optional()->paragraph(),
            'is_active' => true,
            'settings' => json_encode([
                'timezone' => 'Europe/Zurich',
                'language' => 'de',
                'date_format' => 'd.m.Y',
                'time_format' => 'H:i',
                'currency' => 'CHF'
            ]),
            'logo_url' => fake()->optional()->imageUrl(200, 200, 'business'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the tenant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Configure the factory to create a tenant with specific settings.
     */
    public function withSettings(array $settings): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => json_encode(array_merge(
                json_decode($attributes['settings'], true),
                $settings
            )),
        ]);
    }
}