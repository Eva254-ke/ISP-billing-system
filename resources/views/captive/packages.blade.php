<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    @php
        $brandingSettings = is_array($tenant?->settings ?? null) ? (array) ($tenant?->settings ?? []) : [];
        $brandingSettings = (array) ($brandingSettings['branding'] ?? []);
        $accentColor = trim((string) ($tenant?->brand_color_primary ?? ''));
        if (preg_match('/^#(?:[0-9A-Fa-f]{3}){1,2}$/', $accentColor) !== 1) {
            $accentColor = '#0f766e';
        }
        $secondaryColor = trim((string) ($tenant?->brand_color_secondary ?? ($brandingSettings['brand_secondary'] ?? '')));
        if (preg_match('/^#(?:[0-9A-Fa-f]{3}){1,2}$/', $secondaryColor) !== 1) {
            $secondaryColor = $accentColor;
        }
        $brandTitle = trim((string) ($tenant?->captive_portal_title ?: $tenant?->name ?: 'WiFi Portal'));
        $companyName = trim((string) ($tenant?->name ?: $brandTitle));
        $logoUrl = trim((string) ($tenant?->logo_url ?: ($brandingSettings['brand_logo'] ?? '')));
        $faviconUrl = trim((string) ($brandingSettings['brand_favicon'] ?? ''));
        $welcomeMessage = trim((string) ($tenant?->captive_portal_welcome_message ?: ($brandingSettings['brand_welcome'] ?? 'Choose a package and pay.')));
        $termsUrl = trim((string) ($tenant?->captive_portal_terms_url ?: ($brandingSettings['brand_terms'] ?? '')));
        $customCss = trim((string) ($tenant?->captive_portal_custom_css ?? ''));
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
    <title>{{ $brandTitle }} - WiFi Packages</title>
    @php
        $captiveCssPath = public_path('css/captive-portal.css');
        $captiveCssVersion = file_exists($captiveCssPath) ? filemtime($captiveCssPath) : time();
        $tenantId = (int) ($tenant?->id ?? request()->query('tenant_id', 0));
        $supportPhoneRaw = trim((string) ($tenant?->captive_portal_support_phone ?? ''));
        $supportDigits = preg_replace('/\D+/', '', $supportPhoneRaw);
        if (is_string($supportDigits) && str_starts_with($supportDigits, '0')) {
            $supportDigits = '254' . substr($supportDigits, 1);
        }
        $supportEmail = trim((string) ($tenant?->captive_portal_support_email ?? ''));
        $supportTelHref = (is_string($supportDigits) && $supportDigits !== '')
            ? 'tel:+' . ltrim($supportDigits, '+')
            : ($supportEmail !== '' ? 'mailto:' . $supportEmail : 'tel:+254742939094');
        $supportLabel = (is_string($supportDigits) && $supportDigits !== '')
            ? 'Call support'
            : ($supportEmail !== '' ? 'Email support' : 'Contact support');
        $selectedPackageId = (int) old('package_id', 0);
        $paymentActionParams = array_filter([
            'tenant_id' => old('tenant_id', $tenantId > 0 ? $tenantId : request()->query('tenant_id')),
        ], static fn ($value) => $value !== null && $value !== '');
        $tenantIdValue = (string) ($paymentActionParams['tenant_id'] ?? '');
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
        $routePhone = preg_match('/^(?:0[17]\d{8}|(?:\+?254)[17]\d{8})$/', (string) old('phone', $phone ?? '')) === 1
            ? (string) old('phone', $phone ?? '')
            : null;
        $reconnectParams = array_filter([
            'tenant_id' => $tenantId > 0 ? $tenantId : request()->query('tenant_id'),
            'phone' => $routePhone,
        ], static fn ($value) => $value !== null && $value !== '');
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

        .cp-step[hidden] {
            display: none !important;
        }

        .cp-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: none;
            border: none;
            color: var(--cp-primary);
            font: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
            margin-bottom: 12px;
        }

        .cp-back-btn svg {
            width: 18px;
            height: 18px;
        }

        {!! $customCss !!}
    </style>
</head>
<body>
    <main class="cp-page">
        <header class="cp-topbar">
            <div class="cp-brand">
                @if($logoUrl !== '')
                    <div class="cp-brand-mark cp-brand-mark--image"><img src="{{ $logoUrl }}" alt="{{ $companyName }} logo"></div>
                @else
                    <div class="cp-brand-mark">{{ $brandInitials }}</div>
                @endif
                <div class="cp-brand-text">
                    <h1>{{ $brandTitle }}</h1>
                    <p>{{ $welcomeMessage !== '' ? $welcomeMessage : 'Choose a package and pay.' }}</p>
                </div>
            </div>
            <div class="cp-support"><a class="cp-link-support" href="{{ $supportTelHref }}">{{ $supportLabel }}</a></div>
        </header>

        @if(session('error'))
            <div class="cp-flash error">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="cp-flash success">{{ session('success') }}</div>
        @endif
        @if(session('message'))
            <div class="cp-flash success">{{ session('message') }}</div>
        @endif
        @if(request()->boolean('expired'))
            <div class="cp-flash error">Your previous session expired. Select a package and pay again to reconnect.</div>
        @endif
        @if(!empty($tenantResolutionError))
            <div class="cp-flash error">{{ $tenantResolutionError }}</div>
        @endif
        @if($errors->any())
            <div class="cp-flash error">{{ $errors->first() }}</div>
        @endif

        @if(isset($activeSession) && $activeSession)
            @php
                $statusPhone = $activeSession->phone ?? $phone;
            @endphp
            <article class="cp-card">
                <span class="cp-status-pill success">Connected</span>
                <h2 class="cp-section-title">You are already connected</h2>
                <p class="cp-card-subtitle">This device still has time left.</p>

                <div class="cp-facts">
                    <div class="cp-fact">
                        <span>Phone</span>
                        <span>{{ $statusPhone ?: 'Not captured' }}</span>
                    </div>
                    <div class="cp-fact">
                        <span>Expires</span>
                        <span id="activeExpires">{{ $activeSession->expires_at?->format('H:i') ?? 'N/A' }}</span>
                    </div>
                    <div class="cp-fact">
                        <span>Time Left</span>
                        <span id="timeLeft">--:--:--</span>
                    </div>
                </div>

                @if($statusPhone)
                    <a href="{{ route('wifi.status', array_filter(['phone' => $statusPhone, 'tenant_id' => $tenantId > 0 ? $tenantId : null], static fn ($value) => $value !== null && $value !== '')) }}" class="cp-btn cp-btn-primary cp-btn-block">View Connection Status</a>
                @endif
            </article>
        @else
            <article class="cp-card">
                <div class="cp-step" id="cpStep1">
                    <div class="cp-flow">
                        <div class="cp-flow-step is-current">1. Choose package</div>
                        <div class="cp-flow-step">2. Enter phone & pay</div>
                        <div class="cp-flow-step">3. Get connected</div>
                    </div>

                    <h2 class="cp-section-title">Choose a package</h2>
                    <p class="cp-card-subtitle">Select a package, then enter the Safaricom number you want to pay with.</p>

                    @if($packages->isEmpty())
                        <div class="cp-panel">
                            <h3>No packages available</h3>
                            <p>Please contact support to activate packages for this hotspot.</p>
                        </div>
                    @else
                        <div class="cp-grid cp-package-grid">
                            @foreach($packages as $pkg)
                                <button
                                    type="button"
                                    class="cp-package-card js-package-card"
                                    data-package-id="{{ $pkg->id }}"
                                    data-package-name="{{ $pkg->name }}"
                                    data-package-price="KES {{ number_format((float) $pkg->price, 0) }}"
                                    data-package-duration="{{ $pkg->duration_formatted }}"
                                    data-package-speed="{{ $pkg->bandwidth_formatted }}"
                                    aria-pressed="false">
                                    <div class="cp-package-name">{{ $pkg->name }}</div>
                                    <div class="cp-package-price">KES {{ number_format((float) $pkg->price, 0) }}</div>
                                    <div class="cp-package-meta">{{ $pkg->duration_formatted }}</div>
                                    <div class="cp-package-meta">{{ $pkg->bandwidth_formatted }}</div>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="cp-step" id="cpStep2" hidden>
                    <div class="cp-flow">
                        <div class="cp-flow-step is-complete">1. Choose package</div>
                        <div class="cp-flow-step is-current">2. Enter phone & pay</div>
                        <div class="cp-flow-step">3. Get connected</div>
                    </div>

                    <button type="button" class="cp-back-btn" id="cpBackBtn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        Back to packages
                    </button>

                    <h2 class="cp-section-title">Enter your details to pay</h2>
                    <p class="cp-card-subtitle">Use your real name and the Safaricom number that will receive the M-Pesa prompt.</p>

                    <div class="cp-pay-summary" id="cpPaySummary">
                        <div class="cp-pay-summary-name" id="cpSummaryName">--</div>
                        <div class="cp-pay-summary-price" id="cpSummaryPrice">--</div>
                        <div class="cp-pay-summary-meta" id="cpSummaryMeta">--</div>
                    </div>

                    <form method="POST" action="{{ route('wifi.pay', $paymentActionParams) }}" id="cpPaymentForm" class="cp-payment-form">
                        @csrf
                        <input type="hidden" name="package_id" id="cpPackageId" value="">
                        <input type="hidden" name="tenant_id" value="{{ $tenantIdValue }}">
                        <input type="hidden" name="mac" value="{{ $clientMacValue }}">
                        <input type="hidden" name="ip" value="{{ $clientIpValue }}">
                        @foreach($hotspotFieldValues as $fieldName => $fieldValue)
                            <input type="hidden" name="{{ $fieldName }}" value="{{ $fieldValue }}">
                        @endforeach

                        <div class="cp-field">
                            <label for="cpCustomerName">Your Name</label>
                            <input
                                id="cpCustomerName"
                                type="text"
                                name="customer_name"
                                placeholder="Evans"
                                value="{{ old('customer_name', '') }}"
                                required
                                maxlength="120"
                                autocomplete="name">
                        </div>

                        <div class="cp-field">
                            <label for="cpPhone">Safaricom M-Pesa Number</label>
                            <input
                                id="cpPhone"
                                type="tel"
                                name="phone"
                                placeholder="Safaricom number"
                                value="{{ old('phone', $routePhone ?? '') }}"
                                required
                                pattern="(?:0[17]\d{8}|(?:\+?254)[17]\d{8})"
                                autocomplete="tel"
                                inputmode="tel">
                        </div>

                        <button type="submit" class="cp-btn cp-btn-primary cp-btn-block" id="cpPayButton">Pay and Connect</button>
                    </form>
                </div>
            </article>

            <article class="cp-card cp-card-compact">
                <h3 class="cp-section-subtitle">Already paid?</h3>
                <p class="cp-card-subtitle">Use your M-Pesa code or a voucher on the reconnect screen.</p>
                <a href="{{ route('wifi.reconnect.form', $reconnectParams) }}" class="cp-btn cp-btn-soft cp-btn-block">Open Reconnect Screen</a>
            </article>
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
        const packageCards = document.querySelectorAll('.js-package-card');
        const packageInput = document.getElementById('cpPackageId');
        const payButton = document.getElementById('cpPayButton');
        const payForm = document.getElementById('cpPaymentForm');
        const step1 = document.getElementById('cpStep1');
        const step2 = document.getElementById('cpStep2');
        const backBtn = document.getElementById('cpBackBtn');
        const summaryName = document.getElementById('cpSummaryName');
        const summaryPrice = document.getElementById('cpSummaryPrice');
        const summaryMeta = document.getElementById('cpSummaryMeta');
        const phoneInput = document.getElementById('cpPhone');

        function goToStep2(card) {
            if (!step1 || !step2 || !card) return;

            const pkgId = card.dataset.packageId || '';
            const pkgName = card.dataset.packageName || 'Package';
            const pkgPrice = card.dataset.packagePrice || '';
            const pkgDuration = card.dataset.packageDuration || '';
            const pkgSpeed = card.dataset.packageSpeed || '';

            if (packageInput) packageInput.value = pkgId;
            if (summaryName) summaryName.textContent = pkgName;
            if (summaryPrice) summaryPrice.textContent = pkgPrice;
            if (summaryMeta) summaryMeta.textContent = [pkgDuration, pkgSpeed].filter(Boolean).join(' · ');
            if (payButton) payButton.textContent = `Pay ${pkgPrice} and Connect`;

            packageCards.forEach((node) => {
                const isSelected = node === card;
                node.classList.toggle('is-selected', isSelected);
                node.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
            });

            step1?.setAttribute('hidden', '');
            step2?.removeAttribute('hidden');

            if (phoneInput && !phoneInput.value) {
                phoneInput.focus();
            }
        }

        function goToStep1() {
            step2?.setAttribute('hidden', '');
            step1?.removeAttribute('hidden');

            packageCards.forEach((node) => {
                node.classList.remove('is-selected');
                node.setAttribute('aria-pressed', 'false');
            });
        }

        packageCards.forEach((card) => {
            card.addEventListener('click', () => goToStep2(card));
        });

        backBtn?.addEventListener('click', goToStep1);

        @if($selectedPackageId > 0)
        (function() {
            const preselectedCard = document.querySelector('.js-package-card[data-package-id="{{ $selectedPackageId }}"]');
            if (preselectedCard) {
                goToStep2(preselectedCard);
            }
        })();
        @endif

        payForm?.addEventListener('submit', () => {
            if (!payButton) return;
            payButton.disabled = true;
            payButton.textContent = 'Sending M-Pesa prompt...';
        });

        @if(isset($activeSession) && $activeSession)
        const expiresAt = new Date('{{ $activeSession->expires_at?->toIso8601String() ?? $activeSession->expires_at }}').getTime();
        function updateActiveCountdown() {
            const node = document.getElementById('timeLeft');
            if (!node || !expiresAt) return;

            const diff = expiresAt - Date.now();
            if (diff <= 0) {
                node.textContent = 'Expired';
                return;
            }

            const totalSeconds = Math.floor(diff / 1000);
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;
            node.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }

        updateActiveCountdown();
        setInterval(updateActiveCountdown, 1000);
        @endif
    </script>
</body>
</html>
