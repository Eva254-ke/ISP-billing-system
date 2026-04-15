<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0e7490">
    @if(in_array($statusView, ['pending', 'paid']))
        <meta http-equiv="refresh" content="10">
    @endif
    <title>Connection Status - CloudBridge WiFi</title>
    @php
        $captiveCssPath = public_path('css/captive-portal.css');
        $captiveCssVersion = file_exists($captiveCssPath) ? filemtime($captiveCssPath) : time();
        $packagesParams = array_filter([
            'phone' => $phone,
            'tenant_id' => $payment->tenant_id ?? null,
        ], static fn ($value) => $value !== null && $value !== '');
    @endphp
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/captive-portal.css?v={{ $captiveCssVersion }}">
</head>
<body>
    <main class="cp-page cp-page-narrow">
        <header class="cp-topbar">
            <div class="cp-brand">
                <div class="cp-brand-mark">CB</div>
                <div class="cp-brand-text">
                    <h1>CloudBridge WiFi</h1>
                    <p>Connection progress</p>
                </div>
            </div>
            <div class="cp-support">Call support: <a href="tel:+254742939094">0742939094</a></div>
        </header>

        <article class="cp-card">
            @if($statusView === 'pending')
                <div class="cp-status-head">
                    <div>
                        <span class="cp-status-pill warning">Waiting for payment</span>
                        <h2 class="cp-section-title">Confirm the M-Pesa prompt</h2>
                        <p class="cp-card-subtitle">Use phone <strong>{{ $phone }}</strong> and enter your PIN to continue.</p>
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
                    <p>After payment confirmation, your internet access will activate automatically.</p>
                </div>

                <a href="{{ url()->current() }}" class="cp-btn cp-btn-primary cp-btn-block">Check Status Now</a>

            @elseif($statusView === 'paid')
                <div class="cp-status-head">
                    <div>
                        <span class="cp-status-pill">Payment confirmed</span>
                        <h2 class="cp-section-title">Activating internet</h2>
                        <p class="cp-card-subtitle">Your payment is confirmed. Activation usually takes a few seconds.</p>
                    </div>
                    <div class="cp-spinner" aria-hidden="true"></div>
                </div>

                <div class="cp-flow cp-flow-compact">
                    <div class="cp-flow-step is-complete">Package selected</div>
                    <div class="cp-flow-step is-complete">Payment confirmed</div>
                    <div class="cp-flow-step is-current">Internet activation</div>
                </div>

                @if(!empty($radiusFallback))
                    <div class="cp-panel">
                        <h3>Fallback credentials</h3>
                        <p>Use only if activation delays: username <strong>{{ $radiusFallback['username'] }}</strong>, password is the same value.</p>
                    </div>
                @endif

                <a href="{{ url()->current() }}" class="cp-btn cp-btn-primary cp-btn-block">Check Status Now</a>

            @elseif($statusView === 'activated')
                <span class="cp-status-pill success">Connected</span>
                <h2 class="cp-section-title">You are connected to the internet</h2>
                <p class="cp-card-subtitle">Your package is active. Enjoy browsing.</p>

                <div class="cp-facts">
                    <div class="cp-fact">
                        <span>Phone</span>
                        <span>{{ $payment->phone ?: $phone }}</span>
                    </div>
                    <div class="cp-fact">
                        <span>Package</span>
                        <span>{{ $payment->package->name ?? 'N/A' }}</span>
                    </div>
                    <div class="cp-fact">
                        <span>Duration</span>
                        <span>{{ $payment->package->duration_formatted ?? (($payment->package->duration_in_minutes ?? 0) . ' min') }}</span>
                    </div>
                    <div class="cp-fact">
                        <span>Expires</span>
                        <span id="expiresAt">--:--</span>
                    </div>
                </div>

                <div class="cp-countdown" id="countdown">--:--:--</div>

                <a href="https://www.google.com" target="_blank" rel="noopener" class="cp-btn cp-btn-primary cp-btn-block">Continue Browsing</a>

            @elseif($statusView === 'failed')
                <span class="cp-status-pill error">Payment not completed</span>
                <h2 class="cp-section-title">We could not complete payment</h2>
                <p class="cp-card-subtitle">{{ $payment->callback_payload['reason'] ?? $payment->callback_data['ResultDesc'] ?? 'The transaction did not complete.' }}</p>

                <div class="cp-panel">
                    <h3>Next step</h3>
                    <p>Try payment again or reconnect if M-Pesa already charged you.</p>
                </div>

                <div class="cp-actions">
                    <a href="{{ route('wifi.packages', $packagesParams) }}" class="cp-btn cp-btn-primary">Try Payment Again</a>
                    <a href="{{ route('wifi.reconnect.form', $packagesParams) }}" class="cp-btn cp-btn-soft">Reconnect with Code</a>
                </div>

            @else
                <span class="cp-status-pill">Checking status</span>
                <h2 class="cp-section-title">Status update in progress</h2>
                <p class="cp-card-subtitle">Current state: {{ $payment->status }}</p>

                <a href="{{ url()->current() }}" class="cp-btn cp-btn-primary cp-btn-block">Refresh</a>
            @endif
        </article>

        <footer class="cp-footer">
            <p>Call support: <a href="tel:+254742939094">0742939094</a></p>
            <p>Engineered by Engineer Omwenga Evans</p>
        </footer>
    </main>

    <script>
        @if(in_array($statusView, ['pending', 'paid']))
        setInterval(async () => {
            try {
                const response = await fetch('{{ route('wifi.status.check', ['phone' => $phone]) }}', {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                if (!payload) {
                    return;
                }

                if (payload.session_active === true) {
                    window.location.reload();
                    return;
                }

                const nextStatus = (payload.status || '').toLowerCase();
                if (['completed', 'confirmed', 'failed', 'activated'].includes(nextStatus)) {
                    window.location.reload();
                }
            } catch (error) {
                // Keep silent, meta refresh acts as fallback.
            }
        }, 5000);
        @endif

        @if($statusView === 'activated' && !empty($activeSession?->expires_at))
        const expiresAt = new Date('{{ $activeSession->expires_at->toIso8601String() }}').getTime();

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
