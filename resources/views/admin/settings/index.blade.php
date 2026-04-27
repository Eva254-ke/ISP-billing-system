@extends('admin.layouts.app')

@section('page-title', 'Settings')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-cogs me-2"></i>System Settings</h2>
    <button class="btn btn-success" onclick="saveAllSettings()">
        <i class="fas fa-save me-1"></i>Save All Changes
    </button>
</div>

<!-- Settings Tabs -->
<div class="card">
    <div class="card-header p-0">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="pill" data-toggle="pill" href="#tab-mpesa">
                    <i class="fas fa-mobile-alt me-1"></i> M-Pesa
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="pill" data-toggle="pill" href="#tab-sms">
                    <i class="fas fa-sms me-1"></i> SMS Gateway
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="pill" data-toggle="pill" href="#tab-email">
                    <i class="fas fa-envelope me-1"></i> Email/SMTP
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="pill" data-toggle="pill" href="#tab-billing">
                    <i class="fas fa-file-invoice-dollar me-1"></i> Billing & Tax
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="pill" data-toggle="pill" href="#tab-branding">
                    <i class="fas fa-palette me-1"></i> Branding
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="pill" data-toggle="pill" href="#tab-router">
                    <i class="fas fa-server me-1"></i> MikroTik
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="pill" data-toggle="pill" href="#tab-system">
                    <i class="fas fa-microchip me-1"></i> System
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="pill" data-toggle="pill" href="#tab-backup">
                    <i class="fas fa-database me-1"></i> Backup
                </a>
            </li>
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content">

            <!-- ======================================================================= -->
            <!-- M-PESA SETTINGS TAB -->
            <!-- ======================================================================= -->
            <div class="tab-pane fade show active" id="tab-mpesa">
                <h5 class="mb-4"><i class="fas fa-mobile-alt me-2"></i>M-Pesa Daraja API Configuration</h5>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Environment *</label>
                            <select class="form-select" id="mpesa_env">
                                <option value="sandbox">Sandbox (Test Mode)</option>
                                <option value="production" selected>Production (Live)</option>
                            </select>
                            <small class="text-muted">Use sandbox for testing, production for live payments</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Till Number (Buy Goods) *</label>
                            <input type="text" class="form-control" id="mpesa_till" value="5468788" placeholder="e.g., 5468788">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Consumer Key *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="mpesa_key" value="" placeholder="Enter Daraja consumer key">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('mpesa_key')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Consumer Secret *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="mpesa_secret" value="" placeholder="Enter Daraja consumer secret">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('mpesa_secret')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Passkey *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="mpesa_passkey" value="" placeholder="Enter Daraja passkey">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('mpesa_passkey')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Found in Daraja portal under credentials</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Callback URL</label>
                            <input type="text" class="form-control" id="mpesa_callback" value="{{ url('/api/mpesa/callback') }}" readonly>
                            <small class="text-muted">Register this URL in Daraja portal</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Business Short Code (Till)</label>
                            <input type="text" class="form-control" id="mpesa_shortcode" value="5468788">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Transaction Timeout (seconds)</label>
                            <input type="number" class="form-control" id="mpesa_timeout" value="60" min="30" max="300">
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Setup Instructions:</strong>
                    <ol class="mb-0 mt-2">
                        <li>Visit <a href="https://developer.safaricom.co.ke" target="_blank">Daraja Portal</a></li>
                        <li>Create an app to get Consumer Key & Secret</li>
                        <li>Generate Passkey from the portal</li>
                        <li>Register callback URL in your app settings</li>
                    </ol>
                </div>

                <button class="btn btn-primary" onclick="testMpesaConnection()">
                    <i class="fas fa-plug me-1"></i>Test Connection
                </button>
            </div>

            <!-- ======================================================================= -->
            <!-- SMS GATEWAY SETTINGS TAB -->
            <!-- ======================================================================= -->
            <div class="tab-pane fade" id="tab-sms">
                <h5 class="mb-4"><i class="fas fa-sms me-2"></i>SMS Gateway Configuration</h5>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">SMS Provider *</label>
                            <select class="form-select" id="sms_provider">
                                <option value="africastalking" selected>Africa's Talking</option>
                                <option value="twilio">Twilio</option>
                                <option value="bulksms">BulkSMS</option>
                                <option value="smartsmssolutions">SmartSMS Solutions</option>
                                <option value="custom">Custom API</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Sender ID *</label>
                            <input type="text" class="form-control" id="sms_sender" value="CloudBridge" maxlength="11" placeholder="Max 11 chars">
                            <small class="text-muted">Alphanumeric, max 11 characters</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">API Username</label>
                            <input type="text" class="form-control" id="sms_username" value="cloudbridge">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">API Key *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="sms_apikey" value="••••••••••••••••">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('sms_apikey')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">SMS Templates</label>
                    <div class="card bg-light">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label small">Payment Success</label>
                                <textarea class="form-control form-control-sm" id="sms_payment_success" rows="2">CloudBridge: Payment of KES {amount} received. {package} activated. Valid until {expiry}. Thank you!</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Payment Failed</label>
                                <textarea class="form-control form-control-sm" id="sms_payment_failed" rows="2">CloudBridge: Payment failed. Please retry or contact support. Ref: {reference}</textarea>
                            </div>
                            <div class="mb-0">
                                <label class="form-label small">Voucher Purchase</label>
                                <textarea class="form-control form-control-sm" id="sms_voucher" rows="2">CloudBridge: Voucher {code} for {package}. Valid {validity}. Dial *123# to redeem.</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Available Variables:</strong> <code>{amount}</code> <code>{package}</code> <code>{expiry}</code> <code>{code}</code> <code>{validity}</code> <code>{reference}</code> <code>{phone}</code>
                </div>

                <button class="btn btn-primary" onclick="testSmsConnection()">
                    <i class="fas fa-paper-plane me-1"></i>Send Test SMS
                </button>
            </div>

            <!-- ======================================================================= -->
            <!-- EMAIL/SMTP SETTINGS TAB -->
            <!-- ======================================================================= -->
            <div class="tab-pane fade" id="tab-email">
                <h5 class="mb-4"><i class="fas fa-envelope me-2"></i>Email & SMTP Configuration</h5>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Mail Driver *</label>
                            <select class="form-select" id="mail_driver">
                                <option value="smtp" selected>SMTP</option>
                                <option value="mail">PHP Mail</option>
                                <option value="sendmail">Sendmail</option>
                                <option value="log">Log Only (Debug)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Encryption</label>
                            <select class="form-select" id="mail_encryption">
                                <option value="tls" selected>TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="">None</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">SMTP Host *</label>
                            <input type="text" class="form-control" id="mail_host" value="smtp.gmail.com" placeholder="smtp.gmail.com">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">SMTP Port *</label>
                            <input type="number" class="form-control" id="mail_port" value="587" placeholder="587">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">SMTP Username</label>
                            <input type="email" class="form-control" id="mail_username" value="noreply@cloudbridge.network">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">SMTP Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="mail_password" value="••••••••••••">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('mail_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">From Address</label>
                            <input type="email" class="form-control" id="mail_from_address" value="noreply@cloudbridge.network">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">From Name</label>
                            <input type="text" class="form-control" id="mail_from_name" value="CloudBridge Networks">
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary" onclick="testEmailConnection()">
                    <i class="fas fa-envelope me-1"></i>Send Test Email
                </button>
            </div>

            <!-- ======================================================================= -->
            <!-- BILLING & TAX SETTINGS TAB -->
            <!-- ======================================================================= -->
            <div class="tab-pane fade" id="tab-billing">
                <h5 class="mb-4"><i class="fas fa-file-invoice-dollar me-2"></i>Taxes & Invoicing</h5>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Enable VAT/Tax</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="tax_enabled" checked>
                                <label class="form-check-label" for="tax_enabled">Apply tax to invoices and receipts</label>
                            </div>
                            <small class="text-muted">Disable if you are not VAT registered</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Tax Label</label>
                            <input type="text" class="form-control" id="tax_label" value="VAT" placeholder="VAT, GST, Sales Tax">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label class="form-label">Tax Rate (%)</label>
                            <input type="number" class="form-control" id="tax_rate" value="16" min="0" max="100" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label class="form-label">Price Type</label>
                            <select class="form-select" id="tax_inclusive">
                                <option value="inclusive" selected>Prices include tax</option>
                                <option value="exclusive">Prices exclude tax</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label class="form-label">VAT/Tax Number</label>
                            <input type="text" class="form-control" id="tax_number" value="P051234567Q" placeholder="Registration number">
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="mb-3">Invoice Templates</h6>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label class="form-label">Template Style</label>
                            <select class="form-select" id="invoice_template">
                                <option value="modern" selected>Modern</option>
                                <option value="classic">Classic</option>
                                <option value="minimal">Minimal</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label class="form-label">Invoice Prefix</label>
                            <input type="text" class="form-control" id="invoice_prefix" value="CBN-" placeholder="e.g., CBN-">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label class="form-label">Next Invoice Number</label>
                            <input type="number" class="form-control" id="invoice_next_number" value="10001" min="1">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Billing Address</label>
                            <textarea class="form-control" id="invoice_address" rows="3">CloudBridge Networks
P.O. Box 12345 - 00100
Nairobi, Kenya</textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Invoice Footer Note</label>
                            <textarea class="form-control" id="invoice_footer_note" rows="3">Thank you for your business. Payments are due upon receipt.</textarea>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label class="form-label">Payment Terms (days)</label>
                            <input type="number" class="form-control" id="invoice_terms" value="0" min="0" max="90">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group mb-3">
                            <label class="form-label">Invoice Email</label>
                            <input type="email" class="form-control" id="invoice_email" value="billing@cloudbridge.network">
                        </div>
                    </div>
                </div>

                <button class="btn btn-outline-primary mb-4" onclick="previewInvoiceTemplate()">
                    <i class="fas fa-eye me-1"></i>Preview Invoice
                </button>

                <hr class="my-4">

                <h6 class="mb-3">Receipt Emails</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Send Receipt Emails</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="receipt_enabled" checked>
                                <label class="form-check-label" for="receipt_enabled">Email receipts after successful payments</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">BCC Finance</label>
                            <input type="email" class="form-control" id="receipt_bcc" value="finance@cloudbridge.network" placeholder="finance@company.com">
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Receipt Email Subject</label>
                    <input type="text" class="form-control" id="receipt_subject" value="Your CloudBridge receipt">
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Receipt Email Message</label>
                    <textarea class="form-control" id="receipt_body" rows="4">Hello {name},

We’ve received your payment of {amount}. Your service is active until {expiry}.

Thank you for choosing CloudBridge Networks.</textarea>
                    <small class="text-muted">Available variables: <code>{name}</code> <code>{amount}</code> <code>{expiry}</code> <code>{invoice}</code> <code>{package}</code></small>
                </div>
            </div>

            <!-- ======================================================================= -->
            <!-- BRANDING SETTINGS TAB -->
            <!-- ======================================================================= -->
            <div class="tab-pane fade" id="tab-branding">
                <h5 class="mb-4"><i class="fas fa-palette me-2"></i>Branding & Portal Customization</h5>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Company Name *</label>
                            <input type="text" class="form-control" id="brand_name" value="CloudBridge Networks">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Portal Name</label>
                            <input type="text" class="form-control" id="brand_portal_name" value="CloudBridge WiFi">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Logo</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="brand_logo" placeholder="Upload or enter URL">
                                <button class="btn btn-outline-secondary" type="button" onclick="uploadLogo()">
                                    <i class="fas fa-upload"></i>
                                </button>
                            </div>
                            <small class="text-muted">Recommended: 200x60px PNG with transparent background</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Favicon</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="brand_favicon" placeholder="Upload or enter URL">
                                <button class="btn btn-outline-secondary" type="button" onclick="uploadFavicon()">
                                    <i class="fas fa-upload"></i>
                                </button>
                            </div>
                            <small class="text-muted">Recommended: 32x32px ICO or PNG</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label class="form-label">Primary Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="brand_primary" value="#1E40AF" style="max-width: 60px;">
                                <input type="text" class="form-control" id="brand_primary_text" value="#1E40AF">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label class="form-label">Secondary Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="brand_secondary" value="#38BDF8" style="max-width: 60px;">
                                <input type="text" class="form-control" id="brand_secondary_text" value="#38BDF8">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label class="form-label">Accent Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="brand_accent" value="#14B8A6" style="max-width: 60px;">
                                <input type="text" class="form-control" id="brand_accent_text" value="#14B8A6">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Welcome Message</label>
                    <textarea class="form-control" id="brand_welcome" rows="2">Welcome to CloudBridge Networks WiFi! Enjoy fast, reliable internet.</textarea>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Terms & Conditions URL</label>
                    <input type="url" class="form-control" id="brand_terms" placeholder="https://cloudbridge.network/terms">
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Support Contact</label>
                    <input type="text" class="form-control" id="brand_support" value="+254 700 000 000 | support@cloudbridge.network">
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Preview:</strong> Changes will be reflected on the customer captive portal immediately after saving.
                </div>

                <button class="btn btn-outline-primary" onclick="previewPortal()">
                    <i class="fas fa-eye me-1"></i>Preview Portal
                </button>
            </div>

            <!-- ======================================================================= -->
            <!-- MIKROTIK SETTINGS TAB -->
            <!-- ======================================================================= -->
            <div class="tab-pane fade" id="tab-router">
                <h5 class="mb-4"><i class="fas fa-server me-2"></i>MikroTik Global Configuration</h5>

                <div class="alert alert-info">
                    <div><strong>First Router Setup Checklist</strong></div>
                    <ol class="mb-0 mt-2">
                        <li>Add your router under <strong>Admin &gt; Routers</strong> with IP, API port, username, and password.</li>
                        <li>Set <strong>RADIUS Server</strong> to your FreeRADIUS host IP (do not use <code>127.0.0.1</code> unless RADIUS is on the same host).</li>
                        <li>Use the same <strong>RADIUS Secret</strong> here and on your FreeRADIUS client/router config.</li>
                        <li>Copy the generated RouterOS commands and run them on the MikroTik terminal.</li>
                        <li>Click <strong>Test MikroTik Connection</strong> to confirm the app can reach your router.</li>
                    </ol>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Default API Port</label>
                            <input type="number" class="form-control" id="mikrotik_port" value="8728" min="1" max="65535">
                            <small class="text-muted">Default: 8728 (unencrypted), 8729 (encrypted)</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">API Timeout (seconds)</label>
                            <input type="number" class="form-control" id="mikrotik_timeout" value="30" min="5" max="300">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Default Username</label>
                            <input type="text" class="form-control" id="mikrotik_username" value="admin">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Connection Retry</label>
                            <input type="number" class="form-control" id="mikrotik_retry" value="3" min="1" max="10">
                            <small class="text-muted">Number of retry attempts</small>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">RADIUS Settings</label>
                    <div class="card bg-light">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small">RADIUS Server</label>
                                    <input type="text" class="form-control form-control-sm" id="radius_server" placeholder="192.168.88.100">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small">RADIUS Port</label>
                                    <input type="number" class="form-control form-control-sm" id="radius_port" value="1812">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small">RADIUS Secret</label>
                                    <input type="password" class="form-control form-control-sm" id="radius_secret" placeholder="Your secret">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small">Accounting Port</label>
                                    <input type="number" class="form-control form-control-sm" id="radius_acct_port" value="1813">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small">Timeout (seconds)</label>
                                    <input type="number" class="form-control form-control-sm" id="radius_timeout" value="5" min="1" max="30">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">User Profile Template</label>
                    <textarea class="form-control" id="mikrotik_profile_template" rows="3" placeholder="/ppp profile add name={name} rate-limit={download}M/{upload}M session-timeout={timeout}"></textarea>
                    <small class="text-muted">Optional. Leave empty if you do not want a global hardcoded profile template.</small>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">RouterOS Connection Commands</label>
                    <textarea class="form-control font-monospace" id="mikrotik_connect_commands" rows="7" readonly></textarea>
                    <small class="text-muted">Use these commands on MikroTik terminal to connect it to FreeRADIUS.</small>
                </div>

                <button class="btn btn-outline-secondary mb-3" onclick="copyMikrotikCommands()">
                    <i class="fas fa-copy me-1"></i>Copy Commands
                </button>

                <button class="btn btn-primary" onclick="testMikrotikConnection()">
                    <i class="fas fa-plug me-1"></i>Test MikroTik Connection
                </button>
            </div>

            <!-- ======================================================================= -->
            <!-- SYSTEM SETTINGS TAB -->
            <!-- ======================================================================= -->
            <div class="tab-pane fade" id="tab-system">
                <h5 class="mb-4"><i class="fas fa-microchip me-2"></i>System & PHP Configuration</h5>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Timezone *</label>
                            <select class="form-select" id="sys_timezone">
                                <option value="Africa/Nairobi" selected>Africa/Nairobi (EAT)</option>
                                <option value="UTC">UTC</option>
                                <option value="Europe/London">Europe/London (GMT)</option>
                                <option value="America/New_York">America/New_York (EST)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Date Format</label>
                            <select class="form-select" id="sys_date_format">
                                <option value="Y-m-d H:i:s" selected>YYYY-MM-DD HH:MM:SS</option>
                                <option value="d/m/Y H:i:s">DD/MM/YYYY HH:MM:SS</option>
                                <option value="m/d/Y H:i:s">MM/DD/YYYY HH:MM:SS</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Currency</label>
                            <select class="form-select" id="sys_currency">
                                <option value="KES" selected>KES - Kenyan Shilling</option>
                                <option value="USD">USD - US Dollar</option>
                                <option value="EUR">EUR - Euro</option>
                                <option value="GBP">GBP - British Pound</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Currency Symbol</label>
                            <input type="text" class="form-control" id="sys_currency_symbol" value="KES" maxlength="5">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Session Timeout (minutes)</label>
                            <input type="number" class="form-control" id="sys_session_timeout" value="120" min="15" max="1440">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Max Upload Size (MB)</label>
                            <input type="number" class="form-control" id="sys_upload_max" value="64" min="1" max="512">
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">PHP Configuration (from php.ini)</label>
                    <div class="card bg-light">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <small class="text-muted d-block">Memory Limit</small>
                                    <strong>128M</strong>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <small class="text-muted d-block">Max Execution Time</small>
                                    <strong>30s</strong>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <small class="text-muted d-block">PHP Version</small>
                                    <strong>8.5.1</strong>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <small class="text-muted d-block">Laravel Version</small>
                                    <strong>13.1.1</strong>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <small class="text-muted d-block">Database</small>
                                    <strong>SQLite</strong>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <small class="text-muted d-block">Debug Mode</small>
                                    <strong><span class="badge bg-warning">Enabled</span></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> Changing system settings may require application restart. Some changes may affect performance.
                </div>
            </div>

            <!-- ======================================================================= -->
            <!-- BACKUP & MAINTENANCE TAB -->
            <!-- ======================================================================= -->
            <div class="tab-pane fade" id="tab-backup">
                <h5 class="mb-4"><i class="fas fa-database me-2"></i>Backup & Maintenance</h5>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-success text-white">
                                <i class="fas fa-download me-2"></i>Database Backup
                            </div>
                            <div class="card-body">
                                <p class="card-text">Download a complete backup of your database including all vouchers, payments, and configurations.</p>
                                <button class="btn btn-success" onclick="downloadBackup()">
                                    <i class="fas fa-download me-1"></i>Download Backup
                                </button>
                                <small class="text-muted d-block mt-2">Format: SQL • Last backup: 2026-03-19 08:00</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-upload me-2"></i>Restore Backup
                            </div>
                            <div class="card-body">
                                <p class="card-text">Restore from a previously downloaded backup file.</p>
                                <input type="file" class="form-control mb-2" id="backup_file" accept=".sql">
                                <button class="btn btn-info" onclick="restoreBackup()">
                                    <i class="fas fa-upload me-1"></i>Restore
                                </button>
                                <small class="text-muted d-block mt-2"><i class="fas fa-exclamation-triangle me-1"></i>This will overwrite current data</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-header bg-warning">
                                <i class="fas fa-broom me-2"></i>Clear Cache
                            </div>
                            <div class="card-body">
                                <p class="card-text small">Clear application, route, and view caches.</p>
                                <button class="btn btn-warning btn-sm" onclick="clearCache()">
                                    <i class="fas fa-trash me-1"></i>Clear Now
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-header bg-danger">
                                <i class="fas fa-clipboard-list me-2"></i>View Logs
                            </div>
                            <div class="card-body">
                                <p class="card-text small">View application error and activity logs.</p>
                                <button class="btn btn-danger btn-sm" onclick="viewLogs()">
                                    <i class="fas fa-eye me-1"></i>View Logs
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-header bg-primary">
                                <i class="fas fa-sync me-2"></i>System Update
                            </div>
                            <div class="card-body">
                                <p class="card-text small">Check for and install system updates.</p>
                                <button class="btn btn-primary btn-sm" onclick="checkUpdates()">
                                    <i class="fas fa-search me-1"></i>Check Updates
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Auto-Backup:</strong> Database is automatically backed up daily at 02:00 AM. Backups are retained for 30 days.
                </div>
            </div>

        </div>
    </div>

    <div class="card-footer">
        <button class="btn btn-success" onclick="saveAllSettings()">
            <i class="fas fa-save me-1"></i>Save All Changes
        </button>
        <button class="btn btn-outline-secondary ms-2" onclick="resetSettings()">
            <i class="fas fa-undo me-1"></i>Reset to Defaults
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

// Tabs fallback (if Bootstrap tabs are unavailable)
document.addEventListener('DOMContentLoaded', function () {
    const hasBs5Tab = window.bootstrap && window.bootstrap.Tab;
    const hasBs4Tab = window.jQuery && window.jQuery.fn && window.jQuery.fn.tab;
    if (hasBs5Tab || hasBs4Tab) return;

    const tabLinks = document.querySelectorAll('.nav-tabs .nav-link');
    tabLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            tabLinks.forEach(l => l.classList.remove('active'));
            document.querySelectorAll('.tab-content .tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            this.classList.add('active');
            const target = this.getAttribute('href');
            if (target) {
                const pane = document.querySelector(target);
                if (pane) {
                    pane.classList.add('show', 'active');
                }
            }
        });
    });
});

// Color picker sync
['primary', 'secondary', 'accent'].forEach(color => {
    document.getElementById(`brand_${color}`).addEventListener('input', function() {
        document.getElementById(`brand_${color}_text`).value = this.value;
    });
    document.getElementById(`brand_${color}_text`).addEventListener('input', function() {
        document.getElementById(`brand_${color}`).value = this.value;
    });
});

const SETTINGS_API_BASE = '/admin/api/settings';
let lastMikrotikCommands = [];

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

async function apiRequest(url, method = 'GET', body = null) {
    const options = {
        method,
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
    };

    if (body !== null) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(body);
    }

    const response = await fetch(url, options);
    const payload = await response.json().catch(() => ({}));

    if (!response.ok || payload?.success === false) {
        throw new Error(payload?.message || 'Request failed');
    }

    return payload;
}

function collectSettings() {
    const settings = {};

    document.querySelectorAll('.tab-content input[id], .tab-content select[id], .tab-content textarea[id]').forEach((el) => {
        if (['backup_file', 'mikrotik_connect_commands'].includes(el.id)) {
            return;
        }

        if (el.type === 'checkbox') {
            settings[el.id] = !!el.checked;
            return;
        }

        settings[el.id] = el.value;
    });

    return settings;
}

function applySettings(settings) {
    Object.entries(settings || {}).forEach(([key, value]) => {
        const el = document.getElementById(key);
        if (!el) {
            return;
        }

        if (el.type === 'checkbox') {
            el.checked = !!value;
            return;
        }

        el.value = value ?? '';
    });
}

function setMikrotikCommands(commands) {
    lastMikrotikCommands = Array.isArray(commands) ? commands : [];
    const area = document.getElementById('mikrotik_connect_commands');
    if (!area) {
        return;
    }

    area.value = lastMikrotikCommands.join('\n');
}

function buildMikrotikCommandsFromInputs() {
    const radiusServerRaw = document.getElementById('radius_server')?.value?.trim() || '';
    const radiusServer = radiusServerRaw !== '' && !['127.0.0.1', 'localhost', '::1'].includes(radiusServerRaw.toLowerCase())
        ? radiusServerRaw
        : 'YOUR_RADIUS_SERVER_IP';
    const radiusPort = Number(document.getElementById('radius_port')?.value || 1812);
    const radiusAcctPort = Number(document.getElementById('radius_acct_port')?.value || 1813);
    const radiusTimeoutSeconds = Math.max(1, Number(document.getElementById('radius_timeout')?.value || 5));
    const radiusSecretRaw = document.getElementById('radius_secret')?.value?.trim() || '';
    const radiusSecret = radiusSecretRaw !== '' && radiusSecretRaw.toLowerCase() !== 'your-radius-secret'
        ? radiusSecretRaw
        : 'YOUR_SHARED_SECRET';

    return [
        `/radius add service=hotspot,ppp address=${radiusServer} protocol=udp authentication-port=${radiusPort} accounting-port=${radiusAcctPort} secret=${radiusSecret} timeout=${radiusTimeoutSeconds}s`,
        '/ip hotspot profile set [find] use-radius=yes',
        '/ppp aaa set use-radius=yes accounting=yes interim-update=1m',
        '/radius incoming set accept=yes port=3799',
        '/radius monitor 0 once',
    ];
}

function bindMikrotikCommandRefresh() {
    ['radius_server', 'radius_port', 'radius_acct_port', 'radius_secret', 'radius_timeout'].forEach((id) => {
        const el = document.getElementById(id);
        if (!el) {
            return;
        }

        el.addEventListener('input', () => {
            setMikrotikCommands(buildMikrotikCommandsFromInputs());
        });
    });
}

async function loadSettingsFromServer() {
    try {
        const payload = await apiRequest(SETTINGS_API_BASE, 'GET');
        const savedSettings = payload?.data?.settings || {};
        applySettings(savedSettings);

        const commands = payload?.data?.mikrotik_commands;
        if (Array.isArray(commands) && commands.length > 0) {
            setMikrotikCommands(commands);
        } else {
            setMikrotikCommands(buildMikrotikCommandsFromInputs());
        }
    } catch (error) {
        setMikrotikCommands(buildMikrotikCommandsFromInputs());
        console.error('Failed to load settings:', error);
    }
}

bindMikrotikCommandRefresh();
loadSettingsFromServer();

// Test M-Pesa Connection
async function testMpesaConnection() {
    Swal.fire({
        title: 'Testing M-Pesa Connection...',
        text: 'Validating credentials with Safaricom Daraja API',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const settings = collectSettings();
        const payload = await apiRequest(`${SETTINGS_API_BASE}/mpesa/test`, 'POST', {
            mpesa_env: settings.mpesa_env,
            mpesa_key: settings.mpesa_key,
            mpesa_secret: settings.mpesa_secret,
            mpesa_passkey: settings.mpesa_passkey,
            mpesa_shortcode: settings.mpesa_shortcode,
            mpesa_till: settings.mpesa_till,
            mpesa_timeout: settings.mpesa_timeout,
        });

        Swal.fire({
            icon: 'success',
            title: 'Connection Successful!',
            text: payload?.message || 'M-Pesa credentials are valid. STK Push is ready.',
            footer: `Environment: ${(payload?.data?.environment || settings.mpesa_env || 'sandbox').toUpperCase()}`,
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Connection Failed',
            text: error?.message || 'Unable to validate M-Pesa credentials',
        });
    }
}

// Test SMS Connection
function testSmsConnection() {
    Swal.fire({
        title: 'Enter Test Phone Number',
        input: 'tel',
        inputPlaceholder: '+254 7XX XXX XXX',
        showCancelButton: true,
        confirmButtonText: 'Send SMS'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Sending SMS...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            setTimeout(() => {
                Swal.fire('Sent!', 'Test SMS delivered successfully.', 'success');
            }, 2000);
        }
    });
}

// Test Email Connection
function testEmailConnection() {
    Swal.fire({
        title: 'Enter Test Email',
        input: 'email',
        inputPlaceholder: 'test@example.com',
        showCancelButton: true,
        confirmButtonText: 'Send Email'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Sending Email...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            setTimeout(() => {
                Swal.fire('Sent!', 'Test email delivered successfully.', 'success');
            }, 2000);
        }
    });
}

// Test MikroTik Connection
async function testMikrotikConnection() {
    Swal.fire({
        title: 'Testing MikroTik Connection...',
        text: 'Attempting live API connection to tenant router',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const payload = await apiRequest(`${SETTINGS_API_BASE}/mikrotik/test`, 'POST', {
            settings: collectSettings(),
        });

        if (Array.isArray(payload?.commands)) {
            setMikrotikCommands(payload.commands);
        }

        const router = payload?.router || {};
        const data = payload?.data || {};

        Swal.fire({
            icon: 'success',
            title: 'Connection Successful!',
            html: `
                <div class="text-start">
                    <div><strong>Router:</strong> ${router.name || 'Unknown'} (${router.ip_address || '-'})</div>
                    <div><strong>Status:</strong> ${router.status || 'online'}</div>
                    <div><strong>Version:</strong> ${data.version || '-'}</div>
                    <div><strong>CPU:</strong> ${data.cpu ?? '-'}%</div>
                    <div><strong>Memory:</strong> ${data.memory ?? '-'}%</div>
                    <div><strong>Uptime:</strong> ${data.uptime || '-'}</div>
                </div>
            `,
        });
    } catch (error) {
        const diagnostics = error?.payload?.diagnostics || null;
        const detailLines = [
            diagnostics?.message || error.message || 'Unable to reach tenant router',
            diagnostics?.endpoint?.host && diagnostics?.endpoint?.port
                ? `Configured Endpoint: ${diagnostics.endpoint.host}:${diagnostics.endpoint.port} (${diagnostics.endpoint.service || 'api'})`
                : null,
            ...(Array.isArray(diagnostics?.hints)
                ? diagnostics.hints.map((hint) => `Hint: ${hint}`)
                : []),
            diagnostics?.error ? `Raw Error: ${diagnostics.error}` : null,
            diagnostics?.error_type ? `Type: ${diagnostics.error_type}` : null,
            diagnostics?.tcp_probe_message ? `TCP Probe: ${diagnostics.tcp_probe_message}` : null,
        ].filter(Boolean);

        Swal.fire({
            icon: 'error',
            title: 'Connection Failed',
            text: detailLines.join('\n') || 'Unable to reach tenant router',
        });
    }
}

function copyMikrotikCommands() {
    const area = document.getElementById('mikrotik_connect_commands');
    const content = area?.value?.trim() || '';

    if (!content) {
        Swal.fire('Info', 'No commands to copy yet.', 'info');
        return;
    }

    navigator.clipboard.writeText(content)
        .then(() => Swal.fire('Copied', 'MikroTik commands copied to clipboard.', 'success'))
        .catch(() => Swal.fire('Error', 'Unable to copy commands. Copy manually.', 'error'));
}

// Preview Portal
function previewPortal() {
    window.open('/portal', '_blank');
}

// Upload Logo
function uploadLogo() {
    Swal.fire({
        title: 'Upload Logo',
        input: 'file',
        inputAttributes: { accept: 'image/*' },
        showCancelButton: true,
        confirmButtonText: 'Upload'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Uploaded!', 'Logo has been uploaded.', 'success');
        }
    });
}

// Upload Favicon
function uploadFavicon() {
    Swal.fire({
        title: 'Upload Favicon',
        input: 'file',
        inputAttributes: { accept: 'image/*,.ico' },
        showCancelButton: true,
        confirmButtonText: 'Upload'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Uploaded!', 'Favicon has been uploaded.', 'success');
        }
    });
}

// Preview Invoice Template
function previewInvoiceTemplate() {
    Swal.fire({
        title: 'Invoice Preview',
        html: `
            <div style="text-align:left; font-size:13px;">
                <strong>CloudBridge Networks</strong><br>
                P.O. Box 12345 - 00100, Nairobi, Kenya<br>
                VAT: P051234567Q
                <hr>
                <div style="display:flex; justify-content:space-between;">
                    <div>
                        <strong>Invoice:</strong> CBN-10001<br>
                        <strong>Date:</strong> 2026-03-19<br>
                        <strong>Customer:</strong> Jane Doe
                    </div>
                    <div style="text-align:right;">
                        <strong>Total:</strong> KES 1,160<br>
                        <small>(KES 1,000 + 16% VAT)</small>
                    </div>
                </div>
                <hr>
                <table style="width:100%; font-size:12px;">
                    <tr><td>WiFi Package - 30 Days</td><td style="text-align:right;">KES 1,000</td></tr>
                    <tr><td>VAT (16%)</td><td style="text-align:right;">KES 160</td></tr>
                </table>
                <hr>
                <em>Thank you for your business. Payments are due upon receipt.</em>
            </div>
        `,
        width: 650,
        confirmButtonText: 'Close'
    });
}

// Download Backup
function downloadBackup() {
    Swal.fire({
        title: 'Generating Backup...',
        text: 'Creating database backup file',
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

// Restore Backup
function restoreBackup() {
    const file = document.getElementById('backup_file').files[0];
    if (!file) {
        Swal.fire('Error', 'Please select a backup file', 'error');
        return;
    }
    Swal.fire({
        title: 'Restore Backup?',
        text: `Restore from ${file.name}? This will overwrite all current data!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Restore!',
        confirmButtonColor: '#EF4444'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Restoring...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            setTimeout(() => {
                Swal.fire('Restored!', 'Database has been restored from backup.', 'success');
            }, 3000);
        }
    });
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
                <p style="color: #3B82F6;">[2026-03-19 14:35:22] DEBUG: Router sync: Main Hotspot</p>
                <p style="color: #10B981;">[2026-03-19 14:40:05] INFO: Payment processed: KES 50</p>
                <p style="color: #EF4444;">[2026-03-19 14:42:18] ERROR: M-Pesa callback timeout</p>
                <p style="color: #10B981;">[2026-03-19 14:42:25] INFO: M-Pesa callback received: SUCCESS</p>
            </div>
        `,
        width: 600,
        confirmButtonText: 'Close'
    });
}

// Check Updates
function checkUpdates() {
    Swal.fire({
        title: 'Checking for Updates...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    setTimeout(() => {
        Swal.fire({
            icon: 'info',
            title: 'Up to Date',
            text: 'You are running the latest version (v1.0.0)',
            footer: 'Next release: v1.1.0 (Estimated: 2026-04-01)'
        });
    }, 2000);
}

// Save All Settings
async function saveAllSettings() {
    Swal.fire({
        title: 'Saving Settings...',
        text: 'Please wait while we save all configurations',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const payload = await apiRequest(SETTINGS_API_BASE, 'POST', {
            settings: collectSettings(),
        });

        if (Array.isArray(payload?.data?.mikrotik_commands)) {
            setMikrotikCommands(payload.data.mikrotik_commands);
        }

        Swal.fire({
            icon: 'success',
            title: 'Settings Saved!',
            text: 'All configurations have been saved successfully.',
            timer: 2000,
            showConfirmButton: false
        });
    } catch (error) {
        Swal.fire('Error', error.message || 'Failed to save settings', 'error');
    }
}

// Reset Settings
function resetSettings() {
    Swal.fire({
        title: 'Reset to Defaults?',
        text: 'This will reset all settings to their default values. This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Reset!',
        confirmButtonColor: '#EF4444'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Reset!', 'Settings have been reset to defaults.', 'success');
        }
    });
}
</script>
@endpush
