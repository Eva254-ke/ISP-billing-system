<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VoucherPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_voucher_copy_button_uses_full_display_code(): void
    {
        $tenant = $this->createTenant();
        $admin = $this->createAdminUser($tenant);
        $package = $this->createPackage($tenant);
        $voucher = $this->createVoucher($tenant, $package, [
            'code' => 'ABC123',
            'prefix' => 'CB-WIFI',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.vouchers.index'));

        $response->assertOk();
        $response->assertSee("copyCode('CB-WIFI-ABC123')", false);
        $response->assertDontSee("copyCode('ABC123')", false);
        $response->assertSee((string) $voucher->code_display, false);
    }

    public function test_admin_voucher_print_view_shows_full_display_code(): void
    {
        $tenant = $this->createTenant();
        $admin = $this->createAdminUser($tenant);
        $package = $this->createPackage($tenant);
        $voucher = $this->createVoucher($tenant, $package, [
            'code' => 'ZXCV89',
            'prefix' => 'CB-WIFI',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.vouchers.print'));

        $response->assertOk();
        $response->assertSee('<code>' . $voucher->code_display . '</code>', false);
        $response->assertDontSee('<code>' . $voucher->code . '</code>', false);
    }

    public function test_code_display_does_not_duplicate_legacy_prefixes(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $voucher = $this->createVoucher($tenant, $package, [
            'code' => 'CB-WIFI-LEGACY1',
            'prefix' => 'CB-WIFI-',
        ]);

        $this->assertSame('CB-WIFI-LEGACY1', $voucher->code_display);
    }

    public function test_generate_code_uses_easy_to_read_six_character_format(): void
    {
        $code = Voucher::generateCode(6);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertStringNotContainsString('-', $code);
    }

    public function test_reconnect_view_shows_prefix_and_short_suffix_entry_for_vouchers(): void
    {
        $tenant = $this->createTenant();
        $package = $this->createPackage($tenant);
        $this->createVoucher($tenant, $package, [
            'code' => '123456',
            'prefix' => 'CB-WIFI',
        ]);

        $response = $this->get(route('wifi.packages', [
            'tenant_id' => $tenant->id,
            'mode' => 'reconnect',
        ]));

        $response->assertOk();
        $response->assertSee('CB-WIFI-', false);
        $response->assertSee('name="voucher_prefix" value="CB-WIFI"', false);
        $response->assertSee('placeholder="123456"', false);
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

    private function createPackage(Tenant $tenant, array $overrides = []): Package
    {
        return Package::query()->create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => '1 Hour Pass',
            'description' => 'Voucher test package',
            'code' => 'PKG-' . strtoupper(str()->random(6)),
            'price' => 20,
            'currency' => 'KES',
            'duration_value' => 1,
            'duration_unit' => 'hours',
            'download_limit_mbps' => 8,
            'upload_limit_mbps' => 6,
            'is_active' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    private function createAdminUser(Tenant $tenant, array $overrides = []): User
    {
        return User::query()->create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => 'Voucher Admin',
            'email' => 'admin+' . str()->lower(str()->random(6)) . '@example.com',
            'password' => Hash::make('password'),
            'role' => 'tenant_admin',
            'permissions' => ['vouchers.create', 'packages.manage'],
            'phone' => '0712345678',
            'timezone' => 'Africa/Nairobi',
            'is_active' => true,
        ], $overrides));
    }

    private function createVoucher(Tenant $tenant, Package $package, array $overrides = []): Voucher
    {
        $columns = array_flip(Schema::getColumnListing('vouchers'));
        $candidatePayload = array_merge([
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'code' => 'VCH' . strtoupper(str()->random(6)),
            'prefix' => 'CB',
            'status' => Voucher::STATUS_UNUSED,
            'valid_from' => now()->subMinute(),
            'valid_until' => now()->addDay(),
            'validity_hours' => 24,
            'captive_portal_redeemable' => true,
            'max_redemptions' => 1,
            'redemption_count' => 0,
        ], $overrides);

        $payload = [];
        foreach ($candidatePayload as $column => $value) {
            if (isset($columns[$column])) {
                $payload[$column] = $value;
            }
        }

        return Voucher::query()->create($payload);
    }
}
