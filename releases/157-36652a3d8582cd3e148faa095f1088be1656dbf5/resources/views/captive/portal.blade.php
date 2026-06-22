<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tenant->name ?? 'WiFi Portal' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f3f4f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        .btn { display: block; width: 100%; padding: 1rem; margin: 0.5rem 0; border: none; border-radius: 0.5rem; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        input { width: 100%; padding: 0.8rem; margin: 0.5rem 0; border: 1px solid #d1d5db; border-radius: 0.5rem; box-sizing: border-box; }
        .hidden { display: none; }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #2563eb; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 1rem auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="card">
    <!-- STATE 1: ALREADY CONNECTED (Fast Path) -->
    <div id="state-connected" class="{{ $isConnected ? '' : 'hidden' }}">
        <h2 style="color: #10b981;">✓ You are Connected!</h2>
        <p>Opening internet...</p>
        <script> triggerPortalClose(); </script>
    </div>

    <!-- STATE 2: MAIN MENU -->
    <div id="state-menu" class="{{ $isConnected ? 'hidden' : '' }}">
        <h2>Get Internet Access</h2>
        
        @foreach($packages as $pkg)
            <button class="btn btn-primary" onclick="payMpesa({{ $pkg->id }}, '{{ $pkg->name }}', {{ $pkg->price }})">
                {{ $pkg->name }} - KES {{ number_format($pkg->price) }}
            </button>
        @endforeach

        <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid #e5e7eb;">
        <h3 style="font-size: 0.9rem; color: #6b7280;">Already Paid or Have a Voucher?</h3>
        <input type="text" id="reconnect-code" placeholder="M-Pesa Code (e.g. QJK3...) or Voucher">
        <button class="btn btn-secondary" onclick="reconnectAccess()">Reconnect</button>
    </div>

    <!-- STATE 3: PROCESSING -->
    <div id="state-processing" class="hidden">
        <div class="spinner"></div>
        <h3 id="processing-title">Processing...</h3>
        <p id="processing-message" style="color: #6b7280;">Please wait.</p>
    </div>
</div>

<script>
    const MAC = '{{ $mac }}';
    const IP = '{{ $ip }}';
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    // 1. HANDLE M-PESA
    async function payMpesa(packageId, name, amount) {
        const phone = prompt(`Enter M-Pesa Phone Number for ${name} (KES ${amount}):`);
        if (!phone) return;

        showState('processing', 'Sending M-Pesa Prompt...', 'Check your phone and enter your PIN.');

        const res = await fetch('/wifi/initiate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ mac: MAC, ip: IP, type: 'mpesa', phone, package_id: packageId })
        });
        const data = await res.json();

        if (data.payment_id) {
            pollPaymentStatus(data.payment_id);
        } else {
            alert(data.message || 'Failed to initiate payment.');
            showState('menu');
        }
    }

    // 2. POLL STATUS
    async function pollPaymentStatus(paymentId) {
        const interval = setInterval(async () => {
            const res = await fetch(`/wifi/status/${paymentId}`);
            const data = await res.json();

            if (data.status === 'connected') {
                clearInterval(interval);
                showState('connected');
                triggerPortalClose(); 
            } else if (data.status === 'failed') {
                clearInterval(interval);
                alert(data.message);
                showState('menu');
            } else {
                document.getElementById('processing-message').innerText = data.message;
            }
        }, 3000); 
    }

    // 3. HANDLE RECONNECT
    async function reconnectAccess() {
        const code = document.getElementById('reconnect-code').value;
        if (!code) return alert('Please enter a code');

        showState('processing', 'Verifying Code...', 'Checking M-Pesa receipt or Voucher.');

        const res = await fetch('/wifi/reconnect', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ mac: MAC, ip: IP, code })
        });
        const data = await res.json();

        if (data.status === 'connected') {
            showState('connected');
            triggerPortalClose();
        } else {
            alert(data.message || 'Invalid code.');
            showState('menu');
        }
    }

    // 4. THE MAGIC PORTAL CLOSURE (Zero-Friction Auto-Connect)
    function triggerPortalClose() {
        // Attempt to close the webview natively
        window.open('', '_self');
        window.close();

        // Fallback: Redirect to OS connectivity check URLs to force the OS to close the portal
        setTimeout(() => {
            const ua = navigator.userAgent;
            if (/iPhone|iPad|iPod|Macintosh/.test(ua)) {
                window.location.replace('http://captive.apple.com/hotspot-detect.html');
            } else {
                window.location.replace('http://neverssl.com');
            }
        }, 1000);
    }

    function showState(state, title = '', message = '') {
        document.getElementById('state-menu').classList.toggle('hidden', state !== 'menu');
        document.getElementById('state-processing').classList.toggle('hidden', state !== 'processing');
        document.getElementById('state-connected').classList.toggle('hidden', state !== 'connected');
        if (title) document.getElementById('processing-title').innerText = title;
        if (message) document.getElementById('processing-message').innerText = message;
    }
</script>
</body>
</html>