<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#7C3AED">
    <meta http-equiv="refresh" content="10">
    <title>Payment Status - CloudBridge WiFi</title>
    <style>
        :root {
            --primary: #7C3AED;
            --primary-dark: #6D28D9;
            --secondary: #06B6D4;
            --bg: #0F172A;
            --surface: #1E293B;
            --surface-light: #334155;
            --success: #10B981;
            --warning: #F97316;
            --error: #EF4444;
            --text: #FFFFFF;
            --text-muted: #94A3B8;
            --border: #475569;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            min-height: 100vh;
            color: var(--text);
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container { max-width: 480px; width: 100%; }
        
        .status-card {
            background: var(--surface);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid var(--border);
        }
        
        .icon { font-size: 3rem; margin-bottom: 1rem; }
        
        .status-card h1 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .status-card p {
            color: var(--text-muted);
            margin-bottom: 1.25rem;
            line-height: 1.5;
            font-size: 0.875rem;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--surface-light);
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            margin: 0 auto 1.25rem;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .steps {
            text-align: left;
            margin: 1.25rem 0;
            padding: 0.875rem;
            background: var(--surface-light);
            border-radius: 10px;
        }
        
        .step {
            display: flex;
            align-items: center;
            padding: 0.4rem 0;
            color: var(--text-muted);
            font-size: 0.8125rem;
        }
        
        .step.active { color: var(--primary); font-weight: 600; }
        .step.completed { color: var(--success); }
        .step .check { margin-right: 0.5rem; }
        
        .timer {
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary);
            margin: 0.75rem 0;
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
            margin-top: 0.75rem;
        }
        
        .btn:active { transform: scale(0.98); }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--border);
        }
        
        .session-info {
            background: var(--surface-light);
            border-radius: 10px;
            padding: 0.875rem;
            margin: 0.75rem 0;
            text-align: left;
        }
        
        .session-info .row {
            display: flex;
            justify-content: space-between;
            padding: 0.4rem 0;
            border-bottom: 1px solid var(--border);
            font-size: 0.8125rem;
        }
        
        .session-info .row:last-child { border-bottom: none; }
        .session-info .label { color: var(--text-muted); }
        .session-info .value { font-weight: 600; }
        
        .info {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.875rem;
            margin: 0.75rem 0;
            font-size: 0.8125rem;
            color: var(--text-muted);
            text-align: left;
        }
        
        .error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--error);
            padding: 0.75rem;
            border-radius: 8px;
            margin: 0.75rem 0;
            font-size: 0.8125rem;
            text-align: left;
        }
        
        .footer {
            text-align: center;
            padding: 1rem 0 0.5rem;
            color: var(--text-muted);
            font-size: 0.6875rem;
            margin-top: 1rem;
            border-top: 1px solid var(--border);
        }
        
        .footer a { color: var(--primary); text-decoration: none; }
        
        .credit {
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid var(--surface-light);
            opacity: 0.7;
        }
        
        .phone-masked {
            font-family: monospace;
            font-size: 0.9375rem;
            color: var(--secondary);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="status-card">
            @if($statusView === 'pending')
                <div class="spinner"></div>
                <h1>Payment Pending</h1>
                <p>Complete the STK Push on your phone<br><span class="phone-masked">{{ $phone }}</span></p>
                
                <div class="steps">
                    <div class="step completed"><span class="check">✓</span> Package Selected</div>
                    <div class="step active"><span class="check">○</span> Awaiting Payment</div>
                    <div class="step"><span class="check">○</span> Activating Session</div>
                    <div class="step"><span class="check">○</span> Connected</div>
                </div>
                
                <div class="info">
                    <strong>What to do:</strong><br>
                    1. Check your phone for M-Pesa prompt<br>
                    2. Enter your PIN to complete payment<br>
                    3. This page will refresh automatically
                </div>
                
                <a href="{{ route('wifi.packages') }}" class="btn btn-outline">
                    Back to Packages
                </a>
            
            @elseif($statusView === 'paid')
                <div class="spinner"></div>
                <h1>Payment Received</h1>
                <p>Activating your session on the router...</p>
                
                <div class="steps">
                    <div class="step completed"><span class="check">✓</span> Package Selected</div>
                    <div class="step completed"><span class="check">✓</span> Payment Confirmed</div>
                    <div class="step active"><span class="check">○</span> Activating Session</div>
                    <div class="step"><span class="check">○</span> Connected</div>
                </div>
                
                <div class="info">
                    <strong>Please wait...</strong><br>
                    We are configuring your access on the router.<br>
                    This takes about 10-30 seconds.
                </div>

                @if(!empty($radiusFallback))
                <div class="info" style="border-color: var(--warning);">
                    <strong>Connection taking longer?</strong><br>
                    Use this only if auto-connect fails after 30 seconds:<br>
                    Username: <strong>{{ $radiusFallback['username'] }}</strong><br>
                    Password: {{ $radiusFallback['password_hint'] }}
                </div>
                @endif
                
                <a href="{{ url()->current() }}" class="btn btn-outline">
                    Refresh Status
                </a>
            
            @elseif($statusView === 'activated')
                <div class="icon">✓</div>
                <h1 style="color: var(--success);">You are Connected</h1>
                <p>Enjoy your internet access</p>
                
                <div class="session-info">
                    <div class="row">
                        <span class="label">Package</span>
                        <span class="value">{{ $payment->package->name ?? 'N/A' }}</span>
                    </div>
                    <div class="row">
                        <span class="label">Duration</span>
                        <span class="value">{{ $payment->package->duration_minutes ?? 0 }} minutes</span>
                    </div>
                    <div class="row">
                        <span class="label">Expires At</span>
                        <span class="value" id="expiresAt">--:--</span>
                    </div>
                </div>
                
                <div class="timer" id="countdown">--:--</div>
                
                <a href="http://google.com" target="_blank" class="btn btn-success">
                    Browse Internet
                </a>
                
                <a href="{{ route('wifi.packages') }}" class="btn btn-outline">
                    Buy More Time
                </a>
            
            @elseif($statusView === 'failed')
                <div class="icon">!</div>
                <h1 style="color: var(--error);">Payment Failed</h1>
                <p>{{ $payment->callback_payload['reason'] ?? 'Payment was not completed' }}</p>
                
                <div class="error">
                    <strong>Possible reasons:</strong><br>
                    - Insufficient balance<br>
                    - STK push timed out<br>
                    - Wrong PIN entered
                </div>
                
                <a href="{{ route('wifi.packages') }}" class="btn btn-primary">
                    Try Again
                </a>
                
                <a href="{{ route('wifi.reconnect') }}" class="btn btn-outline">
                    Have M-Pesa Code?
                </a>
            
            @else
                <div class="icon">!</div>
                <h1>Unknown Status</h1>
                <p>Payment status: {{ $payment->status }}</p>
                
                <a href="{{ route('wifi.packages') }}" class="btn btn-primary">
                    Back to Packages
                </a>
            @endif
        </div>
        
        <div class="footer">
            <p>Need help? <a href="tel:+254700000000">Call Support</a></p>
            <div class="credit">
                <p>System Architecture & Development</p>
                <p style="color: var(--primary); font-weight: 600; margin-top: 0.25rem;">Omwenga Evans</p>
            </div>
        </div>
    </div>
    
    <script>
        @if(in_array($statusView, ['pending', 'paid']))
        setInterval(async () => {
            try {
                const response = await fetch('{{ route('wifi.status.check', ['phone' => $phone]) }}', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) return;

                const payload = await response.json();
                if (payload && payload.session_active) {
                    window.location.reload();
                }
            } catch (e) {
                // Keep silent; page still has periodic meta refresh fallback.
            }
        }, 5000);
        @endif

        @if($statusView === 'activated' && !empty($activeSession?->expires_at))
        const expiresAt = new Date('{{ $activeSession->expires_at->toIso8601String() }}').getTime();
        
        function updateCountdown() {
            const now = new Date().getTime();
            const distance = expiresAt - now;
            
            if (distance < 0) {
                document.getElementById('countdown').innerHTML = 'EXPIRED';
                document.getElementById('expiresAt').innerHTML = 'Session Expired';
                return;
            }
            
            const hours = Math.floor(distance / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById('countdown').innerHTML = 
                `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            document.getElementById('expiresAt').innerHTML = new Date(expiresAt).toLocaleTimeString();
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
        @endif
    </script>
</body>
</html>