<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Vibe Result — VibeCraft</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
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
            --purple: #7C3AED;
            --purple-light: #A78BFA;
            --blue: #3B82F6;
            --bg: #0a0a1a;
            --glass: rgba(255, 255, 255, 0.06);
            --glass-border: rgba(255, 255, 255, 0.11);
            --text: #F1F0FF;
            --muted: rgba(241, 240, 255, 0.55);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* Orbs */
        .bg-orbs {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            opacity: 0.35;
        }

        .orb-1 {
            width: 600px;
            height: 600px;
            background: var(--purple);
            top: -200px;
            left: -150px;
        }

        .orb-2 {
            width: 400px;
            height: 400px;
            background: #1e40af;
            bottom: -150px;
            right: -100px;
        }

        /* Nav */
        nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.1rem 2rem;
            background: rgba(10, 10, 26, 0.75);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 800;
            font-size: 1.1rem;
            text-decoration: none;
            color: var(--text);
        }

        .brand-icon {
            background: linear-gradient(135deg, var(--purple), var(--blue));
            border-radius: 8px;
            padding: 5px 8px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-sm {
            padding: 0.45rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--glass-border);
            background: var(--glass);
            color: var(--text);
            font-size: 0.82rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            font-family: inherit;
            cursor: pointer;
        }

        .btn-sm:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Main layout */
        main {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem 5rem;
            position: relative;
            z-index: 1;
        }

        /* Page header */
        .page-header {
            margin-bottom: 2.5rem;
        }

        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .page-header p {
            color: var(--muted);
            margin-top: 0.35rem;
            font-size: 0.9rem;
        }

        /* Alert */
        .alert-success {
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            color: #86efac;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            color: #fca5a5;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        /* Grid */
        .result-grid {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 2rem;
        }

        @media (max-width: 800px) {
            .result-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Sidebar */
        .sidebar-card {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 80px;
        }

        .vibe-image {
            width: 100%;
            aspect-ratio: 1/1;
            object-fit: cover;
            border-radius: 14px;
            display: block;
            margin-bottom: 1.25rem;
        }

        .vibe-image-placeholder {
            width: 100%;
            aspect-ratio: 1/1;
            border-radius: 14px;
            background: rgba(124, 58, 237, 0.08);
            border: 2px dashed rgba(124, 58, 237, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin-bottom: 1.25rem;
        }

        .caption-text {
            font-size: 0.95rem;
            font-weight: 600;
            line-height: 1.5;
            margin-bottom: 1rem;
            color: var(--text);
        }

        .section-label {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--purple-light);
            margin-bottom: 0.6rem;
        }

        /* Mood tags */
        .mood-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-bottom: 1.5rem;
        }

        .mood-tag {
            padding: 0.3rem 0.75rem;
            border-radius: 99px;
            font-size: 0.78rem;
            font-weight: 600;
            background: rgba(124, 58, 237, 0.15);
            border: 1px solid rgba(124, 58, 237, 0.35);
            color: var(--purple-light);
        }

        .mood-tag:nth-child(2) {
            background: rgba(59, 130, 246, 0.15);
            border-color: rgba(59, 130, 246, 0.35);
            color: #93c5fd;
        }

        .mood-tag:nth-child(3) {
            background: rgba(236, 72, 153, 0.15);
            border-color: rgba(236, 72, 153, 0.35);
            color: #f9a8d4;
        }

        .mood-tag:nth-child(4) {
            background: rgba(34, 197, 94, 0.15);
            border-color: rgba(34, 197, 94, 0.35);
            color: #86efac;
        }

        /* Feature bars */
        .feature-bars {
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
            margin-bottom: 1.5rem;
        }

        .bar-row {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .bar-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: var(--muted);
        }

        .bar-track {
            height: 5px;
            background: rgba(255, 255, 255, 0.07);
            border-radius: 99px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            border-radius: 99px;
            background: linear-gradient(90deg, var(--purple), var(--blue));
        }

        /* Keywords */
        .keywords {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            margin-bottom: 1.5rem;
        }

        .kw {
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--muted);
        }

        /* Save playlist btn */
        .btn-save-playlist {
            width: 100%;
            padding: 0.8rem;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--purple), var(--blue));
            border: none;
            color: #fff;
            font-size: 0.9rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.2s;
        }

        .btn-save-playlist:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Tracks section */
        .tracks-section h2 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        .tracks-section .sub {
            color: var(--muted);
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }

        /* Source badges */
        .source-badges {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .source-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.75rem;
            border-radius: 99px;
            font-size: 0.72rem;
            font-weight: 600;
            border: 1px solid;
        }

        .badge-lastfm {
            background: rgba(188, 0, 0, 0.12);
            border-color: rgba(188, 0, 0, 0.35);
            color: #fca5a5;
        }

        .badge-deezer {
            background: rgba(105, 36, 255, 0.12);
            border-color: rgba(105, 36, 255, 0.35);
            color: #c4b5fd;
        }

        /* Track list */
        .track-list {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }

        .track-card {
            display: grid;
            grid-template-columns: 52px 1fr auto;
            align-items: center;
            gap: 0.85rem;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            padding: 0.7rem 1rem;
            transition: all 0.2s;
        }

        .track-card:hover {
            background: rgba(255, 255, 255, 0.09);
            border-color: rgba(255, 255, 255, 0.18);
        }

        .track-artwork {
            width: 52px;
            height: 52px;
            border-radius: 10px;
            object-fit: cover;
            background: rgba(124, 58, 237, 0.2);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .track-artwork img {
            width: 52px;
            height: 52px;
            border-radius: 10px;
            object-fit: cover;
            display: block;
        }

        .track-info {
            overflow: hidden;
        }

        .track-title {
            font-size: 0.88rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .track-artist {
            font-size: 0.78rem;
            color: var(--muted);
            margin-top: 0.15rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .track-album {
            font-size: 0.68rem;
            color: rgba(241, 240, 255, 0.35);
            margin-top: 0.1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .track-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        /* Audio player pill */
        .audio-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.7rem;
            background: rgba(124, 58, 237, 0.18);
            border: 1px solid rgba(124, 58, 237, 0.35);
            border-radius: 99px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--purple-light);
            border: none;
            font-family: 'Inter', sans-serif;
        }

        .audio-pill:hover {
            background: rgba(124, 58, 237, 0.3);
        }

        .audio-pill.playing {
            background: rgba(34, 197, 94, 0.2);
            color: #86efac;
            border: 1px solid rgba(34, 197, 94, 0.35);
        }

        .audio-pill-no-preview {
            padding: 0.35rem 0.7rem;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 99px;
            font-size: 0.7rem;
            color: rgba(241, 240, 255, 0.25);
        }

        .deezer-link {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.7rem;
            background: rgba(105, 36, 255, 0.12);
            border: 1px solid rgba(105, 36, 255, 0.3);
            border-radius: 99px;
            font-size: 0.72rem;
            font-weight: 600;
            color: #c4b5fd;
            text-decoration: none;
            transition: all 0.2s;
        }

        .deezer-link:hover {
            background: rgba(105, 36, 255, 0.25);
        }

        /* No tracks state */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            color: var(--muted);
        }

        .empty-state .emoji {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 0.75rem;
        }
    </style>
</head>

<body>
    <div class="bg-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>

    <nav>
        <a href="/" class="brand">
            <span class="brand-icon">🎵</span> VibeCraft
        </a>
        <div class="nav-links">
            <a href="{{ route('dashboard') }}" class="btn-sm">Dashboard</a>
            <a href="{{ route('vibe.history') }}" class="btn-sm">History</a>
            <form action="{{ route('logout') }}" method="POST" style="display:inline">
                @csrf
                <button type="submit" class="btn-sm">Logout</button>
            </form>
        </div>
    </nav>

    <main>
        {{-- Alerts --}}
        @if(session('success'))
            <div class="alert-success">✓ {{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert-error">{{ $errors->first() }}</div>
        @endif

        <div class="page-header">
            <h1>Your Vibe Result ✨</h1>
            <p>AI detected the mood — here's your personalized playlist</p>
        </div>

        <div class="result-grid">

            {{-- ── Left sidebar: Image + Analysis ── --}}
            <aside class="sidebar-card">
                @if($session->image_path)
                    <img src="{{ Storage::url($session->image_path) }}" alt="Your vibe image" class="vibe-image">
                @else
                    <div class="vibe-image-placeholder">🖼️</div>
                @endif

                @if($session->caption)
                    <p class="caption-text">"{{ $session->caption }}"</p>
                @endif

                {{-- Mood Tags from Last.fm --}}
                @if(!empty($moodTags))
                    <div class="section-label">🔗 Last.fm Mood Tags</div>
                    <div class="mood-tags">
                        @foreach($moodTags as $tag)
                            <span class="mood-tag"># {{ $tag }}</span>
                        @endforeach
                    </div>
                @endif

                {{-- Audio feature bars --}}
                <div class="section-label">🤖 AI Analysis</div>
                <div class="feature-bars">
                    <div class="bar-row">
                        <div class="bar-info"><span>Energy</span><span>{{ round($session->energy * 100) }}%</span></div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width:{{ round($session->energy * 100) }}%"></div>
                        </div>
                    </div>
                    <div class="bar-row">
                        <div class="bar-info"><span>Valence</span><span>{{ round($session->valence * 100) }}%</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width:{{ round($session->valence * 100) }}%"></div>
                        </div>
                    </div>
                    <div class="bar-row">
                        <div class="bar-info">
                            <span>Acousticness</span><span>{{ round(($session->acousticness ?? 0.5) * 100) }}%</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width:{{ round(($session->acousticness ?? 0.5) * 100) }}%">
                            </div>
                        </div>
                    </div>
                    <div class="bar-row">
                        <div class="bar-info"><span>Tempo</span><span>{{ round($session->tempo ?? 120) }} BPM</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill"
                                style="width:{{ min(100, round((($session->tempo ?? 120) - 40) / 1.8)) }}%"></div>
                        </div>
                    </div>
                </div>

                {{-- Keywords --}}
                @if(!empty($session->keywords))
                    <div class="section-label">Keywords</div>
                    <div class="keywords">
                        @foreach($session->keywords as $kw)
                            <span class="kw">{{ $kw }}</span>
                        @endforeach
                    </div>
                @endif

                {{-- Save Playlist --}}
                @if(!$session->playlist_name)
                    <form action="{{ route('vibe.create-playlist', $session->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-save-playlist">💾 Save This Playlist</button>
                    </form>
                @else
                    <div
                        style="text-align:center; color:#86efac; font-size:0.85rem; font-weight:600; padding:0.75rem; background:rgba(34,197,94,0.08); border:1px solid rgba(34,197,94,0.25); border-radius:10px;">
                        ✓ Saved as "{{ $session->playlist_name }}"
                    </div>
                @endif
            </aside>

            {{-- ── Right: Track playlist ── --}}
            <section class="tracks-section">
                <h2>🎶 Your Playlist</h2>
                <p class="sub">Discovered via Last.fm tags · Enriched with Deezer previews</p>

                <div class="source-badges">
                    <span class="source-badge badge-lastfm">🎵 Last.fm Tags</span>
                    <span class="source-badge badge-deezer">🎧 Deezer Previews</span>
                </div>

                @if(!empty($tracks))
                    <div class="track-list">
                        @foreach($tracks as $i => $track)
                            <div class="track-card" id="track-{{ $i }}">

                                {{-- Artwork --}}
                                <div class="track-artwork">
                                    @if($track['artwork'])
                                        <img src="{{ $track['artwork'] }}" alt="{{ $track['title'] }}" loading="lazy">
                                    @else
                                        🎵
                                    @endif
                                </div>

                                {{-- Info --}}
                                <div class="track-info">
                                    <div class="track-title">{{ $track['title'] }}</div>
                                    <div class="track-artist">{{ $track['artist'] }}</div>
                                    @if($track['album'])
                                        <div class="track-album">{{ $track['album'] }}</div>
                                    @endif
                                </div>

                                {{-- Actions --}}
                                <div class="track-actions">
                                    @if($track['preview'])
                                        <button class="audio-pill" data-preview="{{ $track['preview'] }}" data-index="{{ $i }}"
                                            onclick="togglePlay(this)" title="Preview 30s">▶ Preview</button>
                                    @else
                                        <span class="audio-pill-no-preview">No preview</span>
                                    @endif

                                    @if($track['deezer_url'])
                                        <a href="{{ $track['deezer_url'] }}" target="_blank" rel="noopener" class="deezer-link"
                                            title="Open on Deezer">
                                            🔗
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <span class="emoji">🎵</span>
                        <p>No tracks found for this vibe yet.</p>
                        <p style="font-size:0.8rem; margin-top:0.5rem;">Try uploading a different image.</p>
                    </div>
                @endif
            </section>
        </div>
    </main>

    {{-- Audio player logic --}}
    <script>
        let currentAudio = null;
        let currentBtn = null;

        function togglePlay(btn) {
            const previewUrl = btn.dataset.preview;

            // Stop any currently playing track
            if (currentAudio) {
                currentAudio.pause();
                currentAudio = null;
                if (currentBtn) {
                    currentBtn.textContent = '▶ Preview';
                    currentBtn.classList.remove('playing');
                }
                if (currentBtn === btn) {
                    currentBtn = null;
                    return; // Clicking same button stops it
                }
            }

            // Start new playback
            currentAudio = new Audio(previewUrl);
            currentBtn = btn;
            btn.textContent = '⏸ Playing';
            btn.classList.add('playing');

            currentAudio.play().catch(err => {
                console.warn('Audio play failed:', err);
                btn.textContent = '▶ Preview';
                btn.classList.remove('playing');
            });

            currentAudio.addEventListener('ended', () => {
                btn.textContent = '▶ Preview';
                btn.classList.remove('playing');
                currentAudio = null;
                currentBtn = null;
            });
        }
    </script>
</body>

</html>