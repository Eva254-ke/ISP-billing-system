# RADIUS Disconnect Fixes - Deployment Guide

## Issues Identified

### 1. Source IP Problem
- **Issue**: RADIUS disconnect requests sent from `0.0.0.0:40405` 
- **Impact**: NAS devices reject or ignore disconnect packets
- **Solution**: Add proper source IP binding

### 2. Network Connectivity
- **Issue**: NAS devices not responding to disconnect requests
- **Impact**: Sessions remain active after expiry
- **Solution**: Add MikroTik API fallback mechanism

### 3. Configuration Gaps
- **Issue**: Limited configuration options for disconnect behavior
- **Impact**: No ability to fine-tune disconnect process
- **Solution**: Enhanced configuration options

## Fixes Implemented

### 1. Source IP Resolution
```php
// New method to determine proper source IP
private function resolveSourceIp(?Router $router): ?string
{
    // 1. Try configured source IP
    $sourceIp = config('radius.disconnect_source_ip');
    
    // 2. Try RADIUS server IP
    $serverIp = config('radius.server_ip');
    
    // 3. Auto-detect based on NAS network
    return $this->detectSourceIp($router->ip_address);
}
```

### 2. MikroTik API Fallback
```php
// Fallback disconnect when RADIUS fails
private function attemptMikrotikDisconnect(UserSession $session, ?Router $router): array
{
    // Try by MAC address
    $mikrotikService->removeHotspotUser($router, $session->mac_address);
    
    // Try by IP address  
    $mikrotikService->removeHotspotUserByIp($router, $session->ip_address);
    
    // Try by username
    $mikrotikService->removeHotspotUserByUsername($router, $session->username);
}
```

### 3. Enhanced Configuration
```php
// New .env options
RADIUS_DISCONNECT_FALLBACK_TO_API=true
RADIUS_DISCONNECT_SOURCE_IP=159.65.18.32
RADIUS_DISCONNECT_RETRY_COUNT=2
```

## Deployment Steps

### 1. Update Configuration Files
```bash
# Add to .env file
RADIUS_DISCONNECT_FALLBACK_TO_API=true
RADIUS_DISCONNECT_SOURCE_IP=159.65.18.32
RADIUS_DISCONNECT_RETRY_COUNT=2
```

### 2. Deploy Code Changes
```bash
# SSH to server
ssh root@159.65.18.32
cd /var/www/cloudbridge/current

# Backup current files
cp app/Services/Radius/RadiusDisconnectService.php app/Services/Radius/RadiusDisconnectService.php.backup
cp config/radius.php config/radius.php.backup

# Copy updated files (from your local deployment)
# Clear caches
php artisan config:clear
php artisan cache:clear
```

### 3. Verify RADIUS Configuration
```bash
# Check FreeRADIUS status
systemctl status freeradius

# Test RADIUS connectivity
radtest testuser testpass 127.0.0.1 1812 testing123

# Check RouterOS RADIUS client configuration
# (via RouterOS terminal or WinBox)
/radius print
```

### 4. Network Configuration
```bash
# Verify server can reach NAS devices
ping 102.213.48.202
ping 192.168.100.18

# Check firewall rules for RADIUS ports
iptables -L | grep 3799
iptables -L | grep 1812
iptables -L | grep 1813
```

## RouterOS Configuration

### 1. Enable RADIUS Disconnect
```routeros
# Enable CoA/Disconnect support
/radius
set use-coa=yes
set coa-port=3799

# Add RADIUS server configuration
/radius server
add address=159.65.18.32 secret=yoursecret timeout=300ms
```

### 2. Verify RADIUS Client
```routeros
# Check RADIUS client status
/radius print detail

# Test disconnect (from server)
echo "User-Name = \"testuser\"" | radclient -x 102.213.48.202:3799 disconnect yoursecret
```

## Monitoring & Testing

### 1. Test Disconnect Functionality
```bash
# Create test session
php artisan tinker
>>> $session = UserSession::factory()->create();
>>> $session->update(['status' => 'active']);

# Test disconnect
>>> $service = new RadiusDisconnectService();
>>> $result = $service->disconnect($session);
>>> print_r($result);
```

### 2. Monitor Logs
```bash
# Watch RADIUS logs in real-time
tail -f storage/logs/radius-*.log | grep "disconnect"

# Check for disconnect attempts
grep "RADIUS disconnect" storage/logs/radius-*.log

# Check fallback usage
grep "Fallback MikroTik" storage/logs/radius-*.log
```

### 3. Performance Monitoring
```bash
# Monitor disconnect success rate
grep "disconnect acknowledged" storage/logs/radius-*.log | wc -l
grep "disconnect failed" storage/logs/radius-*.log | wc -l

# Check session cleanup
php artisan tinker
>>> UserSession::where('status', 'expired')->count();
>>> UserSession::where('status', 'terminated')->count();
```

## Troubleshooting

### 1. Disconnect Still Failing
```bash
# Check source IP binding
tcpdump -i any -n host 102.213.48.202 and port 3799

# Verify RADIUS secret
grep "radius.*secret" .env

# Check radclient availability
which radclient
radclient -h
```

### 2. MikroTik Fallback Issues
```bash
# Test MikroTik API connection
php artisan tinker
>>> $router = Router::first();
>>> $service = new MikroTikService();
>>> $service->pingRouter($router);

# Check API credentials
grep "MIKROTIK_" .env
```

### 3. Network Issues
```bash
# Trace route to NAS
traceroute 102.213.48.202
traceroute 192.168.100.18

# Check port connectivity
nc -zv 102.213.48.202 3799
nc -zv 192.168.100.18 3799
```

## Expected Results

### Before Fixes
```
RADIUS disconnect failed {"exit_code":1,"error":"Sent Disconnect-Request from 0.0.0.0:40405"}
Session marked closed after RADIUS disconnect failure
```

### After Fixes
```
RADIUS disconnect acknowledged {"session_id":173,"nas_ip":"102.213.48.202"}
Fallback MikroTik disconnect succeeded {"fallback_method":"mikrotik_api"}
```

## Rollback Plan

If issues occur:
```bash
# Restore backups
cp app/Services/Radius/RadiusDisconnectService.php.backup app/Services/Radius/RadiusDisconnectService.php
cp config/radius.php.backup config/radius.php

# Clear caches
php artisan config:clear
php artisan cache:clear

# Restart services
systemctl restart freeradius
systemctl restart php-fpm
```

## Configuration Options

| Option | Default | Description |
|--------|---------|-------------|
| `RADIUS_DISCONNECT_FALLBACK_TO_API` | `true` | Enable MikroTik API fallback |
| `RADIUS_DISCONNECT_SOURCE_IP` | `null` | Force source IP for disconnect requests |
| `RADIUS_DISCONNECT_RETRY_COUNT` | `2` | Number of disconnect attempts |
| `RADIUS_DISCONNECT_TIMEOUT` | `5` | Timeout per disconnect attempt (seconds) |

## Performance Impact

- **Minimal**: Fallback only used when RADIUS fails
- **Fast**: MikroTik API typically faster than RADIUS disconnect
- **Reliable**: Dual-path disconnect ensures session cleanup
