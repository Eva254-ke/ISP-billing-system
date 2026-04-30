<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - CloudBridge Networks Admin</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Vite Assets -->
    @include('partials.vite-assets', ['entries' => ['resources/css/app.css', 'resources/js/app.js']])
    <style>
        @keyframes admin-login-fade {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        body {
            font-family: Inter, Arial, sans-serif;
            background: #f3f4f6 !important;
            opacity: 0;
            animation: admin-login-fade 300ms ease forwards;
        }

        .login-box,
        .login-logo,
        .card,
        .card-body,
        .input-group-text,
        .btn,
        .form-control,
        .alert {
            box-shadow: none !important;
            backdrop-filter: none !important;
            transition: none !important;
            background-image: none !important;
        }

        .card {
            border-radius: 8px !important;
        }

        .btn,
        .form-control,
        .input-group-text,
        .btn-close {
            border-radius: 4px !important;
        }
    </style>
</head>
<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <a href="#"><b>CloudBridge</b> Networks</a>
        </div>

        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Sign in to start your session</p>

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('login') }}" method="POST">
                    @csrf
                    <div class="input-group mb-3">
                        <input type="email" name="email" class="form-control" placeholder="Email" value="{{ old('email') }}" required>
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-8">
                            <div class="icheck-primary">
                                <input type="checkbox" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                                <label for="remember">Remember Me</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary w-100">Sign In</button>
                        </div>
                    </div>
                </form>

                <p class="mb-1 mt-3">
                    <a href="#">I forgot my password</a>
                </p>
            </div>
        </div>
    </div>

</body>
</html>
