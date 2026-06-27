<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $title ?? 'Error' }}</title>
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
        .error-icon {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        .error-message {
            color: #4b5563;
            font-size: 1rem;
            line-height: 1.6;
            margin: 1rem 0;
        }
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
    <div class="error-icon">⚠️</div>
    <h2>{{ $title ?? 'Error' }}</h2>
    <p class="error-message">{{ $message ?? 'An unexpected error occurred. Please try again.' }}</p>

    <div class="support-link">
        Need help? <a href="tel:0742939094">Call Support: 0742939094</a>
    </div>

    <footer>
        Cloudbridge Technologies &copy; {{ date('Y') }}
    </footer>
</div>

</body>
</html>