<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#7C3AED">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title>CloudBridge WiFi</title>
    <style>
        :root {
            --primary: #7C3AED;
            --primary-dark: #6D28D9;
            --secondary: #06B6D4;
            --bg: #0F172A;
            --surface: #1E293B;
            --surface-light: #334155;
            --success: #10B981;
            --error: #EF4444;
            --text: #FFFFFF;
            --text-muted: #94A3B8;
            --border: #475569;
            --modal-overlay: rgba(15, 23, 42, 0.8);
        }

        @media (prefers-color-scheme: light) {
            :root {
                --bg: #F8FAFC;
                --surface: #FFFFFF;
                --surface-light: #F1F5F9;
                --text: #0F172A;
                --text-muted: #64748B;
                --border: #E2E8F0;
                --modal-overlay: rgba(248, 250, 252, 0.9);
            }
        }

        * { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--bg);
            min-height: 100vh;
            color: var(--text);
            padding: 0.75rem;
            line-height: 1.5;
            overflow-x: hidden;
        }

        .container { max-width: 480px; margin: 0 auto; padding-bottom: 3rem; }

        .header {
            text-align: center;
            padding: 1rem 0;
            margin-bottom: 0.75rem;
        }

        .header h1 {
            font-size: 1.375rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.25rem;
        }

        .header p { color: var(--text-muted); font-size: 0.8125rem; }

        .active-session {
            background: var(--surface);
            border: 1px solid var(--success);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .active-session h2 {
            color: var(--success);
            font-size: 1rem;
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .active-session .time-left {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--secondary);
            margin: 0.5rem 0;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 0.875rem;
            border: none;
            border-radius: 10px;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.15s;
            margin-bottom: 0.5rem;
        }

        .btn:active { transform: scale(0.98); }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary {
            background: var(--surface-light);
            color: var(--text);
            border: 1px solid var(--border);
        }
        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        .btn-success { background: var(--success); color: white; }
        .btn-ghost {
            background: transparent;
            color: var(--text-muted);
            border: none;
            font-size: 0.8125rem;
            padding: 0.5rem;
        }

        /* Packages Grid */
        .packages {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .package-card {
            background: var(--surface);
            border-radius: 10px;
            padding: 0.875rem;
            border: 2px solid transparent;
            cursor: pointer;
            transition: border-color 0.15s;
            text-align: center;
        }

        .package-card:hover, .package-card.selected {
            border-color: var(--primary);
        }

        .package-card h3 {
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text);
        }

        .package-card .price {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }

        .package-card .features {
            list-style: none;
            text-align: left;
        }

        .package-card .features li {
            padding: 0.125rem 0;
            color: var(--text-muted);
            font-size: 0.6875rem;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .action-card {
            background: var(--surface);
            border-radius: 10px;
            padding: 0.875rem;
            border: 1px solid var(--border);
            text-align: center;
        }

        .action-card h4 {
            font-size: 0.8125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .action-card input {
            width: 100%;
            padding: 0.625rem;
            background: var(--surface-light);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            font-family: monospace;
            text-transform: uppercase;
        }

        .action-card input:focus {
            outline: none;
            border-color: var(--secondary);
        }

        .action-card .btn {
            padding: 0.625rem;
            font-size: 0.8125rem;
            margin-bottom: 0;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--modal-overlay);
            display: flex;
            align-items: flex-end;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: var(--surface);
            border-radius: 20px 20px 0 0;
            padding: 1.25rem;
            width: 100%;
            max-width: 480px;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(100%);
            transition: transform 0.3s ease-out;
        }

        .modal-overlay.active .modal {
            transform: translateY(0);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
        }

        .modal-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text);
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.25rem;
            line-height: 1;
        }

        .modal-package {
            background: var(--surface-light);
            border-radius: 10px;
            padding: 0.875rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .modal-package .name {
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.25rem;
        }

        .modal-package .price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--secondary);
        }

        .modal-package .details {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }

        .modal-form .form-group {
            margin-bottom: 0.875rem;
        }

        .modal-form .form-group label {
            display: block;
            margin-bottom: 0.375rem;
            color: var(--text-muted);
            font-size: 0.8125rem;
            font-weight: 500;
        }

        .modal-form .form-group input {
            width: 100%;
            padding: 0.875rem;
            background: var(--surface-light);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-size: 1rem;
        }

        .modal-form .form-group input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .modal-footer {
            margin-top: 1rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border);
        }

        .modal-footer .note {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 0.75rem;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 1rem 0;
            color: var(--text-muted);
            font-size: 0.6875rem;
            border-top: 1px solid var(--border);
            margin-top: 1rem;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bg);
        }

        .footer a { color: var(--primary); text-decoration: none; }
        .credit {
            font-size: 0.625rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
            opacity: 0.8;
        }
        .credit span {
            color: var(--primary);
            font-weight: 600;
        }

        /* Utility */
        .info {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.75rem;
            margin-bottom: 1rem;
            font-size: 0.75rem;
            color: var(--text-muted);
            line-height: 1.5;
        }
        .info strong { color: var(--text); }
        .error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--error);
            padding: 0.625rem;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            font-size: 0.8125rem;
        }
        .success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--success);
            padding: 0.625rem;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            font-size: 0.8125rem;
        }
        .hidden { display: none !important; }
        .btn.loading {
            opacity: 0.7;
            pointer-events: none;
            position: relative;
        }
        .btn.loading:after {
            content: "";
            position: absolute;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            top: 50%;
            left: 50%;
            margin: -7px 0 0 -7px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 340px) {
            .packages, .quick-actions { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>CloudBridge WiFi</h1>
            <p>Choose a package to connect</p>
        </div>

        @if(session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        @if(isset($activeSession) && $activeSession)
            <div class="active-session">
                <h2>Session Active</h2>
                <div class="time-left" id="timeLeft">--:--</div>
                <p style="color: var(--text-muted); font-size: 0.75rem;">Remaining</p>
                <a href="{{ route('wifi.status', ['phone' => $phone]) }}" class="btn btn-success">Restore Connection</a>
                <a href="{{ route('wifi.extend', ['phone' => $phone]) }}" class="btn btn-outline">Add Time</a>
            </div>
        @else
            <div class="info">
                <strong>Quick Start:</strong> Tap a package, enter your M-Pesa number, complete the prompt.
            </div>

            {{-- Package Grid --}}
            <div class="packages">
                @forelse($packages as $pkg)
                <div class="package-card" data-package-id="{{ $pkg->id }}" data-package-name="{{ $pkg->name }}" data-package-price="{{ $pkg->price }}" data-package-duration="{{ $pkg->duration_minutes }}" data-package-speed="{{ $pkg->speed_mbps ?? '10' }}" tabindex="0">
                    <h3>{{ $pkg->name }}</h3>
                    <div class="price">KES {{ number_format($pkg->price) }}</div>
                    <ul class="features">
                        <li>{{ $pkg->duration_minutes }} min</li>
                        <li>{{ $pkg->speed_mbps ?? '10' }} Mbps</li>
                    </ul>
                </div>
                @empty
                <div class="error" style="grid-column: 1/-1;">No packages available.</div>
                @endforelse
            </div>

            {{-- Quick Actions - 2 Column --}}
            <div class="quick-actions">
                <div class="action-card">
                    <h4>Voucher</h4>
                    <form method="POST" action="{{ route('wifi.reconnect') }}">
                        @csrf
                        <input type="text" name="voucher_code" placeholder="Code" required maxlength="32" autocomplete="off">
                        <input type="hidden" name="phone" id="voucherPhone">
                        <button type="submit" class="btn btn-secondary">Apply</button>
                    </form>
                </div>
                <div class="action-card">
                    <h4>M-Pesa Code</h4>
                    <form method="POST" action="{{ route('wifi.reconnect') }}">
                        @csrf
                        <input type="hidden" name="reconnect_type" value="mpesa_code">
                        <input type="text" name="mpesa_code" placeholder="QGH45XYZ" required maxlength="32" autocomplete="off">
                        <input type="hidden" name="phone" id="mpesaPhone">
                        <button type="submit" class="btn btn-secondary">Reconnect</button>
                    </form>
                </div>
            </div>

            <p style="text-align: center; color: var(--text-muted); font-size: 0.75rem; margin-top: 0.5rem;">
                <a href="{{ route('wifi.reconnect.form') }}" style="color: var(--primary);">Full reconnect form →</a>
            </p>
        @endif
    </div>

    {{-- Payment Modal --}}
    <div class="modal-overlay" id="paymentModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Complete Payment</h3>
                <button class="modal-close" id="modalClose">&times;</button>
            </div>

            <div class="modal-package">
                <div class="name" id="modalPackageName">Package Name</div>
                <div class="price" id="modalPackagePrice">KES 0</div>
                <div class="details" id="modalPackageDetails">0 min • 0 Mbps</div>
            </div>

            <form method="POST" action="{{ route('wifi.pay') }}" class="modal-form" id="modalPaymentForm">
                @csrf
                <input type="hidden" name="package_id" id="modalPackageId">

                <div class="form-group">
                    <label for="modalPhone">M-Pesa Number</label>
                    <input type="tel" id="modalPhone" name="phone" placeholder="0712345678" required pattern="0[17]\d{8}" autocomplete="tel" inputmode="tel" autofocus>
                </div>

                <div class="modal-footer">
                    <p class="note">You will receive an M-Pesa prompt on this number</p>
                    <button type="submit" class="btn btn-primary" id="modalPayBtn">Pay and Connect</button>
                    <button type="button" class="btn btn-ghost" id="modalCancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="footer">
        <p>Need help? <a href="tel:+254700000000">Call Support</a></p>
        <p class="credit">Engineer <span>Omwenga Evans</span></p>
    </div>

    <script>
        // Modal logic
        const modal = document.getElementById('paymentModal');
        const modalClose = document.getElementById('modalClose');
        const modalCancel = document.getElementById('modalCancel');

        function openModal(pkg) {
            document.getElementById('modalPackageId').value = pkg.id;
            document.getElementById('modalPackageName').textContent = pkg.name;
            document.getElementById('modalPackagePrice').textContent = 'KES ' + pkg.price;
            document.getElementById('modalPackageDetails').textContent = pkg.duration + ' min • ' + pkg.speed + ' Mbps';
            modal.classList.add('active');
            document.getElementById('modalPhone').focus();
        }

        function closeModal() {
            modal.classList.remove('active');
        }

        modalClose.addEventListener('click', closeModal);
        modalCancel.addEventListener('click', closeModal);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });

        // Package selection opens modal
        document.querySelectorAll('.package-card').forEach(card => {
            const handler = function() {
                const pkg = {
                    id: card.dataset.packageId,
                    name: card.dataset.packageName,
                    price: card.dataset.packagePrice,
                    duration: card.dataset.packageDuration,
                    speed: card.dataset.packageSpeed
                };
                openModal(pkg);
            };
            card.addEventListener('click', handler);
            card.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    handler();
                }
            });
        });

        // Form submission
        document.getElementById('modalPaymentForm')?.addEventListener('submit', function(e) {
            const btn = document.getElementById('modalPayBtn');
            btn.classList.add('loading');
            btn.textContent = 'Processing';
        });

        // Sync phone to quick action forms
        document.addEventListener('DOMContentLoaded', function() {
            const modalPhone = document.getElementById('modalPhone');
            if (modalPhone) {
                ['voucherPhone', 'mpesaPhone'].forEach(id => {
                    const hidden = document.getElementById(id);
                    if (hidden) {
                        hidden.value = modalPhone.value;
                        modalPhone.addEventListener('input', function() {
                            hidden.value = this.value;
                        });
                    }
                });
            }
        });

        // Active session countdown
        @if(isset($activeSession) && $activeSession)
        const expiresAt = new Date('{{ $activeSession->expires_at }}').getTime();
        function updateTimeLeft() {
            const now = new Date().getTime();
            const distance = expiresAt - now;
            if (distance < 0) {
                document.getElementById('timeLeft').innerHTML = 'Expired';
                location.reload();
                return;
            }
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            document.getElementById('timeLeft').innerHTML =
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        updateTimeLeft();
        setInterval(updateTimeLeft, 1000);
        @endif
    </script>
</body>
</html>