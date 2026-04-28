<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    @php
        $accentColor = trim((string) ($tenant?->brand_color_primary ?? ''));
        if (preg_match('/^#(?:[0-9A-Fa-f]{3}){1,2}$/', $accentColor) !== 1) {
            $accentColor = '#0f766e';
        }
    @endphp
    <meta name="theme-color" content="{{ $accentColor }}">
    <title>{{ $tenant?->name ?: 'CloudBridge WiFi' }} - Reconnect</title>
    @php
        $captiveCssPath = public_path('css/captive-portal.css');
        $captiveCssVersion = file_exists($captiveCssPath) ? filemtime($captiveCssPath) : time();
        $tenantId = (int) ($tenant?->id ?? request()->query('tenant_id', 0));
        $supportPhoneRaw = trim((string) ($tenant?->captive_portal_support_phone ?? ''));
        $supportDigits = preg_replace('/\D+/', '', $supportPhoneRaw);
        if (is_string($supportDigits) && str_starts_with($supportDigits, '0')) {
            $supportDigits = '254' . substr($supportDigits, 1);
        }
        $supportTelHref = (is_string($supportDigits) && $supportDigits !== '')
            ? 'tel:+' . ltrim($supportDigits, '+')
            : 'tel:+254742939094';
        $reconnectActionParams = array_filter([
            'tenant_id' => old('tenant_id', $tenantId > 0 ? $tenantId : request()->query('tenant_id')),
        ], static fn ($value) => $value !== null && $value !== '');
        $tenantIdValue = (string) ($reconnectActionParams['tenant_id'] ?? '');
        $routePhone = preg_match('/^(?:0[17]\d{8}|(?:\+?254)[17]\d{8})$/', (string) old('phone', $phone ?? '')) === 1
            ? (string) old('phone', $phone ?? '')
            : '';
        $packagesParams = array_filter([
            'tenant_id' => $tenantId > 0 ? $tenantId : request()->query('tenant_id'),
            'phone' => $routePhone !== '' ? $routePhone : null,
        ], static fn ($value) => $value !== null && $value !== '');
        $clientMacValue = trim((string) old('mac', $clientMac ?? request()->query('mac', session('captive_client_mac', ''))));
        $clientIpValue = trim((string) old('ip', $clientIp ?? request()->query('ip', session('captive_client_ip', ''))));
        $hotspotContext = is_array($hotspotContext ?? null) ? $hotspotContext : [];
        $hotspotFieldValues = [
            'link-login-only' => trim((string) old('link-login-only', $hotspotContext['link_login_only'] ?? '')),
            'link-login' => trim((string) old('link-login', $hotspotContext['link_login'] ?? '')),
            'dst' => trim((string) old('dst', $hotspotContext['dst'] ?? '')),
            'popup' => trim((string) old('popup', $hotspotContext['popup'] ?? '')),
            'chap-id' => trim((string) old('chap-id', $hotspotContext['chap_id'] ?? '')),
            'chap-challenge' => trim((string) old('chap-challenge', $hotspotContext['chap_challenge'] ?? '')),
            'link-orig' => trim((string) old('link-orig', $hotspotContext['link_orig'] ?? '')),
            'link-orig-esc' => trim((string) old('link-orig-esc', $hotspotContext['link_orig_esc'] ?? '')),
        ];
        $allowMpesaReconnect = (bool) ($tenant?->captive_portal_allow_mpese_code_reconnect ?? true);
        $allowVoucherRedemption = (bool) ($tenant?->captive_portal_allow_voucher_redemption ?? true);
        $voucherPrefixValue = \App\Models\Voucher::normalizePrefix($voucherPrefix ?? 'CB-WIFI') ?? 'CB-WIFI';
    @endphp
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/captive-portal.css?v={{ $captiveCssVersion }}">
    <style>
        :root {
            --cp-primary: {{ $accentColor }};
            --cp-primary-strong: {{ $accentColor }};
        }

        .cp-code-input {
            display: flex;
            align-items: center;
            gap: 0;
            border: 1px solid rgba(15, 23, 42, 0.12);
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
        }

        .cp-code-prefix {
            padding: 0.95rem 0.9rem;
            background: rgba(15, 118, 110, 0.08);
            color: #0f172a;
            font-weight: 700;
            white-space: nowrap;
            border-right: 1px solid rgba(15, 23, 42, 0.08);
        }

        .cp-code-input input {
            border: 0;
            border-radius: 0;
            box-shadow: none;
            flex: 1 1 auto;
        }

        .cp-field-hint {
            display: block;
            margin-top: 0.45rem;
            color: #64748b;
            font-size: 0.88rem;
        }
    </style>
</head>
<body>
    <main class="cp-page cp-page-narrow">
        <header class="cp-topbar">
            <div class="cp-brand">
                <div class="cp-brand-mark">CB</div>
                <div class="cp-brand-text">
                    <h1>{{ $tenant?->name ?: 'CloudBridge WiFi' }}</h1>
                    <p>Use a code to reconnect.</p>
                </div>
            </div>
            <div class="cp-support"><a class="cp-link-support" href="{{ $supportTelHref }}">Call support</a></div>
        </header>

        @if(!empty($tenantResolutionError))
            <div class="cp-flash error">{{ $tenantResolutionError }}</div>
        @endif
        @if($errors->any())
            <div class="cp-flash error">{{ $errors->first() }}</div>
        @endif
        @if(session('success'))
            <div class="cp-flash success">{{ session('success') }}</div>
        @endif

        <article class="cp-card">
            <div class="cp-flow">
                <div class="cp-flow-step">1. Enter code</div>
                <div class="cp-flow-step is-current">2. Verify</div>
                <div class="cp-flow-step">3. Connect</div>
            </div>

            <h2 class="cp-section-title">Reconnect internet</h2>
            <p class="cp-card-subtitle">Use one option only. You do not need to enter the phone number again on this screen.</p>

            @if(!$allowMpesaReconnect && !$allowVoucherRedemption)
                <div class="cp-panel">
                    <h3>Reconnect is not available</h3>
                    <p>This hotspot is not accepting reconnect codes right now. Choose a package instead.</p>
                </div>
            @else
                <div class="cp-form-stack">
                    @if($allowMpesaReconnect)
                        <section class="cp-form-card">
                            <h3 class="cp-form-title">Reconnect with M-Pesa code</h3>
                            <form method="POST" action="{{ route('wifi.reconnect', $reconnectActionParams) }}">
                                @csrf
                                <input type="hidden" name="tenant_id" value="{{ $tenantIdValue }}">
                                <input type="hidden" name="phone" value="{{ $routePhone }}">
                                <input type="hidden" name="mac" value="{{ $clientMacValue }}">
                                <input type="hidden" name="ip" value="{{ $clientIpValue }}">
                                @foreach($hotspotFieldValues as $fieldName => $fieldValue)
                                    <input type="hidden" name="{{ $fieldName }}" value="{{ $fieldValue }}">
                                @endforeach
                                <div class="cp-field">
                                    <label for="mpesaCode">M-Pesa Transaction Code</label>
                                    <input id="mpesaCode" type="text" name="mpesa_code" placeholder="QGH45XYZ" value="{{ old('mpesa_code', '') }}" required maxlength="32" autocomplete="off">
                                </div>
                                <button type="submit" class="cp-btn cp-btn-primary cp-btn-block">Verify and Connect</button>
                            </form>
                        </section>
                    @endif

                    @if($allowVoucherRedemption)
                        <section class="cp-form-card">
                            <h3 class="cp-form-title">Redeem voucher</h3>
                            <form method="POST" action="{{ route('wifi.reconnect', $reconnectActionParams) }}">
                                @csrf
                                <input type="hidden" name="tenant_id" value="{{ $tenantIdValue }}">
                                <input type="hidden" name="phone" value="{{ $routePhone }}">
                                <input type="hidden" name="voucher_prefix" value="{{ $voucherPrefixValue }}">
                                <input type="hidden" name="mac" value="{{ $clientMacValue }}">
                                <input type="hidden" name="ip" value="{{ $clientIpValue }}">
                                @foreach($hotspotFieldValues as $fieldName => $fieldValue)
                                    <input type="hidden" name="{{ $fieldName }}" value="{{ $fieldValue }}">
                                @endforeach
                                <div class="cp-field">
                                    <label for="voucherCode">Voucher Code</label>
                                    <div class="cp-code-input">
                                        <span class="cp-code-prefix">{{ $voucherPrefixValue }}-</span>
                                        <input id="voucherCode" type="text" name="voucher_code" placeholder="123456" value="{{ old('voucher_code', '') }}" required maxlength="64" autocomplete="off" inputmode="numeric" data-voucher-prefix="{{ $voucherPrefixValue }}">
                                    </div>
                                    <small class="cp-field-hint">Enter the 6 digits after the prefix. Full voucher codes still work too.</small>
                                </div>
                                <button type="submit" class="cp-btn cp-btn-soft cp-btn-block">Redeem Voucher</button>
                            </form>
                        </section>
                    @endif
                </div>
            @endif
        </article>

        <article class="cp-card cp-card-compact">
            <h3 class="cp-section-subtitle">Need a package instead?</h3>
            <a href="{{ route('wifi.packages', $packagesParams) }}" class="cp-btn cp-btn-outline cp-btn-block">Go to Packages</a>
        </article>

        <footer class="cp-footer">
            <p><a class="cp-link-support" href="{{ $supportTelHref }}">Call support</a></p>
            <p>Engineered by Engineer Omwenga Evans</p>
        </footer>
    </main>

    <script>
        document.getElementById('mpesaCode')?.addEventListener('input', function () {
            this.value = this.value.toUpperCase().trimStart();
        });

        const voucherCodeInput = document.getElementById('voucherCode');
        const voucherPrefix = String(voucherCodeInput?.dataset.voucherPrefix || '').toUpperCase();
        voucherCodeInput?.addEventListener('input', function () {
            let value = this.value.toUpperCase().trim().replace(/\s+/g, '');

            if (voucherPrefix !== '' && value.startsWith(voucherPrefix + '-')) {
                value = value.slice(voucherPrefix.length + 1);
            } else if (voucherPrefix !== '' && value.startsWith(voucherPrefix)) {
                value = value.slice(voucherPrefix.length);
            }

            value = value.replace(/^-+/, '');

            if (/^\d+$/.test(value)) {
                value = value.slice(0, 6);
            }

            this.value = value;
        });
    </script>
</body>
</html>
