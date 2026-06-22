<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $tenant->name ?? 'WiFi Portal' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            background: #f3f4f6; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
            padding: 15px;
            box-sizing: border-box;
        }
        .card { 
            background: white; 
            padding: 1.5rem; 
            border-radius: 1rem; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            width: 100%; 
            max-width: 450px; 
            text-align: center; 
        }
        h2 { margin-top: 0; color: #111827; font-size: 1.5rem; }
        h3 { font-size: 0.9rem; color: #6b7280; margin-bottom: 0.5rem; }
        
        .packages-grid {
            display: grid;
            grid-template-columns: 1fr 1fr; 
            gap: 12px;
            margin-top: 15px;
        }

        .btn { 
            display: block; 
            width: 100%; 
            border: none; 
            border-radius: 0.5rem; 
            font-size: 0.95rem; 
            font-weight: 600; 
            cursor: pointer; 
            transition: 0.2s; 
            box-sizing: border-box;
        }
        .btn-primary { 
            background: #2563eb; 
            color: white; 
            padding: 1rem 0.5rem; 
        }
        .btn-primary:active { background: #1d4ed8; }
        
        .btn-secondary { 
            background: #e5e7eb; 
            color: #374151; 
            padding: 0.8rem; 
            margin-top: 0.5rem;
        }
        
        input[type="text"], input[type="tel"] { 
            width: 100%; 
            padding: 0.8rem; 
            margin: 0.5rem 0; 
            border: 1px solid #d1d5db; 
            border-radius: 0.5rem; 
            box-sizing: border-box; 
            font-size: 1rem;
            outline: none;
            transition: border-color 0.2s;
        }
        input:focus {
            border-color: #2563eb;
        }

        #phone-number-input {
            padding: 1rem;
            font-size: 1.25rem;
            text-align: center;
            letter-spacing: 2px;
            border: 2px solid #d1d5db;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }
        #phone-number-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .hidden { display: none; }
        
        .spinner { 
            border: 4px solid #f3f3f3; 
            border-top: 4px solid #2563eb; 
            border-radius: 50%; 
            width: 40px; 
            height: 40px; 
            animation: spin 1s linear infinite; 
            margin: 1rem auto; 
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .support-link {
            margin-top: 25px;
            font-size: 0.9rem;
            color: #4b5563;
        }
        .support-link a {
            color: #2563eb;
            font-weight: bold;
            text-decoration: none;
        }
        footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            font-size: 0.8rem;
            color: #9ca3af;
        }
    </style>
</head>
<body>

<div class="card">
    <!-- MAIN MENU -->
    <div id="state-menu">
        <h2>Get Internet Access</h2>
        
        <div class="packages-grid">
            @foreach($packages as $pkg)
                <button class="btn btn-primary" onclick="showPhoneInput({{ $pkg->id }}, '{{ $pkg->name }}', {{ $pkg->price }})">
                    {{ $pkg->name }}<br>
                    <small style="opacity: 0.8; font-weight: normal;">KES {{ number_format($pkg->price) }}</small>
                </button>
            @endforeach
        </div>

        <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid #e5e7eb;">
        <h3>Already Paid or Have a Voucher?</h3>
        <input type="text" id="reconnect-code" placeholder="M-Pesa Code or Voucher">
        <button class="btn btn-secondary" onclick="reconnectAccess()">Reconnect</button>
        
        <div class="support-link">
            Need help? <a href="tel:0742939094">Call Support: 0742939094</a>
        </div>

        <footer>
            Cloudbridge Technologies &copy; 2026
        </footer>
    </div>

    <!-- PHONE INPUT -->
    <div id="state-phone-input" class="hidden">
        <h2>Enter M-Pesa Number</h2>
        <p id="phone-input-details" style="color: #6b7280; margin-bottom: 0.5rem; font-size: 0.95rem;"></p>
        
        <input type="tel" id="phone-number-input" placeholder="0712345678" maxlength="12" inputmode="numeric" autocomplete="tel">
        
        <button class="btn btn-primary" onclick="submitPhoneNumber()">Send STK Push</button>
        <button class="btn btn-secondary" onclick="showState('menu')">Cancel</button>
    </div>

    <!-- PROCESSING -->
    <div id="state-processing" class="hidden">
        <div class="spinner"></div>
        <h3 id="processing-title">Processing...</h3>
        <p id="processing-message" style="color: #6b7280;">Please wait.</p>
    </div>
</div>

<script>
    const MAC = '{{ $mac }}';
    const IP = '{{ $ip }}';
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    
    let selectedPackage = { id: 0, name: '', amount: 0 };

    function showPhoneInput(packageId, name, amount) {
        selectedPackage = { id: packageId, name, amount };
        document.getElementById('phone-input-details').innerHTML = `For <strong>${name}</strong> (KES ${amount.toLocaleString()})`;
        document.getElementById('phone-number-input').value = ''; 
        showState('phone-input');
        
        setTimeout(() => {
            document.getElementById('phone-number-input').focus();
        }, 100);
    }

    function submitPhoneNumber() {
        const phoneInput = document.getElementById('phone-number-input');
        let phone = phoneInput.value.trim();
        
        if (!phone) {
            alert('Please enter your M-Pesa phone number.');
            return;
        }
        
        phone = phone.replace(/\D/g, '');
        
        if (phone.startsWith('254')) {
            phone = '0' + phone.substring(3);
        } else if (phone.startsWith('7') || phone.startsWith('1')) {
            phone = '0' + phone;
        }
        
        if (!/^(0[17]\d{8})$/.test(phone)) {
            alert('Invalid number. Please use 07XXXXXXXX or 01XXXXXXXX.');
            return;
        }

        initiatePayment(phone);
    }

    async function initiatePayment(phone) {
        showState('processing', 'Sending M-Pesa Prompt...', 'Check your phone and enter your PIN.');

        try {
            const res = await fetch('/wifi/initiate', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF 
                },
                body: JSON.stringify({ 
                    mac: MAC, 
                    ip: IP, 
                    type: 'mpesa', 
                    phone: phone, 
                    package_id: selectedPackage.id 
                })
            });

            let data;
            const text = await res.text();
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Server returned non-JSON:', text.substring(0, 200));
                alert('Server Error: Invalid response. Please contact support.');
                showState('menu');
                return;
            }

            if (!res.ok) {
                let errorMsg = data.message || 'Server error.';
                if (data.errors) {
                    errorMsg = Object.values(data.errors).flat().join(', ');
                }
                alert(errorMsg);
                showState('menu');
                return;
            }

            if (data.payment_id) {
                pollPaymentStatus(data.payment_id);
            } else {
                alert(data.message || 'Failed to initiate payment.');
                showState('menu');
            }
        } catch (error) {
            console.error('Fetch error:', error);
            alert('Network error: ' + error.message);
            showState('menu');
        }
    }

    async function pollPaymentStatus(paymentId) {
        const interval = setInterval(async () => {
            try {
                const res = await fetch(`/wifi/status/${paymentId}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (data.status === 'connected') {
                    clearInterval(interval);
                    // Immediately redirect to test connectivity - let MikroTik handle the rest
                    triggerPortalClose();
                } else if (data.status === 'failed') {
                    clearInterval(interval);
                    alert(data.message);
                    showState('menu');
                } else {
                    document.getElementById('processing-message').innerText = data.message;
                }
            } catch (e) {
                console.error('Polling error', e);
            }
        }, 3000); 
    }

    async function reconnectAccess() {
        const code = document.getElementById('reconnect-code').value.trim();
        if (!code) return alert('Please enter a code');

        showState('processing', 'Verifying Code...', 'Checking M-Pesa receipt or Voucher.');

        try {
            const res = await fetch('/wifi/reconnect', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF 
                },
                body: JSON.stringify({ mac: MAC, ip: IP, code })
            });

            let data;
            const text = await res.text();
            try {
                data = JSON.parse(text);
            } catch (e) {
                alert('Server Error: Invalid response.');
                showState('menu');
                return;
            }

            if (!res.ok) {
                alert(data.message || 'Invalid code.');
                showState('menu');
                return;
            }

            if (data.status === 'connected') {
                // Immediately redirect to test connectivity
                triggerPortalClose();
            } else {
                alert(data.message || 'Invalid code.');
                showState('menu');
            }
        } catch (error) {
            alert('Network error: ' + error.message);
            showState('menu');
        }
    }

    function triggerPortalClose() {
        // Redirect to neverssl.com to test connectivity
        // If authenticated, the page will load and the captive portal will close
        // If not authenticated, MikroTik will redirect back to the portal
        setTimeout(() => { window.location.replace('http://login.wifi/login?username=' + encodeURIComponent(MAC) + '&password=' + encodeURIComponent(MAC)); }, 2000);
    }

    function showState(state, title = '', message = '') {
        document.getElementById('state-menu').classList.toggle('hidden', state !== 'menu');
        document.getElementById('state-phone-input').classList.toggle('hidden', state !== 'phone-input');
        document.getElementById('state-processing').classList.toggle('hidden', state !== 'processing');
        
        if (title) document.getElementById('processing-title').innerText = title;
        if (message) document.getElementById('processing-message').innerText = message;
    }
</script>
</body>
</html>