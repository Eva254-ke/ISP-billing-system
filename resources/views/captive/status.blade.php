<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0e7490">
    @if(in_array($statusView, ['pending', 'paid', 'verifying']))
        <meta http-equiv="refresh" content="10">
    @endif
    <title>Connection Status - CloudBridge WiFi</title>
    @php
        $captiveCssPath = public_path('css/captive-portal.css');
        $captiveCssVersion = file_exists($captiveCssPath) ? filemtime($captiveCssPath) : time();
        $tenantId = (int) ($payment->tenant_id ?? request()->query('tenant_id', 0));
        $supportPhoneRaw = trim((string) ($payment->tenant?->captive_portal_support_phone ?? ''));
        $supportDigits = preg_replace('/\D+/', '', $supportPhoneRaw);
        if (is_string($supportDigits) && str_starts_with($supportDigits, '0')) {
            $supportDigits = '254' . substr($supportDigits, 1);
        }
        $supportTelHref = (is_string($supportDigits) && $supportDigits !== '')
            ? 'tel:+' . ltrim($supportDigits, '+')
            : 'tel:+254742939094';
        $statusRoute = route('wifi.status', array_filter([
            'phone' => $phone,
            'tenant_id' => $tenantId > 0 ? $tenantId : null,
            'payment' => $payment->id,
        ], static fn ($value) => $value !== null && $value !== ''));
        $statusCheckRoute = route('wifi.status.check', array_filter([
            'phone' => $phone,
            'tenant_id' => $tenantId > 0 ? $tenantId : null,
            'payment' => $payment->id,
        ], static fn ($value) => $value !== null && $value !== ''));
        $shouldAutoPoll = in_array($statusView, ['pending', 'paid', 'verifying'], true);
        $packagesParams = array_filter([
            'phone' => $phone,
            'tenant_id' => $tenantId > 0 ? $tenantId : null,
        ], static fn ($value) => $value !== null && $value !== '');
        $paymentMeta = is_array($payment->metadata) ? $payment->metadata : [];
        $gatewayStatus = trim((string) ($paymentMeta['daraja_last_status'] ?? ''));
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
            <div class="cp-support"><a class="cp-link-support" href="{{ $supportTelHref }}">Call support</a></div>
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
                        <span class="cp-status-pill warning">{{ $gatewayStatus === 'pending_verification' ? 'Verifying with M-Pesa' : 'Waiting for payment' }}</span>
                        <h2 class="cp-section-title">{{ $gatewayStatus === 'pending_verification' ? 'We are confirming your payment request' : 'Confirm the M-Pesa prompt' }}</h2>
                        <p class="cp-card-subtitle">
                            @if($gatewayStatus === 'pending_verification')
                                If the prompt appears or money is deducted, do not pay again. Keep this page open while we check.
                            @else
                                Use phone <strong>{{ $phone }}</strong> and enter your PIN to continue.
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
                        @if($gatewayStatus === 'pending_verification')
                            We will keep checking automatically. If you already received the M-Pesa SMS, you can also use the reconnect option below.
                        @else
                            After payment confirmation, your internet access will activate automatically.
                        @endif
                    </p>
                </div>

                <div class="cp-actions">
                    <a href="{{ $statusRoute }}" class="cp-btn cp-btn-primary">Refresh Status</a>
                    <a href="{{ route('wifi.reconnect.form', $packagesParams) }}" class="cp-btn cp-btn-soft">Use M-Pesa Code</a>
                </div>

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

                <a href="{{ $statusRoute }}" class="cp-btn cp-btn-primary cp-btn-block">Check Status Now</a>

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
                    <a href="{{ route('wifi.reconnect.form', $packagesParams) }}" class="cp-btn cp-btn-soft">Use M-Pesa Code</a>
                </div>

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

                <a href="https://www.google.com" target="_blank" rel="noopener" class="cp-btn cp-btn-primary cp-btn-block">Continue Browsing</a>

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
                    <a href="{{ route('wifi.reconnect.form', $packagesParams) }}" class="cp-btn cp-btn-soft">Reconnect with Code</a>
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
                        <span>{{ $payment->phone ?: $phone }}</span>
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

        <footer class="cp-footer">
            <p><a class="cp-link-support" href="{{ $supportTelHref }}">Call support</a></p>
            <p>Engineered by Engineer Omwenga Evans</p>
        </footer>
    </main>

    <script>
        @if($shouldAutoPoll)
        const currentStatus = @json($statusView);
        let statusPollInFlight = false;

        const pollStatus = async () => {
            if (statusPollInFlight) {
                return;
            }

            statusPollInFlight = true;
            try {
                const response = await fetch('{{ $statusCheckRoute }}', {
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

                if (payload.session_active === true) {
                    if (currentStatus !== 'activated') {
                        window.location.reload();
                    }
                    return;
                }

                const nextStatus = (payload.status || '').toLowerCase();
                if (nextStatus !== '' && nextStatus !== currentStatus) {
                    window.location.reload();
                }
            } catch (error) {
                // Keep silent, meta refresh acts as fallback.
            } finally {
                statusPollInFlight = false;
            }
        };

        setInterval(() => {
            void pollStatus();
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
