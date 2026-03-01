<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VibeCraft — Turn Your Vibes Into Playlists</title>
    <meta name="description"
        content="Upload a photo, let AI detect the vibe, and get a personalized playlist powered by Last.fm + Deezer.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
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
            --purple: #7C3AED;
            --purple-light: #A78BFA;
            --green: #1DB954;
            --green-light: #4ade80;
            --bg-dark: #0a0a1a;
            --text: #F1F0FF;
            --text-muted: rgba(241, 240, 255, 0.6);
            --glass: rgba(255, 255, 255, 0.07);
            --glass-border: rgba(255, 255, 255, 0.12);
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg-dark);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* ─── Background orbs ─── */
        .bg-orbs {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.45;
            animation: float 8s ease-in-out infinite;
        }

        .orb-1 {
            width: 600px;
            height: 600px;
            background: var(--purple);
            top: -200px;
            left: -150px;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 500px;
            height: 500px;
            background: var(--green);
            bottom: -200px;
            right: -100px;
            animation-delay: 4s;
        }

        .orb-3 {
            width: 300px;
            height: 300px;
            background: #EC4899;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: 2s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) scale(1);
            }

            50% {
                transform: translateY(-30px) scale(1.05);
            }
        }

        .orb-3 {
            animation: float3 10s ease-in-out infinite;
        }

        @keyframes float3 {

            0%,
            100% {
                transform: translate(-50%, -50%) scale(1);
            }

            50% {
                transform: translate(-50%, -55%) scale(1.1);
            }
        }

        /* ─── Noise overlay ─── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.035'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 1;
        }

        /* ─── Content wrapper ─── */
        .page-wrapper {
            position: relative;
            z-index: 2;
        }

        /* ─── Navbar ─── */
        nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 2.5rem;
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(10, 10, 26, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-weight: 800;
            font-size: 1.2rem;
            letter-spacing: -0.03em;
            text-decoration: none;
            color: var(--text);
        }

        .brand-icon {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, var(--purple), var(--green));
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-outline {
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            border: 1px solid var(--glass-border);
            background: var(--glass);
            color: var(--text);
            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.2s;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.25);
        }

        .btn-spotify {
            padding: 0.55rem 1.35rem;
            border-radius: 8px;
            background: var(--green);
            color: #000;
            font-weight: 700;
            font-size: 0.875rem;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            box-shadow: 0 0 24px rgba(29, 185, 84, 0.3);
        }

        .btn-spotify:hover {
            transform: translateY(-1px);
            box-shadow: 0 0 36px rgba(29, 185, 84, 0.5);
            background: #20d65f;
        }

        /* ─── Hero ─── */
        .hero {
            min-height: 90vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 4rem 1.5rem;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            border-radius: 99px;
            background: rgba(124, 58, 237, 0.15);
            border: 1px solid rgba(124, 58, 237, 0.35);
            color: var(--purple-light);
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 2rem;
            animation: fadeInUp 0.6s ease both;
        }

        .hero-title {
            font-size: clamp(2.8rem, 8vw, 5.5rem);
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: -0.04em;
            margin-bottom: 1.75rem;
            animation: fadeInUp 0.6s ease 0.1s both;
        }

        .hero-title .gradient-text {
            background: linear-gradient(135deg, var(--purple-light) 0%, var(--green-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-sub {
            max-width: 580px;
            font-size: 1.1rem;
            line-height: 1.7;
            color: var(--text-muted);
            margin-bottom: 3rem;
            animation: fadeInUp 0.6s ease 0.2s both;
        }

        .hero-cta {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
            animation: fadeInUp 0.6s ease 0.3s both;
        }

        .btn-cta-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.9rem 2rem;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--purple), #5B21B6);
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.25s;
            box-shadow: 0 0 40px rgba(124, 58, 237, 0.4);
            border: none;
        }

        .btn-cta-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 60px rgba(124, 58, 237, 0.55);
        }

        .btn-cta-spotify {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.9rem 2rem;
            border-radius: 12px;
            background: var(--green);
            color: #000;
            font-weight: 800;
            font-size: 1rem;
            text-decoration: none;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.25s;
            box-shadow: 0 0 40px rgba(29, 185, 84, 0.4);
            border: none;
        }

        .btn-cta-spotify:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 60px rgba(29, 185, 84, 0.55);
            background: #20d65f;
        }

        .spotify-icon {
            width: 20px;
            height: 20px;
            fill: #000;
        }

        /* ─── Hero visual preview ─── */
        .hero-visual {
            margin-top: 5rem;
            position: relative;
            width: 100%;
            max-width: 800px;
            animation: fadeInUp 0.7s ease 0.4s both;
        }

        .preview-card {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.5rem;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .preview-upload-zone {
            background: rgba(124, 58, 237, 0.08);
            border: 2px dashed rgba(124, 58, 237, 0.4);
            border-radius: 12px;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            min-height: 200px;
        }

        .preview-upload-icon {
            font-size: 2.5rem;
        }

        .preview-upload-text {
            color: var(--text-muted);
            font-size: 0.85rem;
            text-align: center;
        }

        .preview-info {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .preview-vibe-label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--purple-light);
        }

        .preview-vibe-text {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text);
        }

        .preview-keywords {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-top: 0.25rem;
        }

        .kw-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 99px;
            font-size: 0.7rem;
            font-weight: 600;
            background: rgba(124, 58, 237, 0.2);
            border: 1px solid rgba(124, 58, 237, 0.35);
            color: var(--purple-light);
        }

        .preview-bar-row {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .bar-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.72rem;
            color: var(--text-muted);
        }

        .bar-track {
            height: 5px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 99px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            border-radius: 99px;
            background: linear-gradient(90deg, var(--purple), var(--green));
        }

        .preview-tracks {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .track-row {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.5rem 0.6rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.04);
        }

        .track-thumb {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .track-meta {
            flex: 1;
        }

        .track-name {
            font-size: 0.78rem;
            font-weight: 600;
        }

        .track-artist {
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        .track-badge-green {
            font-size: 0.65rem;
            color: var(--green);
            font-weight: 600;
        }

        /* ─── Steps section ─── */
        .section {
            padding: 6rem 1.5rem;
            max-width: 1100px;
            margin: 0 auto;
        }

        .section-label {
            text-align: center;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--purple-light);
            margin-bottom: 1rem;
        }

        .section-title {
            text-align: center;
            font-size: clamp(1.8rem, 4vw, 2.75rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 1rem;
        }

        .section-sub {
            text-align: center;
            color: var(--text-muted);
            max-width: 560px;
            margin: 0 auto 3.5rem;
            font-size: 1rem;
            line-height: 1.7;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .step-card {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 2rem 1.75rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            transition: all 0.25s;
            position: relative;
            overflow: hidden;
        }

        .step-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at top left, var(--glow-color, rgba(124, 58, 237, 0.12)), transparent 65%);
            pointer-events: none;
        }

        .step-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 255, 255, 0.22);
        }

        .step-num {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .step-icon {
            font-size: 2.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 58px;
            height: 58px;
            border-radius: 14px;
            background: var(--glass-border);
        }

        .step-h {
            font-size: 1.15rem;
            font-weight: 700;
        }

        .step-p {
            font-size: 0.875rem;
            color: var(--text-muted);
            line-height: 1.65;
        }

        /* ─── CTA section ─── */
        .cta-section {
            text-align: center;
            padding: 5rem 1.5rem 7rem;
        }

        .cta-inner {
            max-width: 640px;
            margin: 0 auto;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 3.5rem 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .cta-inner::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse at 20% 30%, rgba(124, 58, 237, 0.2), transparent 60%),
                radial-gradient(ellipse at 80% 70%, rgba(29, 185, 84, 0.15), transparent 60%);
            pointer-events: none;
        }

        .cta-inner h2 {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 0.75rem;
            position: relative;
        }

        .cta-inner p {
            color: var(--text-muted);
            margin-bottom: 2rem;
            font-size: 1rem;
            position: relative;
        }

        .cta-inner .btn-cta-spotify {
            position: relative;
        }

        /* ─── Footer ─── */
        footer {
            text-align: center;
            padding: 2rem;
            color: rgba(241, 240, 255, 0.3);
            font-size: 0.8rem;
            border-top: 1px solid var(--glass-border);
        }

        /* ─── Animations ─── */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(24px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 640px) {
            .preview-card {
                grid-template-columns: 1fr;
            }

            .cta-inner {
                padding: 2.5rem 1.5rem;
            }

            nav {
                padding: 1rem 1.25rem;
            }
        }
    </style>
</head>

<body>
    <div class="bg-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <div class="page-wrapper">
        <!-- Navbar -->
        <nav>
            <a href="/" class="brand">
                <span class="brand-icon">🎵</span>
                VibeCraft
            </a>
            <div class="nav-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-spotify">
                        Go to Dashboard →
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-outline">Sign In</a>
                    <a href="{{ route('register') }}" class="btn-spotify">
                        Get Started →
                    </a>
                @endauth
            </div>
        </nav>

        <!-- Hero -->
        <section class="hero">
            <div class="hero-badge">✦ AI-Powered Music Discovery</div>

            <h1 class="hero-title">
                Turn Your<br>
                <span class="gradient-text">Vibes Into Playlists</span>
            </h1>

            <p class="hero-sub">
                Upload any photo. Our AI reads the mood, energy, and emotion — then crafts a perfectly matched playlist
                powered by Last.fm tags &amp; Deezer previews. Instantly.
            </p>

            <div class="hero-cta">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-cta-primary">
                        ⬡ Open Dashboard
                    </a>
                @else
                    <a href="{{ route('register') }}" class="btn-cta-primary">
                        🎵 Get Started — It's Free
                    </a>
                    <a href="#how-it-works" class="btn-outline">See How It Works ↓</a>
                @endauth
            </div>

            <!-- Preview Card -->
            <div class="hero-visual">
                <div class="preview-card">
                    <!-- Upload zone mock -->
                    <div class="preview-upload-zone">
                        <div class="preview-upload-icon">📸</div>
                        <div class="preview-upload-text">
                            Drop your photo here<br>
                            <span style="color:rgba(241,240,255,0.35);font-size:0.75rem">JPG · PNG · WEBP up to
                                10MB</span>
                        </div>
                    </div>
                    <!-- Info mock -->
                    <div class="preview-info">
                        <div>
                            <div class="preview-vibe-label">Detected Vibe</div>
                            <div class="preview-vibe-text">Late-night city drive 🌃</div>
                            <div class="preview-keywords">
                                <span class="kw-badge">neon</span>
                                <span class="kw-badge">rain</span>
                                <span class="kw-badge">nostalgic</span>
                                <span class="kw-badge">deep</span>
                            </div>
                        </div>
                        <div class="preview-bar-row">
                            <div class="bar-label"><span>Energy</span><span>72%</span></div>
                            <div class="bar-track">
                                <div class="bar-fill" style="width:72%"></div>
                            </div>
                        </div>
                        <div class="preview-bar-row">
                            <div class="bar-label"><span>Valence</span><span>40%</span></div>
                            <div class="bar-track">
                                <div class="bar-fill" style="width:40%"></div>
                            </div>
                        </div>
                        <div class="preview-tracks">
                            <div class="track-row">
                                <div class="track-thumb" style="background:rgba(124,58,237,0.3)">🎵</div>
                                <div class="track-meta">
                                    <div class="track-name">Midnight City</div>
                                    <div class="track-artist">M83</div>
                                </div>
                                <div class="track-badge-green">♪ Match</div>
                            </div>
                            <div class="track-row">
                                <div class="track-thumb" style="background:rgba(29,185,84,0.2)">🎶</div>
                                <div class="track-meta">
                                    <div class="track-name">Blinding Lights</div>
                                    <div class="track-artist">The Weeknd</div>
                                </div>
                                <div class="track-badge-green">♪ Match</div>
                            </div>
                            <div class="track-row">
                                <div class="track-thumb" style="background:rgba(236,72,153,0.2)">🎼</div>
                                <div class="track-meta">
                                    <div class="track-name">Levitating</div>
                                    <div class="track-artist">Dua Lipa</div>
                                </div>
                                <div class="track-badge-green">♪ Match</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section class="section" id="how-it-works">
            <div class="section-label">Simple Process</div>
            <h2 class="section-title">Three steps to your perfect playlist</h2>
            <p class="section-sub">No music theory knowledge needed. Just a photo and your imagination.</p>

            <div class="steps-grid">
                <div class="step-card" style="--glow-color: rgba(124,58,237,0.15)">
                    <div class="step-num">Step 01</div>
                    <div class="step-icon">📸</div>
                    <div class="step-h">Upload a Photo</div>
                    <p class="step-p">Share any image — a sunset, a city street, your mood board, a concert. Any vibe
                        works.</p>
                </div>
                <div class="step-card" style="--glow-color: rgba(236,72,153,0.12)">
                    <div class="step-num">Step 02</div>
                    <div class="step-icon">🤖</div>
                    <div class="step-h">AI Reads the Vibe</div>
                    <p class="step-p">Our vision model extracts mood, energy, and emotion — mapping it to musical
                        attributes in real time.</p>
                </div>
                <div class="step-card" style="--glow-color: rgba(29,185,84,0.12)">
                    <div class="step-num">Step 03</div>
                    <div class="step-icon">🎧</div>
                    <div class="step-h">Dynamic Playlist Generated</div>
                    <p class="step-p">A curated playlist is generated via Deezer, complete with high-quality audio
                        previews
                        ready to listen immediately.</p>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="cta-section">
            <div class="cta-inner">
                <h2>Ready to hear your vibe? 🎵</h2>
                <p>Create an account for free and generate your first playlist in under 30 seconds.</p>
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-cta-primary" style="font-size:1rem;padding:0.9rem 2rem">
                        Go to Dashboard →
                    </a>
                @else
                    <a href="{{ route('register') }}" class="btn-cta-spotify">
                        Get Started — It's Free
                    </a>
                @endauth
            </div>
        </section>

        <footer>
            <p>© 2026 VibeCraft · Built with Laravel + AI · <a href="{{ route('register') }}"
                    style="color:var(--green);text-decoration:none;">Join Now</a></p>
        </footer>
    </div>
</body>

</html>