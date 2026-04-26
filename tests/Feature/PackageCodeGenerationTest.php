<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageCodeGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_requested_code_is_rewritten_before_insert(): void
    {
        $tenant = $this->createTenant();

        $first = Package::query()->create($this->packagePayload($tenant, [
            'name' => '12hrs@20',
            'code' => '12-20',
        ]));

        $second = Package::query()->create($this->packagePayload($tenant, [
            'name' => '12hrs@20',
            'code' => '12-20',
        ]));

        $this->assertSame('12-20', $first->code);
        $this->assertNotSame($first->code, $second->code);
        $this->assertStringStartsWith('12-20-', $second->code);
    }

    public function test_missing_code_uses_name_based_slug(): void
    {
        $tenant = $this->createTenant();

        $package = Package::query()->create($this->packagePayload($tenant, [
            'name' => '12hrs@20',
            'code' => '',
        ]));

        $this->assertSame('12HRS-20', $package->code);
    }

    private function createTenant(array $overrides = []): Tenant
    {
        return Tenant::query()->create(array_merge([
            'name' => 'Test Tenant',
            'subdomain' => 'tenant-' . str()->random(6),
            'contact_email' => 'tenant@example.com',
            'contact_phone' => '0712345678',
            'timezone' => 'Africa/Nairobi',
            'currency' => 'KES',
            'status' => 'active',
            'plan' => 'starter',
            'monthly_fee' => 0,
            'billing_cycle_start' => now()->toDateString(),
            'next_billing_date' => now()->addMonth()->toDateString(),
            'max_routers' => 1,
            'max_users' => 100,
        ], $overrides));
    }

    private function packagePayload(Tenant $tenant, array $overrides = []): array
    {
        return array_merge([
            'tenant_id' => $tenant->id,
            'name' => '1 Hour Pass',
            'description' => 'Fast test package',
            'code' => '',
            'price' => 20,
            'currency' => 'KES',
            'duration_value' => 12,
            'duration_unit' => 'hours',
            'download_limit_mbps' => 8,
            'upload_limit_mbps' => 6,
            'is_active' => true,
            'sort_order' => 1,
        ], $overrides);
    }
}
