<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0e7490">
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
        $packagesParams = array_filter([
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
    <main class="cp-page cp-page-narrow">
        <header class="cp-topbar">
            <div class="cp-brand">
                <div class="cp-brand-mark">CB</div>
                <div class="cp-brand-text">
                    <h1>{{ $tenant?->name ?: 'CloudBridge WiFi' }}</h1>
                    <p>Restore internet access</p>
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
                <div class="cp-flow-step">1. Enter payment proof</div>
                <div class="cp-flow-step is-current">2. Verify details</div>
                <div class="cp-flow-step">3. Connect internet</div>
            </div>

            <h2 class="cp-section-title">Reconnect internet</h2>
            <p class="cp-card-subtitle">Use one option only: M-Pesa transaction code or voucher code.</p>

            <div class="cp-form-stack">
                <section class="cp-form-card">
                    <h3 class="cp-form-title">Reconnect with M-Pesa code</h3>
                    <form method="POST" action="{{ route('wifi.reconnect') }}">
                        @csrf
                        <div class="cp-field">
                            <label for="reconnectPhone">Phone Number</label>
                            <input id="reconnectPhone" type="tel" name="phone" placeholder="0712345678" value="{{ old('phone', $phone ?? '') }}" required pattern="0[17]\d{8}" autocomplete="tel" inputmode="tel">
                        </div>
                        <div class="cp-field">
                            <label for="mpesaCode">M-Pesa Transaction Code</label>
                            <input id="mpesaCode" type="text" name="mpesa_code" placeholder="QGH45XYZ" value="{{ old('mpesa_code', '') }}" required maxlength="32" autocomplete="off">
                        </div>
                        <button type="submit" class="cp-btn cp-btn-primary cp-btn-block">Verify and Connect</button>
                    </form>
                </section>

                <section class="cp-form-card">
                    <h3 class="cp-form-title">Redeem voucher</h3>
                    <form method="POST" action="{{ route('wifi.reconnect') }}">
                        @csrf
                        <div class="cp-field">
                            <label for="voucherPhone">Phone Number</label>
                            <input id="voucherPhone" type="tel" name="phone" placeholder="0712345678" value="{{ old('phone', $phone ?? '') }}" required pattern="0[17]\d{8}" autocomplete="tel" inputmode="tel">
                        </div>
                        <div class="cp-field">
                            <label for="voucherCode">Voucher Code</label>
                            <input id="voucherCode" type="text" name="voucher_code" placeholder="CB-WIFI-1234" value="{{ old('voucher_code', '') }}" required maxlength="64" autocomplete="off">
                        </div>
                        <button type="submit" class="cp-btn cp-btn-soft cp-btn-block">Redeem Voucher</button>
                    </form>
                </section>
            </div>
        </article>

        <article class="cp-card cp-card-compact">
            <h3 class="cp-section-subtitle">Need to buy a package instead?</h3>
            <a href="{{ route('wifi.packages', $packagesParams) }}" class="cp-btn cp-btn-outline cp-btn-block">Go to Packages</a>
        </article>

        <footer class="cp-footer">
            <p><a class="cp-link-support" href="{{ $supportTelHref }}">Call support</a></p>
            <p>Engineered by Engineer Omwenga Evans</p>
        </footer>
    </main>

    <script>
        const codeInputs = [document.getElementById('mpesaCode'), document.getElementById('voucherCode')];
        codeInputs.forEach((input) => {
            input?.addEventListener('input', function () {
                this.value = this.value.toUpperCase().trimStart();
            });
        });
    </script>
</body>
</html>
