<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#155eef">
    <title>CloudBridge WiFi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/captive-portal.css') }}">
</head>
<body>
    <main class="cp-page">
        <header class="cp-topbar">
            <div class="cp-brand">
                <div class="cp-brand-mark">CB</div>
                <div class="cp-brand-text">
                    <h1>{{ $tenant?->name ?: 'CloudBridge WiFi' }}</h1>
                    <p>Fast, secure internet access</p>
                </div>
            </div>
            <div class="cp-support">Support: <a href="tel:+254700000000">+254 700 000 000</a></div>
        </header>

        <section class="cp-stack">
            <article class="cp-card">
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
                    <h2>Session Active</h2>
                    <p class="cp-card-subtitle">You are currently connected. You can restore status or buy additional access.</p>

                    <div class="cp-facts" style="margin-top:12px;">
                        <div class="cp-fact">
                            <span>Phone</span>
                            <span>{{ $activeSession->phone ?? ($phone ?: 'N/A') }}</span>
                        </div>
                        <div class="cp-fact">
                            <span>Expires</span>
                            <span id="activeExpires">{{ $activeSession->expires_at?->format('H:i') ?? 'N/A' }}</span>
                        </div>
                        <div class="cp-fact">
                            <span>Time left</span>
                            <span id="timeLeft">--:--</span>
                        </div>
                    </div>

                    <div class="cp-actions">
                        <a href="{{ route('wifi.status', ['phone' => $phone]) }}" class="cp-btn cp-btn-primary">View Connection Status</a>
                        <a href="{{ route('wifi.packages', ['phone' => $phone]) }}" class="cp-btn cp-btn-soft">Buy More Time</a>
                    </div>
                @else
                    <h2>Choose Your Package</h2>
                    <p class="cp-card-subtitle">Select a plan, complete M-Pesa payment, and get online in seconds.</p>

                    <div class="cp-trust-row">
                        <div class="cp-trust-item"><strong>Secure Checkout</strong>M-Pesa PIN is only entered on your phone.</div>
                        <div class="cp-trust-item"><strong>Instant Activation</strong>Access is enabled automatically after payment.</div>
                        <div class="cp-trust-item"><strong>Reliable Support</strong>Help is available if anything delays.</div>
                    </div>

                    <div class="cp-grid cp-package-grid" style="margin-top:14px;">
                        @forelse($packages as $pkg)
                            <article class="cp-package">
                                <div class="cp-package-title">
                                    <h3>{{ $pkg->name }}</h3>
                                    <div class="cp-price">KES {{ number_format((float) $pkg->price, 0) }}<small>/plan</small></div>
                                </div>
                                <div class="cp-meta">
                                    <div class="cp-meta-line"><strong>Duration:</strong> {{ $pkg->duration_formatted }}</div>
                                    <div class="cp-meta-line"><strong>Speed:</strong> {{ $pkg->bandwidth_formatted }}</div>
                                </div>
                                <button
                                    type="button"
                                    class="cp-btn cp-btn-primary cp-btn-block js-open-payment"
                                    data-package-id="{{ $pkg->id }}"
                                    data-package-name="{{ $pkg->name }}"
                                    data-package-price="{{ number_format((float) $pkg->price, 0) }}"
                                    data-package-duration="{{ $pkg->duration_formatted }}"
                                    data-package-speed="{{ $pkg->bandwidth_formatted }}">
                                    Pay With M-Pesa
                                </button>
                            </article>
                        @empty
                            <div class="cp-flash error">No packages are available right now for this location.</div>
                        @endforelse
                    </div>
                @endif
            </article>

            <article id="reconnect" class="cp-card" data-reconnect-mode="{{ request('mode') === 'reconnect' ? '1' : '0' }}">
                <h2>Already Paid? Restore Access</h2>
                <p class="cp-card-subtitle">No separate page needed. Use your M-Pesa code or voucher right here.</p>

                <div class="cp-form-grid">
                    <section class="cp-form-card">
                        <h4>Reconnect With M-Pesa Code</h4>
                        <form method="POST" action="{{ route('wifi.reconnect') }}">
                            @csrf
                            <div class="cp-field">
                                <label for="reconnectPhone">Phone Number</label>
                                <input id="reconnectPhone" type="tel" name="phone" placeholder="0712345678" value="{{ $phone ?? '' }}" required pattern="0[17]\d{8}" autocomplete="tel" inputmode="tel">
                            </div>
                            <div class="cp-field">
                                <label for="mpesaCode">M-Pesa Transaction Code</label>
                                <input id="mpesaCode" type="text" name="mpesa_code" placeholder="QGH45XYZ" required maxlength="32" autocomplete="off">
                            </div>
                            <button type="submit" class="cp-btn cp-btn-soft cp-btn-block">Verify And Connect</button>
                        </form>
                    </section>

                    <section class="cp-form-card">
                        <h4>Redeem Voucher</h4>
                        <form method="POST" action="{{ route('wifi.reconnect') }}">
                            @csrf
                            <div class="cp-field">
                                <label for="voucherPhone">Phone Number</label>
                                <input id="voucherPhone" type="tel" name="phone" placeholder="0712345678" value="{{ $phone ?? '' }}" required pattern="0[17]\d{8}" autocomplete="tel" inputmode="tel">
                            </div>
                            <div class="cp-field">
                                <label for="voucherCode">Voucher Code</label>
                                <input id="voucherCode" type="text" name="voucher_code" placeholder="CB-WIFI-1234" required maxlength="64" autocomplete="off">
                            </div>
                            <button type="submit" class="cp-btn cp-btn-soft cp-btn-block">Redeem Voucher</button>
                        </form>
                    </section>
                </div>
            </article>
        </section>

        <div class="cp-footer">
            Need help? <a href="tel:+254700000000">Call support now</a>
        </div>
    </main>

    <div class="cp-modal-shell" id="paymentModal" aria-hidden="true">
        <div class="cp-modal" role="dialog" aria-modal="true" aria-labelledby="paymentModalTitle">
            <div class="cp-modal-head">
                <h3 id="paymentModalTitle">Confirm Payment Details</h3>
                <button type="button" class="cp-modal-close" id="closePaymentModal" aria-label="Close">&times;</button>
            </div>

            <div class="cp-selected-package">
                <div class="name" id="modalPackageName">Package</div>
                <div class="detail" id="modalPackageDetails">Details</div>
            </div>

            <form method="POST" action="{{ route('wifi.pay') }}" id="paymentForm" style="margin-top:10px;">
                @csrf
                <input type="hidden" name="package_id" id="modalPackageId">
                <div class="cp-field">
                    <label for="modalPhone">M-Pesa Number</label>
                    <input id="modalPhone" type="tel" name="phone" placeholder="0712345678" value="{{ $phone ?? '' }}" required pattern="0[17]\d{8}" autocomplete="tel" inputmode="tel">
                </div>
                <p class="cp-small" style="margin:0 0 10px;">You will receive the M-Pesa prompt on this number.</p>
                <div class="cp-actions" style="margin-top:0;">
                    <button type="submit" class="cp-btn cp-btn-primary cp-btn-block" id="modalPayBtn">Pay And Connect</button>
                    <button type="button" class="cp-btn cp-btn-outline cp-btn-block" id="cancelPaymentModal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const paymentModal = document.getElementById('paymentModal');
        const closePaymentModal = document.getElementById('closePaymentModal');
        const cancelPaymentModal = document.getElementById('cancelPaymentModal');
        const paymentForm = document.getElementById('paymentForm');

        function openPaymentModal(pkg) {
            document.getElementById('modalPackageId').value = pkg.id;
            document.getElementById('modalPackageName').textContent = `${pkg.name} - KES ${pkg.price}`;
            document.getElementById('modalPackageDetails').textContent = `${pkg.duration} | ${pkg.speed}`;
            paymentModal.classList.add('is-open');
            paymentModal.setAttribute('aria-hidden', 'false');
            document.getElementById('modalPhone').focus();
        }

        function closeModal() {
            paymentModal.classList.remove('is-open');
            paymentModal.setAttribute('aria-hidden', 'true');
        }

        document.querySelectorAll('.js-open-payment').forEach((button) => {
            button.addEventListener('click', () => {
                openPaymentModal({
                    id: button.dataset.packageId,
                    name: button.dataset.packageName,
                    price: button.dataset.packagePrice,
                    duration: button.dataset.packageDuration,
                    speed: button.dataset.packageSpeed,
                });
            });
        });

        closePaymentModal?.addEventListener('click', closeModal);
        cancelPaymentModal?.addEventListener('click', closeModal);
        paymentModal?.addEventListener('click', (event) => {
            if (event.target === paymentModal) {
                closeModal();
            }
        });

        paymentForm?.addEventListener('submit', () => {
            const btn = document.getElementById('modalPayBtn');
            btn.disabled = true;
            btn.textContent = 'Sending M-Pesa Prompt...';
        });

        const reconnectPanel = document.getElementById('reconnect');
        if (reconnectPanel?.dataset.reconnectMode === '1') {
            reconnectPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        @if(isset($activeSession) && $activeSession)
        const expiresAt = new Date('{{ $activeSession->expires_at?->toIso8601String() ?? $activeSession->expires_at }}').getTime();
        function updateActiveCountdown() {
            const now = Date.now();
            const diff = expiresAt - now;
            if (diff <= 0) {
                const node = document.getElementById('timeLeft');
                if (node) node.textContent = 'Expired';
                return;
            }
            const totalSeconds = Math.floor(diff / 1000);
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;
            const text = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            const node = document.getElementById('timeLeft');
            if (node) node.textContent = text;
        }
        updateActiveCountdown();
        setInterval(updateActiveCountdown, 1000);
        @endif

        const codeInputs = [document.getElementById('mpesaCode'), document.getElementById('voucherCode')];
        codeInputs.forEach((input) => {
            input?.addEventListener('input', function () {
                this.value = this.value.toUpperCase().trimStart();
            });
        });
    </script>
</body>
</html>
