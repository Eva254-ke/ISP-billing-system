<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#155eef">
    @if(in_array($statusView, ['pending', 'paid']))
    <meta http-equiv="refresh" content="10">
    @endif
    <title>Connection Status - CloudBridge WiFi</title>
    @php
        $captiveCssPath = public_path('css/captive-portal.css');
        $captiveCssVersion = file_exists($captiveCssPath) ? filemtime($captiveCssPath) : time();
    @endphp
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/captive-portal.css?v={{ $captiveCssVersion }}">
</head>
<body>
    <main class="cp-page cp-status-shell">
        <header class="cp-topbar">
            <div class="cp-brand">
                <div class="cp-brand-mark">CB</div>
                <div class="cp-brand-text">
                    <h1>CloudBridge WiFi</h1>
                    <p>Connection workflow</p>
                </div>
            </div>
            <div class="cp-support">Support: <a href="tel:+254700000000">+254 700 000 000</a></div>
        </header>

        <article class="cp-card">
            @if($statusView === 'pending')
                <div class="cp-status-head">
                    <div>
                        <span class="cp-status-pill warning">Awaiting Payment</span>
                        <h2 class="cp-status-title">Complete M-Pesa Prompt</h2>
                        <p class="cp-status-copy">Use phone <strong>{{ $phone }}</strong> and enter your PIN to confirm payment.</p>
                    </div>
                    <div class="cp-spinner" aria-hidden="true"></div>
                </div>

                <ol class="cp-step-list">
                    <li class="cp-step is-complete">1. Package selected</li>
                    <li class="cp-step is-current">2. Waiting for payment confirmation</li>
                    <li class="cp-step">3. Activating your internet access</li>
                    <li class="cp-step">4. Connected</li>
                </ol>

                <div class="cp-panel">
                    <h3>What to do now</h3>
                    <p>1) Open the M-Pesa prompt on your phone. 2) Enter PIN. 3) Wait here for automatic update.</p>
                </div>

                <div class="cp-actions">
                    <a href="{{ url()->current() }}" class="cp-btn cp-btn-primary">Refresh Status</a>
                    <a href="{{ route('wifi.packages', ['phone' => $phone]) }}" class="cp-btn cp-btn-soft">Back To Packages</a>
                </div>

            @elseif($statusView === 'paid')
                <div class="cp-status-head">
                    <div>
                        <span class="cp-status-pill">Payment Confirmed</span>
                        <h2 class="cp-status-title">Activating Session</h2>
                        <p class="cp-status-copy">Payment is received. We are now enabling internet access.</p>
                    </div>
                    <div class="cp-spinner" aria-hidden="true"></div>
                </div>

                <ol class="cp-step-list">
                    <li class="cp-step is-complete">1. Package selected</li>
                    <li class="cp-step is-complete">2. Payment confirmed</li>
                    <li class="cp-step is-current">3. Activating your internet access</li>
                    <li class="cp-step">4. Connected</li>
                </ol>

                <div class="cp-panel">
                    <h3>Almost done</h3>
                    <p>Activation usually finishes within 10 to 30 seconds.</p>
                </div>

                @if(!empty($radiusFallback))
                    <div class="cp-panel" style="margin-top:10px; border-color: color-mix(in srgb, var(--cp-warning) 45%, var(--cp-border));">
                        <h3>Fallback credentials</h3>
                        <p>Only use if auto-connect delays: username <strong>{{ $radiusFallback['username'] }}</strong>, password same as username.</p>
                    </div>
                @endif

                <div class="cp-actions">
                    <a href="{{ url()->current() }}" class="cp-btn cp-btn-primary">Refresh Status</a>
                    <a href="{{ route('wifi.packages', ['phone' => $phone]) }}" class="cp-btn cp-btn-soft">Back To Packages</a>
                </div>

            @elseif($statusView === 'activated')
                <div class="cp-status-head">
                    <div>
                        <span class="cp-status-pill success">Connected</span>
                        <h2 class="cp-status-title">Internet Is Active</h2>
                        <p class="cp-status-copy">Your session is live. Enjoy browsing.</p>
                    </div>
                </div>

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

                <div class="cp-actions">
                    <a href="https://www.google.com" target="_blank" rel="noopener" class="cp-btn cp-btn-primary">Start Browsing</a>
                    <a href="{{ route('wifi.packages', ['phone' => $phone]) }}" class="cp-btn cp-btn-soft">Buy Another Package</a>
                </div>

            @elseif($statusView === 'failed')
                <div class="cp-status-head">
                    <div>
                        <span class="cp-status-pill error">Payment Not Completed</span>
                        <h2 class="cp-status-title">Payment Failed</h2>
                        <p class="cp-status-copy">{{ $payment->callback_payload['reason'] ?? $payment->callback_data['ResultDesc'] ?? 'The transaction did not complete.' }}</p>
                    </div>
                </div>

                <div class="cp-panel" style="margin-top:10px;">
                    <h3>Common reasons</h3>
                    <p>Insufficient balance, incorrect PIN, or timeout before prompt confirmation.</p>
                </div>

                <div class="cp-form-card" style="margin-top:12px;">
                    <h4>Already paid anyway? Reconnect with M-Pesa code</h4>
                    <form method="POST" action="{{ route('wifi.reconnect') }}">
                        @csrf
                        <div class="cp-field">
                            <label for="failedPhone">Phone Number</label>
                            <input id="failedPhone" type="tel" name="phone" value="{{ $phone }}" required pattern="0[17]\d{8}" autocomplete="tel" inputmode="tel">
                        </div>
                        <div class="cp-field">
                            <label for="failedMpesaCode">M-Pesa Transaction Code</label>
                            <input id="failedMpesaCode" type="text" name="mpesa_code" placeholder="QGH45XYZ" required maxlength="32" autocomplete="off">
                        </div>
                        <button type="submit" class="cp-btn cp-btn-soft cp-btn-block">Verify And Connect</button>
                    </form>
                </div>

                <div class="cp-actions" style="margin-top:12px;">
                    <a href="{{ route('wifi.packages', ['phone' => $phone]) }}" class="cp-btn cp-btn-primary">Try New Payment</a>
                    <a href="{{ route('wifi.packages', ['phone' => $phone, 'mode' => 'reconnect']) }}#reconnect" class="cp-btn cp-btn-outline">Open Full Restore Options</a>
                </div>

            @else
                <div class="cp-status-head">
                    <div>
                        <span class="cp-status-pill">Status Unknown</span>
                        <h2 class="cp-status-title">Checking Payment State</h2>
                        <p class="cp-status-copy">Current status: {{ $payment->status }}</p>
                    </div>
                </div>

                <div class="cp-actions" style="margin-top:12px;">
                    <a href="{{ route('wifi.packages', ['phone' => $phone]) }}" class="cp-btn cp-btn-primary">Back To Packages</a>
                </div>
            @endif
        </article>

        <div class="cp-footer">
            Need help? <a href="tel:+254700000000">Call support now</a>
        </div>
    </main>

    <script>
        @if(in_array($statusView, ['pending', 'paid']))
        setInterval(async () => {
            try {
                const response = await fetch('{{ route('wifi.status.check', ['phone' => $phone]) }}', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) return;

                const payload = await response.json();
                if (!payload) return;

                if (payload.session_active === true) {
                    window.location.reload();
                    return;
                }

                if (['completed', 'confirmed', 'failed', 'activated'].includes((payload.status || '').toLowerCase())) {
                    window.location.reload();
                }
            } catch (error) {
                // Keep silent; meta refresh is fallback.
            }
        }, 5000);
        @endif

        @if($statusView === 'activated' && !empty($activeSession?->expires_at))
        const expiresAt = new Date('{{ $activeSession->expires_at->toIso8601String() }}').getTime();

        function updateCountdown() {
            const now = Date.now();
            const distance = expiresAt - now;
            const expiresNode = document.getElementById('expiresAt');
            const countdownNode = document.getElementById('countdown');

            if (distance <= 0) {
                if (countdownNode) countdownNode.textContent = 'Session Expired';
                if (expiresNode) expiresNode.textContent = 'Expired';
                return;
            }

            const totalSeconds = Math.floor(distance / 1000);
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;

            if (countdownNode) {
                countdownNode.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
            if (expiresNode) {
                expiresNode.textContent = new Date(expiresAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
        @endif

        const failedMpesaCode = document.getElementById('failedMpesaCode');
        failedMpesaCode?.addEventListener('input', function () {
            this.value = this.value.toUpperCase().trimStart();
        });
    </script>
</body>
</html>
