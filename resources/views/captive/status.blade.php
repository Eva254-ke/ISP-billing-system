<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    @php
        $statusTenant = $payment->tenant;
        $brandingSettings = is_array($statusTenant?->settings ?? null) ? (array) ($statusTenant?->settings ?? []) : [];
        $brandingSettings = (array) ($brandingSettings['branding'] ?? []);
        $accentColor = trim((string) ($payment->tenant?->brand_color_primary ?? ''));
        if (preg_match('/^#(?:[0-9A-Fa-f]{3}){1,2}$/', $accentColor) !== 1) {
            $accentColor = '#0f766e';
        }
        $secondaryColor = trim((string) ($statusTenant?->brand_color_secondary ?? ($brandingSettings['brand_secondary'] ?? '')));
        if (preg_match('/^#(?:[0-9A-Fa-f]{3}){1,2}$/', $secondaryColor) !== 1) {
            $secondaryColor = $accentColor;
        }
        $brandTitle = trim((string) ($statusTenant?->captive_portal_title ?: $statusTenant?->name ?: 'WiFi Portal'));
        $companyName = trim((string) ($statusTenant?->name ?: $brandTitle));
        $logoUrl = trim((string) ($statusTenant?->logo_url ?: ($brandingSettings['brand_logo'] ?? '')));
        $faviconUrl = trim((string) ($brandingSettings['brand_favicon'] ?? ''));
        $termsUrl = trim((string) ($statusTenant?->captive_portal_terms_url ?: ($brandingSettings['brand_terms'] ?? '')));
        $customCss = trim((string) ($statusTenant?->captive_portal_custom_css ?? ''));
        $brandInitials = collect(preg_split('/\s+/', $brandTitle) ?: [])
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr((string) $part, 0, 1)))
            ->implode('');
        if ($brandInitials === '') {
            $brandInitials = 'WI';
        }
    @endphp
    <meta name="theme-color" content="{{ $accentColor }}">
    @if(in_array($statusView, ['pending', 'paid', 'verifying']))
        <meta http-equiv="refresh" content="10">
    @endif
    <title>Connection Status - {{ $brandTitle }}</title>
    @php
        $captiveCssPath = public_path('css/captive-portal.css');
        $captiveCssVersion = file_exists($captiveCssPath) ? filemtime($captiveCssPath) : time();
        $tenantId = (int) ($payment->tenant_id ?? request()->query('tenant_id', 0));
        $paymentMeta = is_array($payment->metadata) ? $payment->metadata : [];
        $paymentClientContext = is_array($paymentMeta['client_context'] ?? null) ? $paymentMeta['client_context'] : [];
        $paymentHotspotContext = is_array($paymentMeta['hotspot_context'] ?? null) ? $paymentMeta['hotspot_context'] : [];
        $storedHotspotContext = session('captive_hotspot_context', []);
        $storedHotspotContext = is_array($storedHotspotContext) ? $storedHotspotContext : [];
        $routeHotspotContext = array_merge($paymentHotspotContext, $storedHotspotContext);
        $routeContext = array_filter([
            'mac' => trim((string) request()->query('mac', $paymentClientContext['mac'] ?? session('captive_client_mac', ''))),
            'ip' => trim((string) request()->query('ip', $paymentClientContext['ip'] ?? session('captive_client_ip', ''))),
            'link-login-only' => trim((string) request()->query('link-login-only', $routeHotspotContext['link_login_only'] ?? '')),
            'link-login' => trim((string) request()->query('link-login', $routeHotspotContext['link_login'] ?? '')),
            'dst' => trim((string) request()->query('dst', $routeHotspotContext['dst'] ?? '')),
            'popup' => trim((string) request()->query('popup', $routeHotspotContext['popup'] ?? '')),
            'chap-id' => trim((string) request()->query('chap-id', $routeHotspotContext['chap_id'] ?? '')),
            'chap-challenge' => trim((string) request()->query('chap-challenge', $routeHotspotContext['chap_challenge'] ?? '')),
            'link-orig' => trim((string) request()->query('link-orig', $routeHotspotContext['link_orig'] ?? '')),
            'link-orig-esc' => trim((string) request()->query('link-orig-esc', $routeHotspotContext['link_orig_esc'] ?? '')),
        ], static fn ($value) => $value !== null && $value !== '');
        $supportPhoneRaw = trim((string) ($payment->tenant?->captive_portal_support_phone ?? ''));
        $supportDigits = preg_replace('/\D+/', '', $supportPhoneRaw);
        if (is_string($supportDigits) && str_starts_with($supportDigits, '0')) {
            $supportDigits = '254' . substr($supportDigits, 1);
        }
        $supportEmail = trim((string) ($payment->tenant?->captive_portal_support_email ?? ''));
        $supportTelHref = (is_string($supportDigits) && $supportDigits !== '')
            ? 'tel:+' . ltrim($supportDigits, '+')
            : ($supportEmail !== '' ? 'mailto:' . $supportEmail : 'tel:+254742939094');
        $supportLabel = (is_string($supportDigits) && $supportDigits !== '')
            ? 'Call support'
            : ($supportEmail !== '' ? 'Email support' : 'Contact support');
        $displayPhone = preg_match('/^(?:0[17]\d{8}|(?:\+?254)[17]\d{8})$/', (string) ($payment->phone ?? '')) === 1
            ? (string) $payment->phone
            : (preg_match('/^(?:0[17]\d{8}|(?:\+?254)[17]\d{8})$/', (string) $phone) === 1 ? (string) $phone : null);
        $statusRoute = route('wifi.status', array_filter(array_merge([
            'phone' => $phone,
            'tenant_id' => $tenantId > 0 ? $tenantId : null,
            'payment' => $payment->id,
        ], $routeContext), static fn ($value) => $value !== null && $value !== ''));
        $statusCheckRoute = route('wifi.status.check', array_filter(array_merge([
            'phone' => $phone,
            'tenant_id' => $tenantId > 0 ? $tenantId : null,
            'payment' => $payment->id,
        ], $routeContext), static fn ($value) => $value !== null && $value !== ''));
        $radiusAutoLogin = is_array($radiusAutoLogin ?? null) ? $radiusAutoLogin : null;
        $radiusPendingReauth = (bool) ($radiusPendingReauth ?? false);
        $shouldAutoPoll = in_array($statusView, ['pending', 'paid', 'verifying'], true);
        $packagesParams = array_filter(array_merge([
            'phone' => $displayPhone,
            'tenant_id' => $tenantId > 0 ? $tenantId : null,
        ], $routeContext), static fn ($value) => $value !== null && $value !== '');
        $expiredPackagesUrl = route('wifi.packages', array_merge($packagesParams, ['expired' => 1]));
        $gatewayStatus = trim((string) ($paymentMeta['daraja_last_status'] ?? ''));
        $pendingStatusLabel = match ($gatewayStatus) {
            'pending_customer_confirmation' => 'Prompt sent',
            'pending_verification', 'query_pending' => 'Verifying with M-Pesa',
            default => 'Waiting for payment',
        };
        $pendingTitle = match ($gatewayStatus) {
            'pending_customer_confirmation' => 'Complete the M-Pesa prompt',
            'pending_verification', 'query_pending' => 'We are confirming your payment request',
            default => 'Confirm the M-Pesa prompt',
        };
        $failureReason = trim((string) (
            $payment->reconciliation_notes
            ?? ($paymentMeta['daraja_failure_reason'] ?? null)
            ?? ($payment->callback_payload['reason'] ?? null)
            ?? ($payment->callback_data['ResultDesc'] ?? null)
            ?? ''
        ));
        if ($failureReason === '') {
            $failureReason = 'The transaction did not complete.';
        }
        $lastQueryAt = $paymentMeta['daraja_last_query_at'] ?? null;
        $lastCheckedLabel = null;
        if (!empty($lastQueryAt)) {
            try {
                $lastCheckedLabel = \Illuminate\Support\Carbon::parse($lastQueryAt)
                    ->timezone(config('app.timezone'))
                    ->format('d M, H:i:s');
            } catch (\Throwable) {
                $lastCheckedLabel = null;
            }
        }
        $statusLabel = match ($statusView) {
            'activated' => 'Connected',
            'paid' => 'Paid',
            'verifying' => 'Verifying',
            'failed' => 'Failed',
            default => 'Pending',
        };
        $continueBrowsingUrl = trim((string) ($continueBrowsingUrl ?? 'https://www.google.com'));
        $continueBrowsingAutoLogin = is_array($continueBrowsingAutoLogin ?? null) ? $continueBrowsingAutoLogin : null;
        $formRadiusAutoLogin = $radiusAutoLogin ?: $continueBrowsingAutoLogin;
    @endphp
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/captive-portal.css?v={{ $captiveCssVersion }}">
    @if($faviconUrl !== '')
        <link rel="icon" href="{{ $faviconUrl }}">
    @endif
    <style>
        :root {
            --cp-primary: {{ $accentColor }};
            --cp-primary-strong: {{ $accentColor }};
            --cp-accent: {{ $secondaryColor }};
        }

        .cp-brand-mark--image {
            background: #ffffff;
            padding: 4px;
        }

        .cp-brand-mark--image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        {!! $customCss !!}
    </style>
</head>
<body>
    <main class="cp-page cp-page-narrow">
        <header class="cp-topbar">
            <div class="cp-brand">
                @if($logoUrl !== '')
                    <div class="cp-brand-mark cp-brand-mark--image"><img src="{{ $logoUrl }}" alt="{{ $companyName }} logo"></div>
                @else
                    <div class="cp-brand-mark">{{ $brandInitials }}</div>
                @endif
                <div class="cp-brand-text">
                    <h1>{{ $brandTitle }}</h1>
                    <p>Connection progress</p>
                </div>
            </div>
            <div class="cp-support"><a class="cp-link-support" href="{{ $supportTelHref }}">{{ $supportLabel }}</a></div>
        </header>

        @if(session('message'))
            <div class="cp-flash success">{{ session('message') }}</div>
        @endif
        @if(session('success'))
            <div class="cp-flash success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="cp-flash error">{{ $errors->first() }}</div>
        @endif

        <article class="cp-card">
            @if($statusView === 'pending')
                <div class="cp-status-head">
                    <div>
                        <span class="cp-status-pill warning">{{ $pendingStatusLabel }}</span>
                        <h2 class="cp-section-title">{{ $pendingTitle }}</h2>
                        <p class="cp-card-subtitle">
                            @if(in_array($gatewayStatus, ['pending_verification', 'query_pending'], true))
                                If the prompt appears or money is deducted, do not pay again. Keep this page open while we check.
                            @elseif($gatewayStatus === 'pending_customer_confirmation')
                                If you already entered your PIN or money is deducted, do not pay again. Keep this page open while we verify and connect you.
                            @else
                                @if($displayPhone)
                                    Use phone <strong>{{ $displayPhone }}</strong> and enter your PIN to continue.
                                @else
                                    Complete the M-Pesa prompt on the number you used to pay.
                                @endif
                            @endif
                        </p>
                    </div>
                    <div class="cp-spinner" aria-hidden="true"></div>
                </div>

                <div class="cp-flow cp-flow-compact">
                    <div class="cp-flow-step is-complete">Package selected</div>
                    <div class="cp-flow-step is-current">Payment confirmation</div>
                    <div class="cp-flow-step">Internet activation</div>
                </div>

                <div class="cp-panel">
                    <h3>What happens next</h3>
                    <p>
                        @if(in_array($gatewayStatus, ['pending_verification', 'query_pending'], true))
                            We will keep checking automatically. If you already received the M-Pesa SMS, you can also use the reconnect option below.
                        @elseif($gatewayStatus === 'pending_customer_confirmation')
                            We have already sent the STK push. Once you approve it on your phone, we will keep checking automatically and connect you.
                        @else
                            After payment confirmation, your internet access will activate automatically.
                        @endif
                    </p>
                </div>

                <div class="cp-actions">
                    <a href="{{ $statusRoute }}" class="cp-btn cp-btn-primary">Refresh Status</a>
                    <a href="{{ route('wifi.reconnect.form', $packagesParams) }}" class="cp-btn cp-btn-soft">Reconnect Options</a>
                </div>

            @elseif($statusView === 'paid')
                <div class="cp-status-head">
                    <div>
                        <span class="cp-status-pill">Payment confirmed</span>
                        <h2 class="cp-section-title">
                            {{ $radiusAutoLogin ? 'Connecting you to WiFi' : ($radiusPendingReauth ? 'Waiting for device re-authentication' : 'Activating internet') }}
                        </h2>
                        <p class="cp-card-subtitle">
                            @if($radiusAutoLogin)
                                Your payment is confirmed. We are sending your hotspot login through RADIUS now.
                            @elseif($radiusPendingReauth)
                                Your payment is confirmed. Keep this page open and refresh or reconnect WiFi so the hotspot can re-check your device through RADIUS.
                            @else
                                Your payment is confirmed. Activation usually takes a few seconds.
                            @endif
                        </p>
                    </div>
                    <div class="cp-spinner" aria-hidden="true"></div>
                </div>

                <div class="cp-flow cp-flow-compact">
                    <div class="cp-flow-step is-complete">Package selected</div>
                    <div class="cp-flow-step is-complete">Payment confirmed</div>
                    <div class="cp-flow-step is-current">{{ $radiusPendingReauth ? 'Hotspot re-check' : 'Internet activation' }}</div>
                </div>

                @if($radiusPendingReauth)
                    <div class="cp-panel">
                        <h3>What to do now</h3>
                        <p>Stay on this page for a few seconds, then refresh the captive page or briefly reconnect WiFi. Access should open as soon as the hotspot sees your paid MAC address.</p>
                    </div>
                @endif

                @if($radiusAutoLogin)
                    <div class="cp-panel">
                        <h3>Hotspot sign-in in progress</h3>
                        <p>We are submitting your hotspot login now. If internet opens immediately, you can start browsing right away without waiting for router or accounting confirmation on this page.</p>
                    </div>
                @endif

                @if(!empty($radiusFallback))
                    <div class="cp-panel">
                        <h3>Fallback credentials</h3>
                        <p>Use only if activation delays: username <strong>{{ $radiusFallback['username'] }}</strong>, password is the same value.</p>
                    </div>
                @endif

                @if($radiusAutoLogin)
                    <div class="cp-actions">
                        <button type="button" id="cpRadiusConnectButton" class="cp-btn cp-btn-primary">Connect Now</button>
                        <a href="{{ $statusRoute }}" class="cp-btn cp-btn-soft">Check Status Now</a>
                    </div>
                @else
                    <a href="{{ $statusRoute }}" class="cp-btn cp-btn-primary cp-btn-block">Check Status Now</a>
                @endif

            @elseif($statusView === 'verifying')
                <div class="cp-status-head">
                    <div>
                        <span class="cp-status-pill warning">Payment under review</span>
                        <h2 class="cp-section-title">We are verifying your payment</h2>
                        <p class="cp-card-subtitle">If M-Pesa already deducted money, do not initiate a second payment. We are still checking for confirmation.</p>
                    </div>
                    <div class="cp-spinner" aria-hidden="true"></div>
                </div>

                <div class="cp-flow cp-flow-compact">
                    <div class="cp-flow-step is-complete">Package selected</div>
                    <div class="cp-flow-step is-current">Payment verification</div>
                    <div class="cp-flow-step">Internet activation</div>
                </div>

                <div class="cp-panel">
                    <h3>What you should do</h3>
                    <p>Wait for automatic confirmation, tap recheck if you have already been charged, or reconnect using the M-Pesa SMS code.</p>
                </div>

                <div class="cp-actions">
                    <a href="{{ route('wifi.status', array_filter(['phone' => $phone, 'tenant_id' => $tenantId > 0 ? $tenantId : null, 'payment' => $payment->id, 'recheck' => 1], static fn ($value) => $value !== null && $value !== '')) }}" class="cp-btn cp-btn-primary">Recheck Payment</a>
                    <a href="{{ route('wifi.reconnect.form', $packagesParams) }}" class="cp-btn cp-btn-soft">Reconnect Options</a>
                </div>

            @elseif($statusView === 'activated')
                <span class="cp-status-pill success">Connected</span>
                <h2 class="cp-section-title">You are connected to the internet</h2>
                <p class="cp-card-subtitle">Your package is active.</p>

                <div class="cp-facts">
                    <div class="cp-fact">
                        <span>Phone</span>
                        <span>{{ $displayPhone ?: 'Not captured' }}</span>
                    </div>
                    <div class="cp-fact">
                        <span>Package</span>
                        <span>{{ $payment->package->name ?? 'N/A' }}</span>
                    </div>
                    <div class="cp-fact">
                        <span>Duration</span>
                        <span>{{ $payment->package->duration_formatted ?? (($payment->package->duration_in_minutes ?? 0) . ' min') }}</span>
                    </div>
                    @if(!empty($payment->mpesa_receipt_number))
                        <div class="cp-fact">
                            <span>M-Pesa Receipt</span>
                            <span>{{ $payment->mpesa_receipt_number }}</span>
                        </div>
                    @endif
                    <div class="cp-fact">
                        <span>Expires</span>
                        <span id="expiresAt">--:--</span>
                    </div>
                </div>

                <div class="cp-countdown" id="countdown">--:--:--</div>

                @if($continueBrowsingAutoLogin)
                    <button type="button" id="cpContinueBrowsingButton" class="cp-btn cp-btn-primary cp-btn-block">Continue Browsing</button>
                @else
                    <a href="{{ $continueBrowsingUrl }}" class="cp-btn cp-btn-primary cp-btn-block">Continue Browsing</a>
                @endif

            @elseif($statusView === 'failed')
                <span class="cp-status-pill error">Payment not completed</span>
                <h2 class="cp-section-title">This payment attempt did not go through</h2>
                <p class="cp-card-subtitle">{{ $failureReason }}</p>

                <div class="cp-panel">
                    <h3>Safe next step</h3>
                    <p>If M-Pesa already deducted money, recheck first or use the SMS transaction code. Only retry payment when you are sure this attempt failed.</p>
                </div>

                <div class="cp-actions">
                    <a href="{{ route('wifi.status', array_filter(['phone' => $phone, 'tenant_id' => $tenantId > 0 ? $tenantId : null, 'payment' => $payment->id, 'recheck' => 1], static fn ($value) => $value !== null && $value !== '')) }}" class="cp-btn cp-btn-outline">I Was Charged, Recheck</a>
                    <a href="{{ route('wifi.packages', $packagesParams) }}" class="cp-btn cp-btn-primary">Try Payment Again</a>
                    <a href="{{ route('wifi.reconnect.form', $packagesParams) }}" class="cp-btn cp-btn-soft">Reconnect Options</a>
                </div>

            @else
                <span class="cp-status-pill">Checking status</span>
                <h2 class="cp-section-title">Status update in progress</h2>
                <p class="cp-card-subtitle">Current state: {{ $payment->status }}</p>

                <a href="{{ $statusRoute }}" class="cp-btn cp-btn-primary cp-btn-block">Refresh</a>
            @endif

            @if($statusView !== 'activated')
                <div class="cp-facts">
                    <div class="cp-fact">
                        <span>Status</span>
                        <span>{{ $statusLabel }}</span>
                    </div>
                    <div class="cp-fact">
                        <span>Phone</span>
                        <span>{{ $displayPhone ?: 'Not captured' }}</span>
                    </div>
                    <div class="cp-fact">
                        <span>Package</span>
                        <span>{{ $payment->package->name ?? ($payment->package_name ?? 'N/A') }}</span>
                    </div>
                    <div class="cp-fact">
                        <span>Amount</span>
                        <span>{{ $payment->currency ?? 'KES' }} {{ number_format((float) $payment->amount, 2) }}</span>
                    </div>
                    <div class="cp-fact">
                        <span>Reference</span>
                        <span>{{ $payment->mpesa_receipt_number ?: $payment->mpesa_checkout_request_id }}</span>
                    </div>
                    @if($lastCheckedLabel)
                        <div class="cp-fact">
                            <span>Last checked</span>
                            <span>{{ $lastCheckedLabel }}</span>
                        </div>
                    @endif
                </div>
            @endif
        </article>

        @if($shouldAutoPoll || $radiusAutoLogin || $continueBrowsingAutoLogin)
            <form id="cpRadiusAutoLoginForm" method="POST" @if($formRadiusAutoLogin) action="{{ $formRadiusAutoLogin['action'] }}" @endif target="cpRadiusAutoLoginFrame" hidden>
                @if($formRadiusAutoLogin)
                    <input type="hidden" name="username" value="{{ $formRadiusAutoLogin['username'] }}">
                    <input type="hidden" name="password" value="{{ $formRadiusAutoLogin['password'] }}">
                    @if(!empty($formRadiusAutoLogin['dst']))
                        <input type="hidden" name="dst" value="{{ $formRadiusAutoLogin['dst'] }}">
                    @endif
                    @if(!empty($formRadiusAutoLogin['popup']))
                        <input type="hidden" name="popup" value="{{ $formRadiusAutoLogin['popup'] }}">
                    @endif
                @endif
            </form>
            <iframe id="cpRadiusAutoLoginFrame" name="cpRadiusAutoLoginFrame" hidden></iframe>
        @endif

        <footer class="cp-footer">
            <p><a class="cp-link-support" href="{{ $supportTelHref }}">{{ $supportLabel }}</a></p>
            @if($termsUrl !== '')
                <p><a class="cp-link-support" href="{{ $termsUrl }}" target="_blank" rel="noopener">Terms and conditions</a></p>
            @endif
            <p>{{ $companyName }}</p>
        </footer>
    </main>

    <script>
        @if($shouldAutoPoll || $radiusAutoLogin || $continueBrowsingAutoLogin)
        let radiusAutoLogin = @json($radiusAutoLogin);
        let continueBrowsingAutoLogin = @json($continueBrowsingAutoLogin);
        const continueBrowsingUrl = @json($continueBrowsingUrl);
        const radiusAutoLoginKey = `cp-radius-autologin:${@json((int) $payment->id)}`;
        const expiredPackagesUrl = @json($expiredPackagesUrl);

        function leftRotate(value, amount) {
            return (value << amount) | (value >>> (32 - amount));
        }

        function toWordArray(input) {
            const length = input.length;
            const words = [];

            for (let i = 0; i < length; i += 1) {
                words[i >> 2] = words[i >> 2] || 0;
                words[i >> 2] |= input.charCodeAt(i) << ((i % 4) * 8);
            }

            words[length >> 2] = words[length >> 2] || 0;
            words[length >> 2] |= 0x80 << ((length % 4) * 8);
            words[(((length + 8) >> 6) + 1) * 16 - 2] = length * 8;

            return words;
        }

        function md5Hex(input) {
            const words = toWordArray(input);
            const table = Array.from({ length: 64 }, (_, index) => Math.floor(Math.abs(Math.sin(index + 1)) * 0x100000000));

            let a = 0x67452301;
            let b = 0xefcdab89;
            let c = 0x98badcfe;
            let d = 0x10325476;

            for (let i = 0; i < words.length; i += 16) {
                const originalA = a;
                const originalB = b;
                const originalC = c;
                const originalD = d;

                for (let step = 0; step < 64; step += 1) {
                    let f = 0;
                    let g = step;

                    if (step < 16) {
                        f = (b & c) | ((~b) & d);
                        g = step;
                    } else if (step < 32) {
                        f = (d & b) | ((~d) & c);
                        g = (5 * step + 1) % 16;
                    } else if (step < 48) {
                        f = b ^ c ^ d;
                        g = (3 * step + 5) % 16;
                    } else {
                        f = c ^ (b | (~d));
                        g = (7 * step) % 16;
                    }

                    const shifts = [
                        7, 12, 17, 22, 7, 12, 17, 22, 7, 12, 17, 22, 7, 12, 17, 22,
                        5, 9, 14, 20, 5, 9, 14, 20, 5, 9, 14, 20, 5, 9, 14, 20,
                        4, 11, 16, 23, 4, 11, 16, 23, 4, 11, 16, 23, 4, 11, 16, 23,
                        6, 10, 15, 21, 6, 10, 15, 21, 6, 10, 15, 21, 6, 10, 15, 21
                    ];

                    const temp = d;
                    d = c;
                    c = b;
                    b = (b + leftRotate((a + f + table[step] + (words[i + g] || 0)) >>> 0, shifts[step])) >>> 0;
                    a = temp;
                }

                a = (a + originalA) >>> 0;
                b = (b + originalB) >>> 0;
                c = (c + originalC) >>> 0;
                d = (d + originalD) >>> 0;
            }

            const digestWords = [a, b, c, d];

            return digestWords.map((value) => {
                let out = '';
                for (let i = 0; i < 4; i += 1) {
                    out += (`0${((value >> (i * 8)) & 0xff).toString(16)}`).slice(-2);
                }
                return out;
            }).join('');
        }

        function hexToBinary(hex) {
            if (!/^[0-9a-f]+$/i.test(hex) || hex.length % 2 !== 0) {
                return hex;
            }

            let output = '';
            for (let i = 0; i < hex.length; i += 2) {
                output += String.fromCharCode(parseInt(hex.slice(i, i + 2), 16));
            }

            return output;
        }

        function percentHexToBinary(value) {
            return String(value).replace(/%([0-9a-f]{2})/gi, (_, hex) => String.fromCharCode(parseInt(hex, 16)));
        }

        function slashEscapesToBinary(value) {
            return String(value)
                .replace(/\\x([0-9a-f]{2})/gi, (_, hex) => String.fromCharCode(parseInt(hex, 16)))
                .replace(/\\([0-7]{1,3})/g, (_, octal) => String.fromCharCode(parseInt(octal, 8) & 0xff));
        }

        function decodeBinaryValue(value) {
            if (value === null || value === undefined || value === '') {
                return '';
            }

            let normalized = String(value);

            if (normalized.includes('%')) {
                normalized = percentHexToBinary(normalized);
            }

            if (normalized.includes('\\')) {
                normalized = slashEscapesToBinary(normalized);
            }

            if (/^[0-9a-f]+$/i.test(normalized) && normalized.length % 2 === 0) {
                return hexToBinary(normalized);
            }

            return normalized;
        }

        function decodeChapId(value) {
            const normalized = decodeBinaryValue(value);

            if (normalized.length === 1) {
                return normalized;
            }

            if (/^[0-9a-f]{1,2}$/i.test(String(value))) {
                return String.fromCharCode(parseInt(String(value), 16));
            }

            if (/^[0-9]{1,3}$/.test(String(value))) {
                return String.fromCharCode(parseInt(String(value), 10) & 0xff);
            }

            return normalized.charAt(0);
        }

        function buildChapResponse(chapId, chapChallenge, password) {
            const chapIdBinary = decodeChapId(chapId);
            const chapChallengeBinary = decodeBinaryValue(chapChallenge);

            return md5Hex(chapIdBinary + password + chapChallengeBinary);
        }

        function hasRecentRadiusAutoLoginAttempt(windowMs = 8000) {
            try {
                const lastAttemptAt = Number(sessionStorage.getItem(radiusAutoLoginKey) || 0);
                return lastAttemptAt > 0 && (Date.now() - lastAttemptAt) < windowMs;
            } catch (storageError) {
                return false;
            }
        }

        function shouldUseTopLevelRadiusAutoLogin(loginPayload) {
            if (!loginPayload || !loginPayload.action) {
                return false;
            }

            try {
                const actionUrl = new URL(loginPayload.action, window.location.href);

                // Router hotspot login is usually hosted on a different origin from
                // the captive app. Submitting that login in a hidden iframe is often
                // delayed or ignored by browsers, which leaves users stuck waiting
                // for accounting updates instead of finishing captive auth.
                return actionUrl.origin !== window.location.origin;
            } catch (urlError) {
                return false;
            }
        }

        function buildRadiusAutoLoginNavigationUrl(loginPayload) {
            if (
                !loginPayload
                || !loginPayload.action
                || loginPayload.chap_id
                || loginPayload.chap_challenge
            ) {
                return null;
            }

            try {
                const loginUrl = new URL(loginPayload.action, window.location.href);
                loginUrl.searchParams.set('username', String(loginPayload.username || ''));
                loginUrl.searchParams.set('password', String(loginPayload.password || ''));

                if (loginPayload.dst) {
                    loginUrl.searchParams.set('dst', String(loginPayload.dst));
                }

                if (loginPayload.popup) {
                    loginUrl.searchParams.set('popup', String(loginPayload.popup));
                }

                return loginUrl.toString();
            } catch (urlError) {
                return null;
            }
        }

        function setHiddenField(form, name, value) {
            const existing = Array.from(form.querySelectorAll('input')).find((input) => input.name === name);

            if (value === null || value === undefined || value === '') {
                existing?.remove();
                return;
            }

            const field = existing || document.createElement('input');
            field.type = 'hidden';
            field.name = name;
            field.value = String(value);

            if (!existing) {
                form.appendChild(field);
            }
        }

        function shouldUseGetRadiusAutoLogin(loginPayload) {
            return shouldUseTopLevelRadiusAutoLogin(loginPayload)
                && !loginPayload?.chap_id
                && !loginPayload?.chap_challenge;
        }

        function hydrateRadiusAutoLoginForm(form, loginPayload) {
            form.method = shouldUseGetRadiusAutoLogin(loginPayload) ? 'GET' : 'POST';
            form.action = loginPayload.action || '';
            form.target = shouldUseTopLevelRadiusAutoLogin(loginPayload) ? '_top' : 'cpRadiusAutoLoginFrame';

            setHiddenField(form, 'username', loginPayload.username || '');
            setHiddenField(form, 'password', loginPayload.password || '');
            setHiddenField(form, 'dst', loginPayload.dst || '');
            setHiddenField(form, 'popup', loginPayload.popup || '');
            setHiddenField(form, 'response', null);
            setHiddenField(form, 'chap-id', null);
            setHiddenField(form, 'chap-challenge', null);
        }

        function submitRadiusAutoLogin(loginPayload = null) {
            if (loginPayload && typeof loginPayload === 'object') {
                radiusAutoLogin = loginPayload;
            }

            if (!radiusAutoLogin || !radiusAutoLogin.action) {
                return false;
            }

            try {
                sessionStorage.setItem(radiusAutoLoginKey, String(Date.now()));
            } catch (storageError) {
                // Ignore storage failures.
            }

            const form = document.getElementById('cpRadiusAutoLoginForm');
            if (form) {
                hydrateRadiusAutoLoginForm(form, radiusAutoLogin);

                const passwordInput = form.querySelector('input[name="password"]');

                if (
                    passwordInput
                    && radiusAutoLogin.chap_id
                    && radiusAutoLogin.chap_challenge
                ) {
                    passwordInput.value = buildChapResponse(
                        radiusAutoLogin.chap_id,
                        radiusAutoLogin.chap_challenge,
                        String(radiusAutoLogin.password || '')
                    );
                }

                form.submit();

                return true;
            }

            const navigationUrl = shouldUseTopLevelRadiusAutoLogin(radiusAutoLogin)
                ? buildRadiusAutoLoginNavigationUrl(radiusAutoLogin)
                : null;

            if (navigationUrl) {
                window.location.replace(navigationUrl);
                return true;
            }

            return false;
        }

        document.getElementById('cpRadiusConnectButton')?.addEventListener('click', () => {
            submitRadiusAutoLogin();
        });

        document.getElementById('cpContinueBrowsingButton')?.addEventListener('click', () => {
            if (!submitRadiusAutoLogin(continueBrowsingAutoLogin)) {
                window.location.href = continueBrowsingUrl;
            }
        });

        if (@json($shouldAutoPoll) && radiusAutoLogin && !hasRecentRadiusAutoLoginAttempt()) {
            setTimeout(() => {
                submitRadiusAutoLogin();
            }, 150);
        }
        @endif

        @if($shouldAutoPoll)
        let currentStatus = @json($statusView);
        let statusPollInFlight = false;

        const pollStatus = async () => {
            if (statusPollInFlight) {
                return;
            }

            statusPollInFlight = true;
            try {
                const response = await fetch(@json($statusCheckRoute), {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store'
                });

                if (!response.ok) {
                    return;
                }

                let payload = null;
                try {
                    payload = await response.json();
                } catch (jsonError) {
                    return;
                }

                if (!payload || typeof payload !== 'object') {
                    return;
                }

                if (typeof payload.redirect_url === 'string' && payload.redirect_url !== '') {
                    window.location.replace(payload.redirect_url);
                    return;
                }

                if (payload.session_active === true) {
                    if (currentStatus !== 'activated') {
                        window.location.reload();
                    }
                    return;
                }

                const nextStatus = (payload.status || '').toLowerCase();
                const nextRadiusAutoLogin = payload.radius_auto_login && typeof payload.radius_auto_login === 'object'
                    ? payload.radius_auto_login
                    : null;
                const nextRadiusPendingReauth = payload.radius_pending_reauth === true;

                if (nextStatus === 'paid' && nextRadiusAutoLogin) {
                    currentStatus = 'paid';

                    if (!hasRecentRadiusAutoLoginAttempt()) {
                        submitRadiusAutoLogin(nextRadiusAutoLogin);
                    }

                    return;
                }

                if (nextStatus === 'paid' && nextRadiusPendingReauth && currentStatus !== 'paid') {
                    window.location.reload();
                    return;
                }

                if (nextStatus !== '' && nextStatus !== currentStatus) {
                    window.location.reload();
                }
            } catch (error) {
                // Keep silent, meta refresh acts as fallback.
            } finally {
                statusPollInFlight = false;
            }
        };

        setTimeout(() => {
            void pollStatus();
        }, 800);

        setInterval(() => {
            void pollStatus();
        }, 2000);
        @endif

        @if($statusView === 'activated' && !empty($activeSession?->expires_at))
        const expiredPackagesUrl = @json($expiredPackagesUrl);
        const expiresAt = new Date('{{ $activeSession->expires_at->toIso8601String() }}').getTime();
        let expiryRedirectTriggered = false;

        function updateCountdown() {
            const expiresNode = document.getElementById('expiresAt');
            const countdownNode = document.getElementById('countdown');
            if (!expiresNode || !countdownNode) {
                return;
            }

            const distance = expiresAt - Date.now();

            if (distance <= 0) {
                countdownNode.textContent = 'Session Expired';
                expiresNode.textContent = 'Expired';
                if (!expiryRedirectTriggered) {
                    expiryRedirectTriggered = true;
                    window.location.replace(expiredPackagesUrl);
                }
                return;
            }

            const totalSeconds = Math.floor(distance / 1000);
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;

            countdownNode.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            expiresNode.textContent = new Date(expiresAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
        @endif
    </script>
</body>
</html>
