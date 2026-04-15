<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0e7490">
    <title>{{ $tenant?->name ?: 'CloudBridge WiFi' }} - WiFi Packages</title>
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
        $selectedPackageId = (int) old('package_id', 0);
        $selectedPackage = $packages->firstWhere('id', $selectedPackageId);
        $reconnectParams = array_filter([
            'tenant_id' => $tenantId > 0 ? $tenantId : request()->query('tenant_id'),
            'phone' => old('phone', $phone ?? ''),
        ], static fn ($value) => $value !== null && $value !== '');
    @endphp
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/captive-portal.css?v={{ $captiveCssVersion }}">
</head>
<body>
    <main class="cp-page">
        <header class="cp-topbar">
            <div class="cp-brand">
                <div class="cp-brand-mark">CB</div>
                <div class="cp-brand-text">
                    <h1>{{ $tenant?->name ?: 'CloudBridge WiFi' }}</h1>
                    <p>Secure internet in minutes</p>
                </div>
            </div>
            <div class="cp-support"><a class="cp-link-support" href="{{ $supportTelHref }}">Call support</a></div>
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
                <p class="cp-card-subtitle">Your session is active now. Check remaining time below.</p>

                <div class="cp-facts">
                    <div class="cp-fact">
                        <span>Phone</span>
                        <span>{{ $statusPhone ?: 'N/A' }}</span>
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
                <div class="cp-flow">
                    <div class="cp-flow-step is-current">1. Choose package</div>
                    <div class="cp-flow-step">2. Pay with M-Pesa</div>
                    <div class="cp-flow-step">3. Get connected</div>
                </div>

                <h2 class="cp-section-title">Choose package and pay</h2>
                <p class="cp-card-subtitle">Simple flow: select package, enter phone number, pay, then internet connects automatically.</p>

                @if($packages->isEmpty())
                    <div class="cp-panel">
                        <h3>No packages available</h3>
                        <p>Please contact support to activate packages for this hotspot.</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('wifi.pay') }}" id="cpPaymentForm" class="cp-payment-form">
                        @csrf

                        <div class="cp-field">
                            <label for="cpPhone">M-Pesa Number</label>
                            <input
                                id="cpPhone"
                                type="tel"
                                name="phone"
                                placeholder="0712345678"
                                value="{{ old('phone', $phone ?? '') }}"
                                required
                                pattern="0[17]\d{8}"
                                autocomplete="tel"
                                inputmode="tel">
                        </div>

                        <input type="hidden" name="package_id" id="cpPackageId" value="{{ $selectedPackageId > 0 ? $selectedPackageId : '' }}">

                        <div class="cp-grid cp-package-grid">
                            @foreach($packages as $pkg)
                                @php
                                    $isSelected = $selectedPackageId === (int) $pkg->id;
                                @endphp
                                <button
                                    type="button"
                                    class="cp-package-card js-package-card{{ $isSelected ? ' is-selected' : '' }}"
                                    data-package-id="{{ $pkg->id }}"
                                    data-package-name="{{ $pkg->name }}"
                                    data-package-price="KES {{ number_format((float) $pkg->price, 0) }}"
                                    data-package-duration="{{ $pkg->duration_formatted }}"
                                    data-package-speed="{{ $pkg->bandwidth_formatted }}"
                                    aria-pressed="{{ $isSelected ? 'true' : 'false' }}">
                                    <div class="cp-package-name">{{ $pkg->name }}</div>
                                    <div class="cp-package-price">KES {{ number_format((float) $pkg->price, 0) }}</div>
                                    <div class="cp-package-meta">{{ $pkg->duration_formatted }}</div>
                                    <div class="cp-package-meta">{{ $pkg->bandwidth_formatted }}</div>
                                </button>
                            @endforeach
                        </div>

                        <div class="cp-selected-summary" id="cpSelectedSummary">
                            @if($selectedPackage)
                                Selected: {{ $selectedPackage->name }} - KES {{ number_format((float) $selectedPackage->price, 0) }}
                            @else
                                Select a package to continue.
                            @endif
                        </div>

                        <button
                            type="submit"
                            class="cp-btn cp-btn-primary cp-btn-block"
                            id="cpPayButton"
                            {{ $selectedPackage ? '' : 'disabled' }}>
                            Pay and Connect
                        </button>
                    </form>
                @endif
            </article>

            <article class="cp-card cp-card-compact">
                <h3 class="cp-section-subtitle">Already paid?</h3>
                <p class="cp-card-subtitle">Reconnect using your M-Pesa code or voucher on a separate screen.</p>
                <a href="{{ route('wifi.reconnect.form', $reconnectParams) }}" class="cp-btn cp-btn-soft cp-btn-block">Open Reconnect Screen</a>
            </article>
        @endif

        <footer class="cp-footer">
            <p><a class="cp-link-support" href="{{ $supportTelHref }}">Call support</a></p>
            <p>Engineered by Engineer Omwenga Evans</p>
        </footer>
    </main>

    <script>
        const packageCards = document.querySelectorAll('.js-package-card');
        const packageInput = document.getElementById('cpPackageId');
        const payButton = document.getElementById('cpPayButton');
        const summaryNode = document.getElementById('cpSelectedSummary');
        const payForm = document.getElementById('cpPaymentForm');

        function setSelectedPackage(card) {
            if (!card || !packageInput || !summaryNode || !payButton) {
                return;
            }

            packageCards.forEach((node) => {
                node.classList.remove('is-selected');
                node.setAttribute('aria-pressed', 'false');
            });

            card.classList.add('is-selected');
            card.setAttribute('aria-pressed', 'true');

            const packageId = card.dataset.packageId || '';
            const packageName = card.dataset.packageName || 'Package';
            const packagePrice = card.dataset.packagePrice || '';
            const packageDuration = card.dataset.packageDuration || '';

            packageInput.value = packageId;
            summaryNode.textContent = `Selected: ${packageName} - ${packagePrice} (${packageDuration})`;
            payButton.disabled = packageId === '';
        }

        packageCards.forEach((card) => {
            card.addEventListener('click', () => setSelectedPackage(card));
        });

        if (packageInput && packageInput.value !== '') {
            const preselectedCard = document.querySelector(`.js-package-card[data-package-id="${packageInput.value}"]`);
            if (preselectedCard) {
                setSelectedPackage(preselectedCard);
            }
        }

        payForm?.addEventListener('submit', () => {
            if (!payButton) {
                return;
            }

            payButton.disabled = true;
            payButton.textContent = 'Sending M-Pesa Prompt...';
        });

        @if(isset($activeSession) && $activeSession)
        const expiresAt = new Date('{{ $activeSession->expires_at?->toIso8601String() ?? $activeSession->expires_at }}').getTime();
        function updateActiveCountdown() {
            const node = document.getElementById('timeLeft');
            if (!node || !expiresAt) {
                return;
            }

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
