@extends('admin.layouts.app')

@section('page-title', 'Settings')

@section('content')
<div class="row">
    <!-- M-Pesa Settings -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-mobile-alt me-2"></i>
                    M-Pesa Settings
                </h3>
            </div>
            <div class="card-body">
                <form id="mpesaForm">
                    <div class="mb-3">
                        <label class="form-label">Till Number / Paybill *</label>
                        <input type="text" class="form-control" value="174379" placeholder="e.g., 174379">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Consumer Key</label>
                        <div class="input-group">
                            <input type="password" class="form-control" value="••••••••••••••••" id="consumerKey">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('consumerKey')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Consumer Secret</label>
                        <div class="input-group">
                            <input type="password" class="form-control" value="••••••••••••••••" id="consumerSecret">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('consumerSecret')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Passkey</label>
                        <div class="input-group">
                            <input type="password" class="form-control" value="••••••••••••••••" id="passkey">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('passkey')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Environment</label>
                        <select class="form-select">
                            <option value="sandbox" selected>Sandbox (Test)</option>
                            <option value="production">Production (Live)</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="testMpesaConnection()">
                        <i class="fas fa-plug me-1"></i>Test Connection
                    </button>
                    <button type="button" class="btn btn-success" onclick="saveSettings('mpesa')">
                        <i class="fas fa-save me-1"></i>Save M-Pesa Settings
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- SMS Settings -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-sms me-2"></i>
                    SMS Settings
                </h3>
            </div>
            <div class="card-body">
                <form id="smsForm">
                    <div class="mb-3">
                        <label class="form-label">SMS Provider</label>
                        <select class="form-select">
                            <option value="africastalking" selected>Africa's Talking</option>
                            <option value="twilio">Twilio</option>
                            <option value="bulkSMS">BulkSMS</option>
                            <option value="custom">Custom API</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username / API Key</label>
                        <input type="text" class="form-control" value="cloudbridge" placeholder="Your API username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">API Key / Secret</label>
                        <div class="input-group">
                            <input type="password" class="form-control" value="••••••••••••••••" id="smsKey">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('smsKey')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sender ID</label>
                        <input type="text" class="form-control" value="CloudBridge" placeholder="e.g., CloudBridge">
                        <small class="text-muted">Max 11 characters, alphanumeric</small>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="testSmsConnection()">
                        <i class="fas fa-paper-plane me-1"></i>Send Test SMS
                    </button>
                    <button type="button" class="btn btn-success" onclick="saveSettings('sms')">
                        <i class="fas fa-save me-1"></i>Save SMS Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Branding Settings -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-palette me-2"></i>
                    Branding & Portal
                </h3>
            </div>
            <div class="card-body">
                <form id="brandingForm">
                    <div class="mb-3">
                        <label class="form-label">Portal Name</label>
                        <input type="text" class="form-control" value="CloudBridge Networks" placeholder="Displayed on captive portal">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Welcome Message</label>
                        <textarea class="form-control" rows="2" placeholder="Welcome to CloudBridge Networks WiFi!">Welcome to CloudBridge Networks WiFi!</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Primary Color</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" value="#1E40AF" style="max-width: 60px;">
                            <input type="text" class="form-control" value="#1E40AF" id="primaryColor">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo</label>
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Upload or enter logo URL">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-upload"></i>
                            </button>
                        </div>
                        <small class="text-muted">Recommended: 200x60px PNG with transparent background</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Terms & Conditions URL</label>
                        <input type="url" class="form-control" placeholder="https://cloudbridge.network/terms">
                    </div>
                    <button type="button" class="btn btn-success" onclick="saveSettings('branding')">
                        <i class="fas fa-save me-1"></i>Save Branding Settings
                    </button>
                    <button type="button" class="btn btn-outline-primary" onclick="previewPortal()">
                        <i class="fas fa-eye me-1"></i>Preview Portal
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Admin Account -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-cog me-2"></i>
                    Admin Account
                </h3>
            </div>
            <div class="card-body">
                <form id="adminForm">
                    <div class="mb-3">
                        <label class="form-label">Admin Name</label>
                        <input type="text" class="form-control" value="Admin User">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admin Email</label>
                        <input type="email" class="form-control" value="admin@cloudbridge.network">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" value="+254 7XX XXX XXX" placeholder="+254 7XX XXX XXX">
                    </div>
                    <hr>
                    <h6 class="text-muted mb-3">Change Password</h6>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" placeholder="••••••••">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" placeholder="••••••••">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" placeholder="••••••••">
                    </div>
                    <button type="button" class="btn btn-success" onclick="saveSettings('admin')">
                        <i class="fas fa-save me-1"></i>Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- System Info Card -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-info-circle me-2"></i>
            System Information
        </h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Application</strong>
                <p class="text-muted mb-0">CloudBridge WiFi SaaS v1.0.0</p>
            </div>
            <div class="col-md-3">
                <strong>Laravel Version</strong>
                <p class="text-muted mb-0">13.1.1</p>
            </div>
            <div class="col-md-3">
                <strong>PHP Version</strong>
                <p class="text-muted mb-0">8.5.1</p>
            </div>
            <div class="col-md-3">
                <strong>Database</strong>
                <p class="text-muted mb-0">SQLite (Local)</p>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-4">
                <strong>Server Time</strong>
                <p class="text-muted mb-0">{{ now()->format('Y-m-d H:i:s') }} (Africa/Nairobi)</p>
            </div>
            <div class="col-md-4">
                <strong>Environment</strong>
                <p class="text-muted mb-0"><span class="badge bg-warning">Local Development</span></p>
            </div>
            <div class="col-md-4">
                <strong>Debug Mode</strong>
                <p class="text-muted mb-0"><span class="badge bg-danger">Enabled</span></p>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <button class="btn btn-outline-secondary btn-sm" onclick="clearCache()">
            <i class="fas fa-trash-alt me-1"></i>Clear Application Cache
        </button>
        <button class="btn btn-outline-secondary btn-sm" onclick="viewLogs()">
            <i class="fas fa-clipboard-list me-1"></i>View Logs
        </button>
        <button class="btn btn-outline-secondary btn-sm" onclick="downloadBackup()">
            <i class="fas fa-download me-1"></i>Download Backup
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Toggle password visibility
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        input.type = input.type === 'password' ? 'text' : 'password';
    }

    // Color picker sync
    document.querySelector('input[type="color"]').addEventListener('input', function() {
        document.getElementById('primaryColor').value = this.value;
    });
    document.getElementById('primaryColor').addEventListener('input', function() {
        document.querySelector('input[type="color"]').value = this.value;
    });

    // Test M-Pesa Connection (Mock)
    function testMpesaConnection() {
        Swal.fire({
            title: 'Testing M-Pesa Connection...',
            text: 'Validating credentials with Safaricom Daraja API',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        
        setTimeout(() => {
            Swal.fire({
                icon: 'success',
                title: 'Connection Successful!',
                text: 'M-Pesa credentials are valid. STK Push is ready.',
                footer: 'Response time: 245ms'
            });
        }, 2000);
    }

    // Test SMS Connection (Mock)
    function testSmsConnection() {
        Swal.fire({
            title: 'Sending Test SMS...',
            text: 'Sending test message to +254 7XX XXX XXX',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        
        setTimeout(() => {
            Swal.fire({
                icon: 'success',
                title: 'SMS Sent!',
                text: 'Test message delivered successfully.',
                footer: 'Provider: Africa\'s Talking • Cost: KES 0.80'
            });
        }, 2500);
    }

    // Preview Portal
    function previewPortal() {
        Swal.fire({
            title: 'Opening Portal Preview',
            text: 'Loading captive portal in new tab...',
            icon: 'info',
            confirmButtonText: 'Open Preview'
        }).then(() => {
            window.open('/portal', '_blank');
        });
    }

    // Save Settings (Mock)
    function saveSettings(section) {
        const labels = {
            'mpesa': 'M-Pesa Settings',
            'sms': 'SMS Settings',
            'branding': 'Branding Settings',
            'admin': 'Admin Profile'
        };
        
        Swal.fire({
            title: 'Saving...',
            text: `Updating ${labels[section]}`,
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        
        setTimeout(() => {
            Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: `${labels[section]} have been updated successfully.`,
                timer: 2000,
                showConfirmButton: false
            });
        }, 1500);
    }

    // Clear Cache
    function clearCache() {
        Swal.fire({
            title: 'Clear Cache?',
            text: 'This will clear config, route, and view caches.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Clear!',
            confirmButtonColor: '#F59E0B'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Clearing...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                setTimeout(() => {
                    Swal.fire('Cleared!', 'Application cache has been cleared.', 'success');
                }, 1000);
            }
        });
    }

    // View Logs
    function viewLogs() {
        Swal.fire({
            title: 'System Logs',
            html: `
                <div style="text-align: left; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                    <p style="color: #10B981;">[2026-03-19 14:32:10] INFO: Application started</p>
                    <p style="color: #10B981;">[2026-03-19 14:32:15] INFO: Database connection established</p>
                    <p style="color: #3B82F6;">[2026-03-19 14:35:22] DEBUG: Router sync: Main Hotspot (192.168.88.1)</p>
                    <p style="color: #10B981;">[2026-03-19 14:40:05] INFO: Payment processed: WIFI-ABC123 (KES 50)</p>
                    <p style="color: #EF4444;">[2026-03-19 14:42:18] ERROR: M-Pesa callback timeout (retrying...)</p>
                    <p style="color: #10B981;">[2026-03-19 14:42:25] INFO: M-Pesa callback received: SUCCESS</p>
                </div>
            `,
            width: 600,
            confirmButtonText: 'Close'
        });
    }

    // Download Backup
    function downloadBackup() {
        Swal.fire({
            title: 'Generate Backup?',
            text: 'This will create a database backup file.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Yes, Download!',
            confirmButtonColor: '#2563EB'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Generating...',
                    text: 'Creating database backup...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                setTimeout(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Backup Ready!',
                        text: 'cloudbridge-backup-2026-03-19.sql (2.4 MB)',
                        confirmButtonText: 'Download'
                    });
                }, 2000);
            }
        });
    }
</script>
@endpush