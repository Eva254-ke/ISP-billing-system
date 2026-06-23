# Fix: Metadata Structure Mismatch - Client MAC/IP Not Propagated

## Problem
Users see captive portal packages after payment confirmation instead of getting internet access.

### Root Cause
Metadata structure mismatch between payment creation and callback processing:

- **`CaptivePortalController::processMpesa()`** (line 309-315) stores MAC/IP at:
  - `metadata['mac']` and `metadata['ip']` (flat structure)

- **`ProcessMpesaCallback`** (line 312-314) reads from:
  - `metadata['client_context']['mac']` and `metadata['client_context']['ip']` (nested structure)

This mismatch causes `$clientMac` and `$clientIp` to be `null` during callback processing, leaving sessions in `idle` status.

## Solution Steps

### 1. Fix `app/Http/Controllers/CaptivePortalController.php` (line 309-315)
Change:
```php
'metadata' => [
    'gateway' => 'mpesa',
    'created_via' => 'captive_portal',
    'mac' => $mac, 
    'ip' => $ip, 
    'package_name' => $package->name
]
```
To:
```php
'metadata' => [
    'gateway' => 'mpesa',
    'created_via' => 'captive_portal',
    'client_context' => ['mac' => $mac, 'ip' => $ip],
    'package_name' => $package->name
]
```

### 2. Verify consistency in `ProcessMpesaCallback.php` (line 312-314)
Ensure it reads:
```php
$paymentClientContext = is_array($paymentMetadata['client_context'] ?? null) ? $paymentMetadata['client_context'] : [];
$clientMac = $this->normalizeMacAddress((string) ($paymentClientContext['mac'] ?? ''));
$clientIp = $this->normalizeClientIpAddress((string) ($paymentClientContext['ip'] ?? ''));
```

### 3. Also fix `checkStatus()` method in CaptivePortalController.php (line 116-117)
Change from reading `metadata['mac']` to `metadata['client_context']['mac']`:
```php
$pMac = $this->cleanMac($metadata['client_context']['mac'] ?? $metadata['mac'] ?? '');
$pIp = $metadata['client_context']['ip'] ?? $metadata['ip'] ?? '';
```

### 4. Add backward compatibility in `rememberPaymentClient()` (line 594-606)
Ensure both structures are checked when updating payment metadata.

## Validation
- Run: `php artisan test --filter=CaptivePortalPaymentStatusTest`
- Deploy and test with real M-Pesa payment
- Check logs: `tail -f storage/logs/payment-$(date +%Y-%m-%d).log` for proper MAC/IP propagation