# Server Deployment Checklist

## SSH into Server
```bash
ssh root@159.65.18.32
cd /var/www/cloudbridge/current
```

## 1. Backup Current Version
```bash
# Backup critical files
cp app/Http/Controllers/CaptivePortalController.php app/Http/Controllers/CaptivePortalController.php.backup
cp app/Services/Radius/RadiusAccountingService.php app/Services/Radius/RadiusAccountingService.php.backup
cp routes/api.php routes/api.php.backup
```

## 2. Deploy Updated Files
```bash
# Copy the updated files from your local machine to the server
# Use SCP or your preferred method:

# Example with SCP (run from your local machine):
scp app/Http/Controllers/CaptivePortalController.php root@159.65.18.32:/var/www/cloudbridge/current/app/Http/Controllers/
scp app/Services/Radius/RadiusAccountingService.php root@159.65.18.32:/var/www/cloudbridge/current/app/Services/Radius/
scp routes/api.php root@159.65.18.32:/var/www/cloudbridge/current/
```

## 3. Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## 4. Verify Configuration
```bash
# Check RADIUS configuration
php artisan tinker
>>> echo config('radius.enabled');      # Should return: 1
>>> echo config('radius.pure_radius');  # Should return: 1
>>> echo config('radius.access_mode');  # Should return: mac
>>> exit
```

## 5. Test Session Detection
```bash
# Run the test script
php test_session_detection.php

# Expected output: All ✅ checks
```

## 6. Test Multi-Tenant API Fix
```bash
# Test the /api/user endpoint
curl -H "Authorization: Bearer YOUR_TOKEN" http://your-domain/api/user

# Should return proper JSON response (not 500 error)
```

## 7. Verify RADIUS Accounting
```bash
# Check RADIUS database connection
mysql -u radius -p -h db-mysql-lon1-71685-do-user-17788951-0.m.db.ondigitalocean.com radius -e "SELECT COUNT(*) as active_sessions FROM radacct WHERE acctstoptime IS NULL;"
```

## 8. Test Real Session Flow

### Step A: Create Test Session
```bash
# Pay for a package (3 minutes as you mentioned)
# Connect to WiFi → Pay → Get session
```

### Step B: Verify Session in Database
```bash
php artisan tinker
>>> $session = App\Models\UserSession::where('status', 'active')->latest()->first();
>>> echo "Session ID: " . $session->id;
>>> echo "Phone: " . $session->phone;
>>> echo "MAC: " . $session->mac_address;
>>> echo "Expires: " . $session->expires_at;
>>> exit
```

### Step C: Test Router Switch
```bash
# Disconnect from current router
# Connect to different router
# Should auto-redirect to internet (no captive portal)
```

### Step D: Monitor Logs
```bash
# Watch for session detection logs
tail -f storage/logs/radius-*.log | grep "Active session found via RADIUS accounting"

# Should see log entry when session is detected
```

## 9. Verify No "Connecting" Page
```bash
# Should NOT see this log:
grep "Connecting you to WiFi" storage/logs/laravel*.log

# Should NOT see this page in browser
```

## 10. Test Session Expiry
```bash
# Wait for 3 minutes (session expiry)
# Try to connect again
# Should show package selection (not auto-connect)
```

## Expected Results

✅ **User pays → Gets session → Switches router → Auto-connected**
✅ **No "Connecting you to WiFi" page**
✅ **No 500 errors in /api/user endpoint**
✅ **Proper RADIUS accounting lookups**
✅ **Works across any router in network**

## If Issues Occur

### Issue: Still shows "Connecting" page
```bash
# Check logs
tail -f storage/logs/captive-*.log | grep "Session detection"

# Verify pure RADIUS mode
php artisan tinker
>>> echo config('radius.pure_radius'); # Must be 1
```

### Issue: API still crashes
```bash
# Check if fix was deployed
grep -A 5 "if (!\$user->tenant)" routes/api.php

# Should show the null check
```

### Issue: No auto-redirect
```bash
# Check RADIUS accounting records
mysql -u radius -p radius -e "SELECT username, callingstationid, acctstarttime FROM radacct WHERE acctstoptime IS NULL LIMIT 5;"
```

## Rollback if Needed
```bash
# Restore backups
cp app/Http/Controllers/CaptivePortalController.php.backup app/Http/Controllers/CaptivePortalController.php
cp app/Services/Radius/RadiusAccountingService.php.backup app/Services/Radius/RadiusAccountingService.php
cp routes/api.php.backup routes/api.php

# Clear caches
php artisan config:clear
php artisan cache:clear
```

## Success Confirmation

When everything works, you should see:
1. ✅ All tests pass in `php test_session_detection.php`
2. ✅ Users auto-connect when switching routers
3. ✅ No "Connecting you to WiFi" page
4. ✅ No API crashes for multi-tenant users
5. ✅ Proper RADIUS accounting logs

Deploy and test! The fixes are ready for production.
