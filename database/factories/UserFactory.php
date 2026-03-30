<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'), // Default password for testing
            'role' => fake()->randomElement(['tenant_admin', 'operator', 'viewer']),
            'permissions' => [],
            'phone' => fake()->phoneNumber(),
            'timezone' => 'Africa/Nairobi',
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // STATE DEFINITIONS (For Testing Different User Types)
    // ──────────────────────────────────────────────────────────────────────

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => null, // Super admins aren't tied to a tenant
            'role' => 'super_admin',
            'permissions' => ['*'], // All permissions
        ]);
    }

    public function tenantAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'tenant_admin',
            'permissions' => ['users.manage', 'routers.manage', 'packages.manage'],
        ]);
    }

    public function operator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'operator',
            'permissions' => ['vouchers.create', 'payments.view'],
        ]);
    }

    public function viewer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'viewer',
            'permissions' => ['dashboard.view'],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}