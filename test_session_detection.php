<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Session Detection Test ===\n";

// Test 1: Check if RADIUS configuration is correct
echo "\n1. Checking RADIUS Configuration:\n";
echo "RADIUS Enabled: " . (config('radius.enabled') ? 'YES' : 'NO') . "\n";
echo "Pure RADIUS: " . (config('radius.pure_radius') ? 'YES' : 'NO') . "\n";
echo "Access Mode: " . config('radius.access_mode') . "\n";

// Test 2: Check if CaptivePortalController methods exist
echo "\n2. Checking Controller Methods:\n";
$controller = new App\Http\Controllers\CaptivePortalController();
$methods = ['resolvePackagesActiveSession', 'resolveVerifiedActiveSession', 'resolveClientContext'];

foreach ($methods as $method) {
    $exists = method_exists($controller, $method);
    echo "$method: " . ($exists ? '✅ EXISTS' : '❌ MISSING') . "\n";
}

// Test 3: Check if RadiusAccountingService methods exist
echo "\n3. Checking RADIUS Service Methods:\n";
try {
    $radiusService = new App\Services\Radius\RadiusAccountingService(app(App\Services\Radius\RadiusIdentityResolver::class));
    $methods = ['findActiveSessionByMac', 'findActiveSessionByIp'];
    
    foreach ($methods as $method) {
        $exists = method_exists($radiusService, $method);
        echo "$method: " . ($exists ? '✅ EXISTS' : '❌ MISSING') . "\n";
    }
} catch (Exception $e) {
    echo "❌ RADIUS Service Error: " . $e->getMessage() . "\n";
}

// Test 4: Check database connection for RADIUS
echo "\n4. Checking RADIUS Database Connection:\n";
try {
    $connection = config('radius.db_connection', 'radius');
    $dbConfig = config("database.connections.$connection");
    
    if ($dbConfig) {
        echo "RADIUS DB Config: ✅ FOUND\n";
        echo "Database: " . ($dbConfig['database'] ?? 'Not set') . "\n";
        echo "Host: " . ($dbConfig['host'] ?? 'Not set') . "\n";
    } else {
        echo "❌ RADIUS DB Config: NOT FOUND\n";
    }
} catch (Exception $e) {
    echo "❌ DB Config Error: " . $e->getMessage() . "\n";
}

// Test 5: Check for recent sessions
echo "\n5. Checking Recent Sessions:\n";
try {
    $recentSessions = App\Models\UserSession::where('status', 'active')
        ->where('expires_at', '>', now())
        ->orderByDesc('last_activity_at')
        ->limit(5)
        ->get();
    
    echo "Active Sessions Found: " . $recentSessions->count() . "\n";
    
    foreach ($recentSessions as $session) {
        echo "- Session ID: {$session->id}, Phone: {$session->phone}, MAC: {$session->mac_address}\n";
        echo "  Expires: {$session->expires_at}\n";
        echo "  Status: {$session->status}\n";
    }
} catch (Exception $e) {
    echo "❌ Session Query Error: " . $e->getMessage() . "\n";
}

// Test 6: Test MAC address normalization
echo "\n6. Testing MAC Address Normalization:\n";
try {
    $identityResolver = new App\Services\Radius\RadiusIdentityResolver();
    $testMacs = ['aa:bb:cc:dd:ee:ff', 'AABBCCDDEEFF', 'aa-bb-cc-dd-ee-ff'];
    
    foreach ($testMacs as $mac) {
        $normalized = $identityResolver->normalizeMacAddress($mac);
        echo "$mac → $normalized\n";
    }
} catch (Exception $e) {
    echo "❌ MAC Normalization Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "If all tests show ✅, the session detection should work correctly.\n";
echo "Deploy to server and test with a real session.\n";
