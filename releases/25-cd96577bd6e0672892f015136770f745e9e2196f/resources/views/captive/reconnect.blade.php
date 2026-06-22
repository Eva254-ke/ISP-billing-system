<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#06B6D4">
    <title>Reconnect - CloudBridge WiFi</title>
    <style>
        :root {
            --primary: #7C3AED;
            --secondary: #06B6D4;
            --bg: #0F172A;
            --surface: #1E293B;
            --surface-light: #334155;
            --success: #10B981;
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
        
        .card {
            background: var(--surface);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--border);
        }
        
        .card h1 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            text-align: center;
            font-weight: 600;
        }
        
        .card .subtitle {
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
        }
        
        .icon { 
            font-size: 2.5rem; 
            text-align: center; 
            margin-bottom: 1rem;
            color: var(--secondary);
        }
        
        .form-group { margin-bottom: 0.875rem; }
        
        .form-group label {
            display: block;
            margin-bottom: 0.375rem;
            color: var(--text-muted);
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.875rem;
            background: var(--surface-light);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-size: 1rem;
            font-family: monospace;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--secondary);
        }
        
        .btn {
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
        
        .btn-primary {
            background: var(--secondary);
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--border);
        }
        
        .info {
            background: var(--surface-light);
            border-radius: 10px;
            padding: 0.875rem;
            margin: 0.75rem 0;
            font-size: 0.8125rem;
            color: var(--text-muted);
        }
        
        .error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--error);
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 0.875rem;
            font-size: 0.875rem;
        }
        
        .example {
            background: var(--surface);
            padding: 0.75rem;
            border-radius: 8px;
            text-align: center;
            margin: 0.75rem 0;
            color: var(--secondary);
            font-weight: 600;
            letter-spacing: 2px;
        }
        
        .footer {
            text-align: center;
            padding: 1rem 0 0.5rem;
            color: var(--text-muted);
            font-size: 0.6875rem;
            margin-top: 1rem;
            border-top: 1px solid var(--border);
        }
        
        .credit {
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid var(--surface-light);
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="icon">🔑</div>
            <h1>Reconnect with Code</h1>
            <p class="subtitle">Already paid? Enter your transaction code</p>
            
            @if($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif
            
            <form method="POST" action="{{ route('wifi.reconnect') }}">
                @csrf
                <div class="form-group">
                    <label for="mpesa_code">M-Pesa Transaction Code</label>
                    <input type="text" id="mpesa_code" name="mpesa_code" 
                           placeholder="QGH45XYZ123" 
                           required maxlength="32" autocomplete="off" 
                           style="text-transform: uppercase; letter-spacing: 2px;">
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number Used</label>
                    <input type="tel" id="phone" name="phone" 
                           placeholder="0712345678" 
                           required pattern="0[17]\d{8}" autocomplete="tel">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    Verify and Connect
                </button>
            </form>
            
            <div class="info">
                <strong>Where to find your code:</strong><br>
                Check your M-Pesa SMS message. The code looks like:
                <div class="example">QGH45XYZ123</div>
                It is usually 10-12 characters (letters and numbers)
            </div>
            
            <a href="{{ route('wifi.packages') }}" class="btn btn-outline">
                Back to Packages
            </a>
        </div>
        
        <div class="footer">
            <div class="credit">
                <p>System Architecture & Development</p>
                <p style="color: var(--secondary); font-weight: 600; margin-top: 0.25rem;">Omwenga Evans</p>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('mpesa_code').addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
        });
    </script>
</body>
</html>