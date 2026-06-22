<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found</title>
    <style>
        body {
            margin: 0;
            font-family: Segoe UI, Arial, sans-serif;
            background: #f7f9fc;
            color: #1f2937;
            display: grid;
            min-height: 100vh;
            place-items: center;
        }
        .card {
            width: min(560px, 92vw);
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
            text-align: center;
        }
        h1 { margin: 0 0 8px; font-size: 26px; }
        p { margin: 0 0 16px; color: #4b5563; }
        a {
            display: inline-block;
            text-decoration: none;
            background: #2563eb;
            color: #fff;
            padding: 10px 14px;
            border-radius: 8px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>404 Not Found</h1>
        <p>The page you requested does not exist.</p>
        <a href="{{ url('/') }}">Go Home</a>
    </div>
</body>
</html>
