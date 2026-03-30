<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Package;
use App\Models\Router;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ──────────────────────────────────────────────────────────────────
        // SUPER ADMIN (Global access)
        // ──────────────────────────────────────────────────────────────────
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@cloudbridge.network',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'phone' => '+254700000000',
            'timezone' => 'Africa/Nairobi',
            'is_active' => true,
        ]);

        // ──────────────────────────────────────────────────────────────────
        // SAMPLE TENANT: Nyamira WiFi
        // ──────────────────────────────────────────────────────────────────
        $nyamira = Tenant::create([
            'name' => 'Nyamira WiFi',
            'subdomain' => 'nyamira',
            'contact_email' => 'admin@nyamirawifi.co.ke',
            'contact_phone' => '+254712345678',
            'timezone' => 'Africa/Nairobi',
            'currency' => 'KES',
            'status' => 'active',
            'plan' => 'growth',
            'monthly_fee' => 4500.00,
            'billing_cycle_start' => now(),
            'next_billing_date' => now()->addMonth(),
            'max_routers' => 5,
            'max_users' => 500,
        ]);

        // Tenant Admin for Nyamira
        User::create([
            'tenant_id' => $nyamira->id,
            'name' => 'Nyamira Admin',
            'email' => 'admin@nyamirawifi.co.ke',
            'password' => Hash::make('password'),
            'role' => 'tenant_admin',
            'phone' => '+254712345678',
            'timezone' => 'Africa/Nairobi',
            'is_active' => true,
        ]);

        // Operator for Nyamira
        User::create([
            'tenant_id' => $nyamira->id,
            'name' => 'John Operator',
            'email' => 'operator@nyamirawifi.co.ke',
            'password' => Hash::make('password'),
            'role' => 'operator',
            'permissions' => ['vouchers.create', 'payments.view'],
            'phone' => '+254712345679',
            'timezone' => 'Africa/Nairobi',
            'is_active' => true,
        ]);

        // ──────────────────────────────────────────────────────────────────
        // SAMPLE TENANT: Kisii Hotspot
        // ──────────────────────────────────────────────────────────────────
        $kisii = Tenant::create([
            'name' => 'Kisii Hotspot',
            'subdomain' => 'kisii',
            'contact_email' => 'admin@kisiispot.co.ke',
            'contact_phone' => '+254723456789',
            'timezone' => 'Africa/Nairobi',
            'currency' => 'KES',
            'status' => 'active',
            'plan' => 'starter',
            'monthly_fee' => 1500.00,
            'billing_cycle_start' => now(),
            'next_billing_date' => now()->addMonth(),
            'max_routers' => 2,
            'max_users' => 100,
        ]);

        User::create([
            'tenant_id' => $kisii->id,
            'name' => 'Kisii Admin',
            'email' => 'admin@kisiispot.co.ke',
            'password' => Hash::make('password'),
            'role' => 'tenant_admin',
            'phone' => '+254723456789',
            'timezone' => 'Africa/Nairobi',
            'is_active' => true,
        ]);

        // ──────────────────────────────────────────────────────────────────
        // SAMPLE PACKAGES FOR NYAMIRA
        // ──────────────────────────────────────────────────────────────────
        Package::create([
            'tenant_id' => $nyamira->id,
            'name' => '1 Hour Pass',
            'code' => '1HR-001',
            'price' => 50.00,
            'currency' => 'KES',
            'duration_value' => 1,
            'duration_unit' => 'hours',
            'download_limit_mbps' => 5,
            'upload_limit_mbps' => 2,
            'mikrotik_profile_name' => 'profile-1hour',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Package::create([
            'tenant_id' => $nyamira->id,
            'name' => '3 Hours Pass',
            'code' => '3HR-001',
            'price' => 100.00,
            'currency' => 'KES',
            'duration_value' => 3,
            'duration_unit' => 'hours',
            'download_limit_mbps' => 5,
            'upload_limit_mbps' => 2,
            'mikrotik_profile_name' => 'profile-3hours',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Package::create([
            'tenant_id' => $nyamira->id,
            'name' => '24 Hours Pass',
            'code' => '24HR-001',
            'price' => 400.00,
            'currency' => 'KES',
            'duration_value' => 1,
            'duration_unit' => 'days',
            'download_limit_mbps' => 10,
            'upload_limit_mbps' => 5,
            'mikrotik_profile_name' => 'profile-24hours',
            'is_active' => true,
            'is_featured' => true,
            'sort_order' => 3,
        ]);

        Package::create([
            'tenant_id' => $nyamira->id,
            'name' => 'Weekly Pass',
            'code' => 'WK-001',
            'price' => 2000.00,
            'currency' => 'KES',
            'duration_value' => 1,
            'duration_unit' => 'weeks',
            'download_limit_mbps' => 10,
            'upload_limit_mbps' => 5,
            'mikrotik_profile_name' => 'profile-weekly',
            'is_active' => true,
            'sort_order' => 4,
        ]);

        // ──────────────────────────────────────────────────────────────────
        // SAMPLE ROUTER FOR NYAMIRA
        // ──────────────────────────────────────────────────────────────────
        Router::create([
            'tenant_id' => $nyamira->id,
            'name' => 'Main Hotspot',
            'model' => 'RB750Gr3',
            'ip_address' => '192.168.88.1',
            'api_port' => 8728,
            'api_username' => 'admin',
            'api_password' => 'mikrotik_password', // ⚠️ Encrypt in production!
            'location' => 'Nyamira Office',
            'status' => 'online',
            'last_seen_at' => now(),
            'last_sync_at' => now(),
            'cpu_usage' => 35,
            'memory_usage' => 52,
            'active_sessions' => 45,
            'uptime_seconds' => 86400,
            'accounting_interval' => 60,
            'ntp_enabled' => true,
        ]);

        // ──────────────────────────────────────────────────────────────────
        // SAMPLE VOUCHERS (For Testing)
        // ──────────────────────────────────────────────────────────────────
        // We'll create voucher generation logic later, but add a few manually for testing
        // For now, skip vouchers or create them via artisan tinker

        $this->command->info('✅ Database seeded successfully!');
        $this->command->line('');
        $this->command->line('🔑 Test Credentials:');
        $this->command->line('  Super Admin: admin@cloudbridge.network / password');
        $this->command->line('  Nyamira Admin: admin@nyamirawifi.co.ke / password');
        $this->command->line('  Kisii Admin: admin@kisiispot.co.ke / password');
        $this->command->line('');
        $this->command->line('📦 Sample Packages Created:');
        $this->command->line('  - 1 Hour Pass (KES 50)');
        $this->command->line('  - 3 Hours Pass (KES 100)');
        $this->command->line('  - 24 Hours Pass (KES 400)');
        $this->command->line('  - Weekly Pass (KES 2,000)');
        $this->command->line('');
        $this->command->line('🖥️ Sample Router:');
        $this->command->line('  - Main Hotspot (192.168.88.1)');
    }
}