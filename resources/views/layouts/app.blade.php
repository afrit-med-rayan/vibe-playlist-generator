<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Vibe Playlist Generator') | VibeCraft</title>
    <meta name="description"
        content="Turn your photos into personalized playlists using AI, Last.fm mood tags, and Deezer previews.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg-deep: #0a0a1a;
            --bg-mid: #0f0c29;
            --accent-purple: #7C3AED;
            --accent-green: #1DB954;
            --glass-bg: rgba(255, 255, 255, 0.06);
            --glass-border: rgba(255, 255, 255, 0.12);
            --text-primary: #F1F0FF;
            --text-muted: rgba(241, 240, 255, 0.55);
        }

        html,
        body {
            height: 100%;
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg-deep);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
        }

        /* ---- Animated background ---- */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 10% -10%, rgba(124, 58, 237, 0.35) 0%, transparent 60%),
                radial-gradient(ellipse 70% 50% at 90% 110%, rgba(29, 185, 84, 0.18) 0%, transparent 55%),
                radial-gradient(ellipse 100% 80% at 50% 50%, #0f0c29 0%, #0a0a1a 100%);
            z-index: -1;
            animation: bgPulse 12s ease-in-out infinite alternate;
        }

        @keyframes bgPulse {
            0% {
                opacity: 1;
            }

            100% {
                opacity: 0.85;
                filter: hue-rotate(20deg);
            }
        }

        /* ---- Navbar ---- */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            height: 64px;
            background: rgba(10, 10, 26, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--glass-border);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 700;
            font-size: 1.15rem;
            letter-spacing: -0.02em;
        }

        .navbar-brand .logo-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-green));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .navbar-nav {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.9rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-muted);
            transition: all 0.2s;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--text-primary);
            background: var(--glass-bg);
        }

        .nav-badge-spotify {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.75rem;
            border-radius: 20px;
            background: rgba(29, 185, 84, 0.15);
            border: 1px solid rgba(29, 185, 84, 0.35);
            color: var(--accent-green);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .nav-badge-spotify .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--accent-green);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: 0.4
            }
        }

        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.9rem;
            border-radius: 8px;
            background: none;
            border: 1px solid var(--glass-border);
            color: var(--text-muted);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }

        .btn-logout:hover {
            color: #f87171;
            border-color: rgba(248, 113, 113, 0.4);
            background: rgba(248, 113, 113, 0.08);
        }

        /* ---- Main layout ---- */
        .main-content {
            min-height: calc(100vh - 64px);
            padding: 2rem 2rem 4rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        /* ---- Flash / alerts ---- */
        .flash-success {
            background: rgba(29, 185, 84, 0.12);
            border: 1px solid rgba(29, 185, 84, 0.3);
            color: #4ade80;
            padding: 0.85rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .flash-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            padding: 0.85rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        /* ---- Glass card ---- */
        .glass-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 0 1rem;
            }

            .main-content {
                padding: 1.25rem 1rem 3rem;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

    <nav class="navbar">
        <a href="{{ route('dashboard') }}" class="navbar-brand">
            <span class="logo-icon">🎵</span>
            VibeCraft
        </a>

        <div class="navbar-nav">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                ⬡ Dashboard
            </a>
            <a href="{{ route('vibe.history') }}"
                class="nav-link {{ request()->routeIs('vibe.history') ? 'active' : '' }}">
                ⏱ History
            </a>

            <form method="POST" action="{{ route('logout') }}" style="display:inline">
                @csrf
                <button type="submit" class="btn-logout">↩ Logout</button>
            </form>
        </div>
    </nav>

    <div class="main-content">
        @if(session('success'))
            <div class="flash-success">✓ {{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="flash-error">
                @foreach($errors->all() as $error)
                    <div>✕ {{ $error }}</div>
                @endforeach
            </div>
        @endif

        @yield('content')
    </div>

    @stack('scripts')
</body>

</html>