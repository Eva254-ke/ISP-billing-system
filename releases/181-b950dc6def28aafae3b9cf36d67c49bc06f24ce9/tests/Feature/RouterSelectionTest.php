<?php

namespace Tests\Feature;

use App\Models\Router;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouterSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_selection_candidates_collapse_stale_public_ip_duplicates(): void
    {
        $tenant = $this->createTenant();

        $stale = $this->createRouter($tenant, [
            'name' => 'Old Public Router',
            'ip_address' => '102.213.48.202',
            'api_port' => 18728,
            'api_username' => 'admin',
            'status' => Router::STATUS_OFFLINE,
        ]);

        $canonical = $this->createRouter($tenant, [
            'name' => 'Canonical Public Router',
            'ip_address' => '102.213.48.202',
            'api_port' => 8728,
            'api_username' => 'cloudbridge',
            'status' => Router::STATUS_WARNING,
            'last_seen_at' => now(),
        ]);

        $other = $this->createRouter($tenant, [
            'name' => 'Second Router',
            'ip_address' => '41.78.188.129',
            'api_port' => 8728,
            'status' => Router::STATUS_ONLINE,
            'last_seen_at' => now()->subMinute(),
        ]);

        $candidates = Router::selectionCandidatesForTenant($tenant->id);

        $this->assertCount(2, $candidates);
        $this->assertTrue($candidates->contains(fn (Router $router): bool => (int) $router->id === (int) $canonical->id));
        $this->assertTrue($candidates->contains(fn (Router $router): bool => (int) $router->id === (int) $other->id));
        $this->assertFalse($candidates->contains(fn (Router $router): bool => (int) $router->id === (int) $stale->id));
    }

    public function test_canonical_record_for_stale_duplicate_returns_newest_matching_router(): void
    {
        $tenant = $this->createTenant();

        $stale = $this->createRouter($tenant, [
            'name' => 'Old Public Router',
            'ip_address' => '102.213.48.202',
            'api_port' => 18728,
            'api_username' => 'admin',
            'status' => Router::STATUS_OFFLINE,
        ]);

        $canonical = $this->createRouter($tenant, [
            'name' => 'Canonical Public Router',
            'ip_address' => '102.213.48.202',
            'api_port' => 8728,
            'api_username' => 'cloudbridge',
            'status' => Router::STATUS_WARNING,
            'last_seen_at' => now(),
        ]);

        $resolved = Router::resolveCanonicalRecord($stale);

        $this->assertNotNull($resolved);
        $this->assertSame($canonical->id, $resolved?->id);
    }

    public function test_preferred_router_ignores_older_duplicate_even_if_its_status_looks_better(): void
    {
        $tenant = $this->createTenant();

        $this->createRouter($tenant, [
            'name' => 'Old Public Router',
            'ip_address' => '102.213.48.202',
            'api_port' => 18728,
            'api_username' => 'admin',
            'status' => Router::STATUS_ONLINE,
            'last_seen_at' => now(),
        ]);

        $canonical = $this->createRouter($tenant, [
            'name' => 'Canonical Public Router',
            'ip_address' => '102.213.48.202',
            'api_port' => 8728,
            'api_username' => 'cloudbridge',
            'status' => Router::STATUS_WARNING,
            'last_seen_at' => now()->subSeconds(5),
        ]);

        $preferred = Router::resolvePreferredForTenant($tenant->id);

        $this->assertNotNull($preferred);
        $this->assertSame($canonical->id, $preferred?->id);
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
            'max_routers' => 10,
            'max_users' => 100,
        ], $overrides));
    }

    private function createRouter(Tenant $tenant, array $overrides = []): Router
    {
        return Router::query()->create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => 'Router',
            'model' => 'MikroTik Hotspot',
            'ip_address' => '102.213.48.202',
            'api_port' => 8728,
            'api_username' => 'admin',
            'api_password' => encrypt('secret'),
            'api_ssl' => false,
            'status' => Router::STATUS_OFFLINE,
        ], $overrides));
    }
}
