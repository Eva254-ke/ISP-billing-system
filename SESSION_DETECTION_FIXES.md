# Session Detection Fixes - Deployment Guide

## Problem Identified

Users who leave the router and return are being asked to sign in again even though they have an active session. This creates a poor user experience and unnecessary friction.

### Root Causes:

1. **Inactive Session Detection**: System not properly recognizing existing active sessions
2. **MAC/IP Changes**: Device identifiers changing between connections
3. **Missing Auto-Redirect**: Users not automatically redirected when session found
4. **Limited Fallback Logic**: No robust fallback mechanisms for session detection

## Fixes Implemented

### 1. Automatic Redirect for Active Sessions
```php
// If active session found, automatically redirect to continue browsing
if ($activeSession) {
    $activePayment = $activeSession->payment()->first();
    if ($activePayment) {
        // Verify session is still active and redirect to continue browsing
        $verifiedSession = $this->resolveVerifiedActiveSession($activeSession, $activePayment);
        if ($verifiedSession) {
            $continueBrowsingUrl = $this->resolveContinueBrowsingUrl($activePayment, $request);
            return redirect($continueBrowsingUrl);
        }
    }
}
```

### 2. Enhanced Session Detection Logic
```php
// Primary detection by MAC/IP
$candidates = UserSession::query()
    ->where('tenant_id', $tenantId)
    ->active()
    ->where(function ($query) use ($clientMac, $clientIp) {
        if ($clientMac !== null) {
            $query->orWhere('mac_address', $clientMac);
        }
        if ($clientIp !== null) {
            $query->orWhere('ip_address', $clientIp);
        }
    })
    ->get();

// Fallback: Phone-based detection
if ($phone) {
    $phoneCandidates = UserSession::query()
        ->where('tenant_id', $tenantId)
        ->where('phone', $phone)
        ->active()
        ->where('last_activity_at', '>=', now()->subHours(2))
        ->get();
}

// Final fallback: Recent device activity
if ($clientMac || $clientIp) {
    $recentCandidates = UserSession::query()
        ->where('tenant_id', $tenantId)
        ->active()
        ->where('last_activity_at', '>=', now()->subMinutes(30))
        ->when($clientMac, fn ($q) => $q->where('mac_address', $clientMac))
        ->when($clientIp, fn ($q) => $q->where('ip_address', $clientIp))
        ->get();
}
```

### 3. Dynamic MAC/IP Updates
```php
// Update MAC/IP if they've changed
if ($clientMac !== null && $candidate->mac_address !== $clientMac) {
    $candidate->update(['mac_address' => $clientMac]);
}
if ($clientIp !== null && $candidate->ip_address !== $clientIp) {
    $candidate->update(['ip_address' => $clientIp]);
}
```

### 4. Enhanced Client Context Resolution
```php
// Better RouterOS hotspot parameter handling
foreach ([
    (string) $request->input('mac', ''),
    (string) $request->server('HTTP_X_FORWARDED_FOR'), // Proxy MAC
    (string) $request->server('HTTP_CLIENT_MAC'),
    // ... other parameters
] as $candidate) {
    if (trim($candidate) !== '') {
        $macInput = $candidate;
        break;
    }
}
```

### 5. Comprehensive Logging
```php
Log::channel('captive')->info('Session detection attempt', [
    'tenant_id' => $tenant->id,
    'phone' => $phone,
    'client_mac' => $clientMac,
    'client_ip' => $clientIp,
    'active_session_found' => $activeSession !== null,
    'session_id' => $activeSession?->id,
    'session_status' => $activeSession?->status,
    'session_expires_at' => $activeSession?->expires_at,
]);
```

## Deployment Steps

### 1. Deploy Code Changes
```bash
# SSH to server
ssh root@159.65.18.32
cd /var/www/cloudbridge/current

# Backup current files
cp app/Http/Controllers/CaptivePortalController.php app/Http/Controllers/CaptivePortalController.php.backup

# Copy updated files from local deployment
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### 2. Configure Logging
```bash
# Ensure captive log channel exists
grep "captive" config/logging.php

# Add captive channel if missing
php artisan config:cache
```

### 3. Test Session Detection
```bash
# Create test session
php artisan tinker
>>> $session = UserSession::factory()->create([
...     'status' => 'active',
...     'expires_at' => now()->addHours(1),
...     'mac_address' => 'AA:BB:CC:DD:EE:FF',
...     'ip_address' => '192.168.1.100'
... ]);

# Test detection with MAC
>>> $controller = new CaptivePortalController();
>>> $request = new \Illuminate\Http\Request(['mac' => 'AA:BB:CC:DD:EE:FF']);
>>> $context = $controller->resolveClientContext($request);
>>> print_r($context);
```

## Expected Behavior

### Before Fixes:
1. User connects and pays → Gets session
2. User leaves router and returns → Sees package selection page
3. User must manually reconnect or pay again

### After Fixes:
1. User connects and pays → Gets session
2. User leaves router and returns → **Auto-redirected to internet**
3. Seamless browsing experience

## Testing Scenarios

### 1. Same MAC, Different IP
```bash
# Simulate device with same MAC but new IP
curl "http://your-domain/wifi?mac=AA:BB:CC:DD:EE:FF&ip=192.168.1.101"
# Expected: Auto-redirect to continue browsing
```

### 2. Different MAC, Same IP
```bash
# Simulate device with new MAC but same IP
curl "http://your-domain/wifi?mac=11:22:33:44:55:66&ip=192.168.1.100"
# Expected: Fallback to IP-based detection
```

### 3. Phone-based Detection
```bash
# Test with phone number only
curl "http://your-domain/wifi?phone=254712345678"
# Expected: Find session by phone if recent
```

## Monitoring & Debugging

### 1. Watch Session Detection Logs
```bash
tail -f storage/logs/captive-*.log | grep "Session detection"
```

### 2. Monitor Session Updates
```bash
# Check for MAC/IP updates
grep "mac_address.*updated" storage/logs/laravel*.log
grep "ip_address.*updated" storage/logs/laravel*.log
```

### 3. Track Auto-Redirects
```bash
# Monitor successful redirects
grep "continue browsing" storage/logs/captive-*.log
```

## RouterOS Configuration

### 1. Ensure Proper Hotspot Parameters
```routeros
# Verify hotspot passes MAC address
/ip hotspot profile print
# Check that 'mac-cookie' or similar is enabled
```

### 2. Hotspot Login Link
```routeros
# Ensure login link includes MAC parameter
/ip hotspot profile set [find] login-by="mac-cookie"
```

## Troubleshooting

### 1. Sessions Not Detected
```bash
# Check session data
php artisan tinker
>>> UserSession::where('status', 'active')->count();
>>> UserSession::where('expires_at', '>', now())->count();

# Check MAC format
>>> $session = UserSession::first();
>>> var_dump($session->mac_address);
```

### 2. Auto-Redirect Not Working
```bash
# Check continue browsing URL resolution
php artisan tinker
>>> $payment = Payment::first();
>>> $controller = new CaptivePortalController();
>>> $request = new \Illuminate\Http\Request();
>>> $url = $controller->resolveContinueBrowsingUrl($payment, $request);
>>> echo $url;
```

### 3. MAC/IP Changes Not Updated
```bash
# Check update queries
grep "UPDATE.*mac_address" storage/logs/laravel*.log
grep "UPDATE.*ip_address" storage/logs/laravel*.log
```

## Performance Impact

- **Minimal**: Additional queries only when primary detection fails
- **Fast**: Session detection optimized with proper indexing
- **Efficient**: Fallback queries limited and time-bound

## Configuration Options

| Option | Default | Description |
|--------|---------|-------------|
| Phone fallback window | 2 hours | How long to search by phone |
| Recent activity window | 30 minutes | How long to search by device |
| Session verification | Enabled | Verify session with router/RADIUS |

## Rollback Plan

If issues occur:
```bash
# Restore backup
cp app/Http/Controllers/CaptivePortalController.php.backup app/Http/Controllers/CaptivePortalController.php

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Restart services
systemctl restart php-fpm
```

## Success Metrics

- **Reduced friction**: Users should not see package selection when returning
- **Higher conversion**: Less abandoned sessions
- **Better UX**: Seamless reconnection experience
- **Lower support**: Fewer "can't reconnect" complaints

## Expected Log Output

```
Session detection attempt {
    "tenant_id": 1,
    "phone": "254712345678",
    "client_mac": "AA:BB:CC:DD:EE:FF",
    "client_ip": "192.168.1.100",
    "active_session_found": true,
    "session_id": 123,
    "session_status": "active",
    "session_expires_at": "2026-05-06T12:00:00Z"
}
```
