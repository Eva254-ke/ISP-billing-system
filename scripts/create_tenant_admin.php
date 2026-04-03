<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tenant = Tenant::firstOrCreate(
    ['subdomain' => 'demoisp'],
    [
        'name' => 'Demo ISP',
        'contact_email' => 'admin@demoisp.co.ke',
        'contact_phone' => '+254700111222',
        'timezone' => 'Africa/Nairobi',
        'currency' => 'KES',
        'status' => 'active',
        'plan' => 'starter',
        'monthly_fee' => 0,
        'billing_cycle_start' => now()->toDateString(),
        'next_billing_date' => now()->addMonth()->toDateString(),
        'max_routers' => 2,
        'max_users' => 200,
    ]
);

User::updateOrCreate(
    ['email' => 'admin@demoisp.co.ke'],
    [
        'tenant_id' => $tenant->id,
        'name' => 'Demo ISP Admin',
        'password' => Hash::make('Admin@12345'),
        'role' => 'tenant_admin',
        'phone' => '+254700111222',
        'timezone' => 'Africa/Nairobi',
        'is_active' => true,
    ]
);

echo 'Tenant ID: ' . $tenant->id . PHP_EOL;
echo 'Admin login email: admin@demoisp.co.ke' . PHP_EOL;
echo 'Admin login password: Admin@12345' . PHP_EOL;
