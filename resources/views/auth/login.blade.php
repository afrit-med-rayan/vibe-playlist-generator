<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Vibe Playlist Generator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(ellipse at 20% 50%, #1a0533 0%, #0a0a1a 50%, #001a33 100%);
            overflow: hidden;
        }

        .bg-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            animation: float 8s ease-in-out infinite;
        }

        .orb1 {
            width: 400px;
            height: 400px;
            background: rgba(139, 92, 246, 0.2);
            top: -100px;
            left: -100px;
        }

        .orb2 {
            width: 300px;
            height: 300px;
            background: rgba(59, 130, 246, 0.15);
            bottom: -50px;
            right: -50px;
            animation-delay: -4s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) scale(1);
            }

            50% {
                transform: translateY(-30px) scale(1.05);
            }
        }

        .card {
            width: 100%;
            max-width: 400px;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            box-shadow: 0 32px 64px rgba(0, 0, 0, 0.4);
            position: relative;
            z-index: 1;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-icon {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 0.5rem;
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #a78bfa, #60a5fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            color: #fff;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: border-color 0.2s, background 0.2s;
        }

        input:focus {
            border-color: rgba(167, 139, 250, 0.6);
            background: rgba(255, 255, 255, 0.12);
        }

        .invalid {
            border-color: rgba(239, 68, 68, 0.7) !important;
        }

        .error-msg {
            color: #f87171;
            font-size: 0.78rem;
            margin-top: 0.35rem;
        }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.85rem;
        }

        .remember-row input[type="checkbox"] {
            accent-color: #7c3aed;
            width: 15px;
            height: 15px;
        }

        .btn-primary {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #7c3aed, #3b82f6);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.2s;
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .divider {
            text-align: center;
            color: rgba(255, 255, 255, 0.3);
            font-size: 0.85rem;
            margin: 1.25rem 0;
        }

        .link-secondary {
            display: block;
            text-align: center;
            color: #a78bfa;
            font-size: 0.9rem;
            text-decoration: none;
        }

        .link-secondary:hover {
            text-decoration: underline;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            color: #fca5a5;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }
    </style>
</head>

<body>
    <div class="bg-orb orb1"></div>
    <div class="bg-orb orb2"></div>

    <div class="card">
        <div class="logo">
            <span class="logo-icon">🎵</span>
            <h1>Vibe Generator</h1>
            <p>Welcome back</p>
        </div>

        @if(session('error'))
            <div class="alert-error">{{ session('error') }}</div>
        @endif

        <form action="/login" method="POST">
            @csrf

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com"
                    class="{{ $errors->has('email') ? 'invalid' : '' }}">
                @error('email') <div class="error-msg">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Your password"
                    class="{{ $errors->has('password') ? 'invalid' : '' }}">
                @error('password') <div class="error-msg">{{ $message }}</div> @enderror
            </div>

            <div class="remember-row">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember" style="margin-bottom:0; cursor:pointer;">Remember me</label>
            </div>

            <button type="submit" class="btn-primary">Sign In</button>
        </form>

        <div class="divider">Don't have an account?</div>
        <a href="/register" class="link-secondary">Create one for free →</a>
    </div>
</body>

</html>