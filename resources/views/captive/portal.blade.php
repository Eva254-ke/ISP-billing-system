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
        h2 { margin-top: 0; color: #111827; }
        h3 { font-size: 0.9rem; color: #6b7280; margin-bottom: 0.5rem; }
        
        /* 2-COLUMN GRID FOR PACKAGES */
        .packages-grid {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Forces exactly 2 columns */
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
        
        input { 
            width: 100%; 
            padding: 0.8rem; 
            margin: 0.5rem 0; 
            border: 1px solid #d1d5db; 
            border-radius: 0.5rem; 
            box-sizing: border-box; 
            font-size: 1rem;
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

        /* SUPPORT LINK & FOOTER */
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
    <!-- STATE 1: ALREADY CONNECTED (Fast Path) -->
    <div id="state-connected" class="{{ $isConnected ? '' : 'hidden' }}">
        <h2 style="color: #10b981;">✓ You are Connected!</h2>
        <p>Opening internet...</p>
        <script> triggerPortalClose(); </script>
    </div>

    <!-- STATE 2: MAIN MENU -->
    <div id="state-menu" class="{{ $isConnected ? 'hidden' : '' }}">
        <h2>Get Internet Access</h2>
        
        <!-- 2-COLUMN GRID FOR PACKAGES -->
        <div class="packages-grid">
            @foreach($packages as $pkg)
                <button class="btn btn-primary" onclick="payMpesa({{ $pkg->id }}, '{{ $pkg->name }}', {{ $pkg->price }})">
                    {{ $pkg->name }}<br>
                    <small style="opacity: 0.8; font-weight: normal;">KES {{ number_format($pkg->price) }}</small>
                </button>
            @endforeach
        </div>

        <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid #e5e7eb;">
        <h3>Already Paid or Have a Voucher?</h3>
        <input type="text" id="reconnect-code" placeholder="M-Pesa Code or Voucher">
        <button class="btn btn-secondary" onclick="reconnectAccess()">Reconnect</button>
        
        <!-- CALL SUPPORT LINK -->
        <div class="support-link">
            Need help? <a href="tel:0742939094">Call Support: 0742939094</a>
        </div>

        <!-- FOOTER -->
        <footer>
            Cloudbridge Technologies &copy; 2026
        </footer>
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

        try {
            const res = await fetch('/wifi/initiate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ mac: MAC, ip: IP, type: 'mpesa', phone, package_id: packageId })
            });

            // FIX: Catch HTML 500 errors from Laravel instead of breaking silently
            if (!res.ok) {
                let errorMsg = 'Server error. Please check logs.';
                try {
                    const errData = await res.json();
                    errorMsg = errData.message || errorMsg;
                } catch (e) {}
                alert(errorMsg);
                showState('menu');
                return;
            }

            const data = await res.json();

            if (data.payment_id) {
                pollPaymentStatus(data.payment_id);
            } else {
                alert(data.message || 'Failed to initiate payment.');
                showState('menu');
            }
        } catch (error) {
            console.error(error);
            alert('Network error. Please try again.');
            showState('menu');
        }
    }

    // 2. POLL STATUS
    async function pollPaymentStatus(paymentId) {
        const interval = setInterval(async () => {
            try {
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
            } catch (e) {
                console.error('Polling error', e);
            }
        }, 3000); 
    }

    // 3. HANDLE RECONNECT
    async function reconnectAccess() {
        const code = document.getElementById('reconnect-code').value;
        if (!code) return alert('Please enter a code');

        showState('processing', 'Verifying Code...', 'Checking M-Pesa receipt or Voucher.');

        try {
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
        } catch (error) {
            alert('Network error.');
            showState('menu');
        }
    }

    // 4. THE MAGIC PORTAL CLOSURE
    function triggerPortalClose() {
        window.open('', '_self');
        window.close();

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