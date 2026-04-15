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
    <style>
        /* Two-step flow transitions */
        .cp-step-container {
            position: relative;
            overflow: hidden;
        }
        .cp-step {
            transition: transform 0.35s cubic-bezier(.4,0,.2,1), opacity 0.3s ease;
        }
        .cp-step[hidden] {
            display: block !important;
            position: absolute;
            top: 0; left: 0; right: 0;
            pointer-events: none;
            opacity: 0;
            transform: translateX(60px);
        }
        .cp-step.is-active {
            position: relative;
            pointer-events: auto;
            opacity: 1;
            transform: translateX(0);
        }
        .cp-step.is-leaving {
            position: absolute;
            top: 0; left: 0; right: 0;
            pointer-events: none;
            opacity: 0;
            transform: translateX(-60px);
        }

        /* Back button */
        .cp-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: none;
            border: none;
            color: var(--cp-primary);
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            padding: 0;
            margin-bottom: 12px;
        }
        .cp-back-btn:hover {
            text-decoration: underline;
        }
        .cp-back-btn svg {
            width: 18px;
            height: 18px;
        }

        /* Selected package summary on step 2 */
        .cp-pay-summary {
            border: 1px solid var(--cp-border);
            border-radius: var(--cp-radius-md);
            background: var(--cp-surface-soft);
            padding: 14px;
            margin-bottom: 14px;
            display: grid;
            gap: 4px;
        }
        .cp-pay-summary-name {
            font-size: 16px;
            font-weight: 800;
            line-height: 1.25;
        }
        .cp-pay-summary-price {
            font-size: 22px;
            font-weight: 800;
            color: var(--cp-primary);
            line-height: 1.2;
        }
        .cp-pay-summary-meta {
            color: var(--cp-muted);
            font-size: 13px;
            line-height: 1.3;
        }

        /* Package card continue indicator */
        .cp-package-card .cp-pkg-tap-hint {
            display: none;
            font-size: 11px;
            font-weight: 700;
            color: var(--cp-primary);
            margin-top: 2px;
        }
        .cp-package-card.is-selected .cp-pkg-tap-hint {
            display: block;
        }
    </style>
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
            <article class="cp-card cp-step-container">
                {{-- ============== STEP 1: Choose Package ============== --}}
                <div class="cp-step is-active" id="cpStep1">
                    <div class="cp-flow">
                        <div class="cp-flow-step is-current">1. Choose package</div>
                        <div class="cp-flow-step">2. Enter phone & pay</div>
                        <div class="cp-flow-step">3. Get connected</div>
                    </div>

                    <h2 class="cp-section-title">Choose a package</h2>
                    <p class="cp-card-subtitle">Select a WiFi package below, then you'll enter your M-Pesa number to pay.</p>

                    @if($packages->isEmpty())
                        <div class="cp-panel">
                            <h3>No packages available</h3>
                            <p>Please contact support to activate packages for this hotspot.</p>
                        </div>
                    @else
                        <div class="cp-grid cp-package-grid" style="margin-top:14px">
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
                                    <div class="cp-pkg-tap-hint">Tap again to continue →</div>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- ============== STEP 2: Enter Phone & Pay ============== --}}
                <div class="cp-step" id="cpStep2" hidden>
                    <div class="cp-flow">
                        <div class="cp-flow-step is-complete">1. Choose package ✓</div>
                        <div class="cp-flow-step is-current">2. Enter phone & pay</div>
                        <div class="cp-flow-step">3. Get connected</div>
                    </div>

                    <button type="button" class="cp-back-btn" id="cpBackBtn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        Back to packages
                    </button>

                    <h2 class="cp-section-title">Enter your number to pay</h2>
                    <p class="cp-card-subtitle">An M-Pesa STK Push prompt will be sent to this number.</p>

                    <div class="cp-pay-summary" id="cpPaySummary" style="margin-top:14px">
                        <div class="cp-pay-summary-name" id="cpSummaryName">--</div>
                        <div class="cp-pay-summary-price" id="cpSummaryPrice">--</div>
                        <div class="cp-pay-summary-meta" id="cpSummaryMeta">--</div>
                    </div>

                    <form method="POST" action="{{ route('wifi.pay') }}" id="cpPaymentForm" class="cp-payment-form" style="margin-top:0">
                        @csrf
                        <input type="hidden" name="package_id" id="cpPackageId" value="">

                        <div class="cp-field">
                            <label for="cpPhone">Safaricom M-Pesa Number</label>
                            <input
                                id="cpPhone"
                                type="tel"
                                name="phone"
                                placeholder="0712345678 or 0112345678"
                                value="{{ old('phone', $phone ?? '') }}"
                                required
                                pattern="(?:0[17]\d{8}|(?:\+?254)[17]\d{8})"
                                autocomplete="tel"
                                inputmode="tel">
                        </div>

                        <button
                            type="submit"
                            class="cp-btn cp-btn-primary cp-btn-block"
                            id="cpPayButton">
                            Pay and Connect
                        </button>
                    </form>
                </div>
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
        const payForm = document.getElementById('cpPaymentForm');
        const step1 = document.getElementById('cpStep1');
        const step2 = document.getElementById('cpStep2');
        const backBtn = document.getElementById('cpBackBtn');
        const summaryName = document.getElementById('cpSummaryName');
        const summaryPrice = document.getElementById('cpSummaryPrice');
        const summaryMeta = document.getElementById('cpSummaryMeta');
        const phoneInput = document.getElementById('cpPhone');

        let currentSelectedCard = null;

        function goToStep2(card) {
            if (!step1 || !step2 || !card) return;

            const pkgId = card.dataset.packageId || '';
            const pkgName = card.dataset.packageName || 'Package';
            const pkgPrice = card.dataset.packagePrice || '';
            const pkgDuration = card.dataset.packageDuration || '';
            const pkgSpeed = card.dataset.packageSpeed || '';

            // Set hidden input
            if (packageInput) packageInput.value = pkgId;

            // Fill summary
            if (summaryName) summaryName.textContent = pkgName;
            if (summaryPrice) summaryPrice.textContent = pkgPrice;
            if (summaryMeta) summaryMeta.textContent = [pkgDuration, pkgSpeed].filter(Boolean).join(' · ');

            // Update pay button text
            if (payButton) payButton.textContent = `Pay ${pkgPrice} and Connect`;

            // Animate step transition
            step1.classList.remove('is-active');
            step1.classList.add('is-leaving');
            step2.removeAttribute('hidden');

            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    step2.classList.add('is-active');
                    step1.setAttribute('hidden', '');
                    step1.classList.remove('is-leaving');
                });
            });

            // Focus phone input
            setTimeout(() => {
                if (phoneInput && !phoneInput.value) phoneInput.focus();
            }, 400);
        }

        function goToStep1() {
            if (!step1 || !step2) return;

            step2.classList.remove('is-active');
            step2.classList.add('is-leaving');
            step1.removeAttribute('hidden');

            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    step1.classList.add('is-active');
                    step2.setAttribute('hidden', '');
                    step2.classList.remove('is-leaving');
                });
            });

            // Clear selection
            currentSelectedCard = null;
            packageCards.forEach((node) => {
                node.classList.remove('is-selected');
                node.setAttribute('aria-pressed', 'false');
            });
        }

        packageCards.forEach((card) => {
            card.addEventListener('click', () => {
                if (currentSelectedCard === card) {
                    // Second tap on same card → go to step 2
                    goToStep2(card);
                    return;
                }

                // First tap → select the card
                packageCards.forEach((node) => {
                    node.classList.remove('is-selected');
                    node.setAttribute('aria-pressed', 'false');
                });
                card.classList.add('is-selected');
                card.setAttribute('aria-pressed', 'true');
                currentSelectedCard = card;

                // On mobile, auto-advance after a brief delay for visual feedback
                // On desktop, user can tap again or we auto-advance
                setTimeout(() => {
                    if (currentSelectedCard === card) {
                        goToStep2(card);
                    }
                }, 600);
            });
        });

        if (backBtn) {
            backBtn.addEventListener('click', goToStep1);
        }

        // If there were validation errors (old input exists), go straight to step 2
        @if($selectedPackageId > 0 && $selectedPackage)
        (function() {
            const preselectedCard = document.querySelector('.js-package-card[data-package-id="{{ $selectedPackageId }}"]');
            if (preselectedCard) {
                currentSelectedCard = preselectedCard;
                preselectedCard.classList.add('is-selected');
                preselectedCard.setAttribute('aria-pressed', 'true');
                // Jump immediately (no animation) on validation error redirect
                if (step1 && step2) {
                    step1.classList.remove('is-active');
                    step1.setAttribute('hidden', '');
                    step2.removeAttribute('hidden');
                    step2.classList.add('is-active');
                    if (packageInput) packageInput.value = '{{ $selectedPackageId }}';
                    if (summaryName) summaryName.textContent = preselectedCard.dataset.packageName || '';
                    if (summaryPrice) summaryPrice.textContent = preselectedCard.dataset.packagePrice || '';
                    if (summaryMeta) summaryMeta.textContent = [preselectedCard.dataset.packageDuration, preselectedCard.dataset.packageSpeed].filter(Boolean).join(' · ');
                    if (payButton) payButton.textContent = `Pay ${preselectedCard.dataset.packagePrice || ''} and Connect`;
                }
            }
        })();
        @endif

        payForm?.addEventListener('submit', () => {
            if (!payButton) return;
            payButton.disabled = true;
            payButton.textContent = 'Sending M-Pesa Prompt...';
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
