<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin 404</title>
    <style>
        body {
            margin: 0;
            font-family: Segoe UI, Arial, sans-serif;
            background: #f3f4f6;
            color: #111827;
            display: grid;
            min-height: 100vh;
            place-items: center;
        }
        .card {
            width: min(620px, 92vw);
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
            background: #1f2937;
            color: #fff;
            padding: 10px 14px;
            border-radius: 8px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Admin Page Not Found</h1>
        <p>This admin URL does not exist.</p>
        <a href="{{ route('admin.dashboard') }}">Back to Dashboard</a>
    </div>
</body>
</html>
