<?php

use App\Models\Payment;
use App\Models\UserSession;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$phones = ['0742939094', '254742939094', '+254742939094'];

echo json_encode([
    'now' => now()->toIso8601String(),
    'timezone' => config('app.timezone'),
    'payments' => Payment::with('package')
        ->whereIn('phone', $phones)
        ->latest('id')
        ->limit(8)
        ->get([
            'id',
            'tenant_id',
            'phone',
            'package_id',
            'amount',
            'status',
            'mpesa_receipt_number',
            'created_at',
            'initiated_at',
            'confirmed_at',
            'completed_at',
            'activated_at',
            'session_id',
            'payment_channel',
        ]),
    'sessions' => UserSession::with('package')
        ->whereIn('phone', $phones)
        ->latest('id')
        ->limit(8)
        ->get([
            'id',
            'tenant_id',
            'payment_id',
            'package_id',
            'username',
            'phone',
            'mac_address',
            'ip_address',
            'status',
            'started_at',
            'expires_at',
            'grace_period_active',
            'grace_period_ends_at',
            'grace_period_seconds',
            'last_activity_at',
            'last_synced_at',
            'terminated_at',
            'termination_reason',
        ]),
], JSON_PRETTY_PRINT) . PHP_EOL;
