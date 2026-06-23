<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLogExplorerTest extends TestCase
{
    use RefreshDatabase;

    private string $logDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logDirectory = storage_path('framework/testing/log-explorer-' . str()->random(8));
        File::ensureDirectoryExists($this->logDirectory);
        config()->set('admin.logs.path', $this->logDirectory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->logDirectory);

        parent::tearDown();
    }

    public function test_admin_logs_page_renders_live_entries_from_configured_log_directory(): void
    {
        $tenant = $this->createTenant();
        $admin = $this->createAdminUser($tenant, 'logs-admin@example.com');

        File::put($this->logDirectory . DIRECTORY_SEPARATOR . 'payment-2026-04-30.log', implode("\n", [
            '[2026-04-30 12:41:00] testing.ERROR: Callback write failed {"tenant_id":1,"reference":"CP-123"}',
            '[2026-04-30 12:42:00] testing.INFO: Payment recovered {"tenant_id":1,"payment_id":151}',
            '',
        ]));

        File::put($this->logDirectory . DIRECTORY_SEPARATOR . 'router-health.log', implode("\n", [
            '[2026-04-30 12:43:00] WARNING: Router handshake timeout on Main Hotspot',
            '',
        ]));

        $response = $this->actingAs($admin)->get(route('admin.logs.index'));

        $response->assertOk();
        $response->assertSee('System Logs');
        $response->assertSee('Payment recovered');
        $response->assertSee('Router handshake timeout on Main Hotspot');
        $response->assertSee('Files In Scope');
    }

    public function test_admin_logs_page_filters_by_source_level_and_search(): void
    {
        $tenant = $this->createTenant();
        $admin = $this->createAdminUser($tenant, 'logs-filter@example.com');

        File::put($this->logDirectory . DIRECTORY_SEPARATOR . 'payment-2026-04-30.log', implode("\n", [
            '[2026-04-30 12:41:00] testing.ERROR: Underpaid callback rejected {"tenant_id":1,"payment_id":151}',
            '[2026-04-30 12:42:00] testing.INFO: Payment recovered {"tenant_id":1,"payment_id":151}',
            '',
        ]));

        File::put($this->logDirectory . DIRECTORY_SEPARATOR . 'mikrotik-2026-04-30.log', implode("\n", [
            '[2026-04-30 12:43:00] testing.ERROR: Router handshake timeout {"router":"Main Hotspot"}',
            '',
        ]));

        $response = $this->actingAs($admin)->get(route('admin.logs.index', [
            'source' => 'payments',
            'level' => 'error',
            'search' => 'Underpaid',
        ]));

        $response->assertOk();
        $response->assertSee('Underpaid callback rejected');
        $response->assertDontSee('Payment recovered');
        $response->assertDontSee('Router handshake timeout');
    }

    private function createTenant(array $overrides = []): Tenant
    {
        return Tenant::query()->create(array_merge([
            'name' => 'Logs Tenant',
            'subdomain' => 'logs-' . str()->lower(str()->random(6)),
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

    private function createAdminUser(Tenant $tenant, string $email): User
    {
        return User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Logs Admin',
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => 'tenant_admin',
            'permissions' => ['dashboard.view'],
            'phone' => '0712345678',
            'timezone' => 'Africa/Nairobi',
            'is_active' => true,
        ]);
    }
}
