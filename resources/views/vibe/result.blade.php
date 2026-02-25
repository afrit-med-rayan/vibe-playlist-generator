@extends('layouts.app')

@section('title', 'Your Vibe Results')

@section('content')
    <style>
        /* ─── Back nav ─── */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: rgba(241, 240, 255, 0.5);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 2rem;
            transition: color 0.2s;
        }

        .back-btn:hover {
            color: rgba(241, 240, 255, 0.9);
        }

        /* ─── Top grid ─── */
        .result-top {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 1.75rem;
            margin-bottom: 1.75rem;
            align-items: start;
        }

        /* ─── Image card ─── */
        .image-card {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            overflow: hidden;
        }

        .vibe-image {
            width: 100%;
            height: 280px;
            object-fit: cover;
            display: block;
        }

        .image-card-body {
            padding: 1.25rem;
        }

        .playlist-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .playlist-status.created {
            color: #4ade80;
        }

        .playlist-status.pending {
            color: rgba(241, 240, 255, 0.5);
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
        }

        /* ─── Vibe info card ─── */
        .vibe-info-card {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            padding: 1.75rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .vibe-caption {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.35;
        }

        .section-label-sm {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(241, 240, 255, 0.4);
            margin-bottom: 0.6rem;
        }

        .keywords-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }

        .kw-badge {
            padding: 0.25rem 0.7rem;
            border-radius: 99px;
            font-size: 0.78rem;
            font-weight: 600;
            background: rgba(124, 58, 237, 0.18);
            border: 1px solid rgba(124, 58, 237, 0.35);
            color: #A78BFA;
        }

        /* ─── Bars ─── */
        .bars-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .bar-row {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .bar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .bar-name {
            font-size: 0.8rem;
            font-weight: 600;
            color: rgba(241, 240, 255, 0.7);
        }

        .bar-val {
            font-size: 0.8rem;
            font-weight: 700;
            color: rgba(241, 240, 255, 0.9);
        }

        .bar-track {
            height: 6px;
            background: rgba(255, 255, 255, 0.07);
            border-radius: 99px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            border-radius: 99px;
            transition: width 1s cubic-bezier(.22, .61, .36, 1);
        }

        .bar-fill-energy {
            background: linear-gradient(90deg, #7C3AED, #EC4899);
        }

        .bar-fill-valence {
            background: linear-gradient(90deg, #1DB954, #4ade80);
        }

        .bar-fill-tempo {
            background: linear-gradient(90deg, #F97316, #FBBF24);
        }

        .bar-fill-acousticness {
            background: linear-gradient(90deg, #3B82F6, #67E8F9);
        }

        /* ─── Playlist section ─── */
        .playlist-section {
            margin-bottom: 1.75rem;
        }

        .section-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .section-row h2 {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .btn-create-playlist {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.35rem;
            border-radius: 10px;
            background: #1DB954;
            color: #000;
            font-weight: 700;
            font-size: 0.875rem;
            font-family: inherit;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 0 24px rgba(29, 185, 84, 0.3);
        }

        .btn-create-playlist:hover {
            transform: translateY(-1px);
            box-shadow: 0 0 40px rgba(29, 185, 84, 0.5);
            background: #20d65f;
        }

        .btn-open-spotify {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.35rem;
            border-radius: 10px;
            background: rgba(29, 185, 84, 0.15);
            border: 1px solid rgba(29, 185, 84, 0.4);
            color: #4ade80;
            font-weight: 700;
            font-size: 0.875rem;
            font-family: inherit;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-open-spotify:hover {
            background: rgba(29, 185, 84, 0.25);
        }

        /* ─── Tracks grid ─── */
        .tracks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 0.85rem;
        }

        .track-card {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
        }

        .track-card:hover {
            background: rgba(255, 255, 255, 0.09);
            border-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-1px);
        }

        .track-number {
            font-size: 0.72rem;
            font-weight: 700;
            color: rgba(241, 240, 255, 0.25);
            width: 20px;
            text-align: right;
            flex-shrink: 0;
        }

        .track-cover {
            width: 42px;
            height: 42px;
            border-radius: 6px;
            object-fit: cover;
            flex-shrink: 0;
            background: rgba(124, 58, 237, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            overflow: hidden;
        }

        .track-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .track-info {
            flex: 1;
            min-width: 0;
        }

        .track-name {
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: rgba(241, 240, 255, 0.95);
        }

        .track-artist {
            font-size: 0.75rem;
            color: rgba(241, 240, 255, 0.45);
            margin-top: 0.1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .track-duration {
            font-size: 0.72rem;
            color: rgba(241, 240, 255, 0.35);
            flex-shrink: 0;
        }

        /* ─── No tracks empty ─── */
        .no-tracks {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            color: rgba(241, 240, 255, 0.3);
        }

        .no-tracks .no-tracks-icon {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
        }

        @media (max-width: 860px) {
            .result-top {
                grid-template-columns: 1fr;
            }

            .bars-grid {
                grid-template-columns: 1fr;
            }

            .tracks-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <a href="{{ route('dashboard') }}" class="back-btn">← Back to Dashboard</a>

    <!-- Top: Image + Vibe Info -->
    <div class="result-top">
        <!-- Image -->
        <div class="image-card">
            @if($session->image_path)
                <img src="{{ Storage::url($session->image_path) }}" alt="Vibe image" class="vibe-image">
            @else
                <div class="vibe-image"
                    style="display:flex;align-items:center;justify-content:center;background:rgba(124,58,237,0.15);font-size:3rem;">
                    🎨</div>
            @endif

            <div class="image-card-body">
                @if($session->playlist_url)
                    <div class="playlist-status created">
                        <span class="status-dot"></span> Playlist Created on Spotify
                    </div>
                @else
                    <div class="playlist-status pending">
                        <span class="status-dot" style="background:rgba(241,240,255,0.3)"></span> No playlist yet
                    </div>
                @endif
            </div>
        </div>

        <!-- Vibe info -->
        <div class="vibe-info-card">
            <div>
                <div class="section-label-sm">Detected Vibe</div>
                <div class="vibe-caption">{{ $session->caption ?? 'Your unique vibe ✨' }}</div>
            </div>

            @if($session->keywords && count($session->keywords) > 0)
                <div>
                    <div class="section-label-sm">Keywords</div>
                    <div class="keywords-wrap">
                        @foreach($session->keywords as $kw)
                            <span class="kw-badge">{{ $kw }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <div class="section-label-sm">Audio Attributes</div>
                <div class="bars-grid">
                    <div class="bar-row">
                        <div class="bar-header">
                            <span class="bar-name">Energy</span>
                            <span class="bar-val">{{ round($session->energy * 100) }}%</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-energy" style="width: {{ round($session->energy * 100) }}%"></div>
                        </div>
                    </div>
                    <div class="bar-row">
                        <div class="bar-header">
                            <span class="bar-name">Valence</span>
                            <span class="bar-val">{{ round($session->valence * 100) }}%</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-valence" style="width: {{ round($session->valence * 100) }}%">
                            </div>
                        </div>
                    </div>
                    <div class="bar-row">
                        <div class="bar-header">
                            <span class="bar-name">Tempo</span>
                            <span class="bar-val">{{ round($session->tempo) }} BPM</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-tempo"
                                style="width: {{ min(100, round($session->tempo / 200 * 100)) }}%"></div>
                        </div>
                    </div>
                    <div class="bar-row">
                        <div class="bar-header">
                            <span class="bar-name">Acoustic</span>
                            <span class="bar-val">{{ round(($session->acousticness ?? 0.5) * 100) }}%</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill bar-fill-acousticness"
                                style="width: {{ round(($session->acousticness ?? 0.5) * 100) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: auto">
                @if($session->playlist_url)
                    <a href="{{ $session->playlist_url }}" target="_blank" class="btn-open-spotify">
                        <svg style="width:16px;height:16px;fill:currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z" />
                        </svg>
                        Open "{{ $session->playlist_name }}" on Spotify
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Tracks Section -->
    <div class="playlist-section">
        <div class="section-row">
            <h2>🎵 Recommended Tracks</h2>
            @if(!$session->playlist_url)
                <form method="POST" action="{{ route('vibe.create-playlist', $session->id) }}">
                    @csrf
                    <button type="submit" class="btn-create-playlist">
                        <svg style="width:15px;height:15px;fill:currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z" />
                        </svg>
                        Save as Spotify Playlist
                    </button>
                </form>
            @endif
        </div>

        <div class="tracks-grid">
            @forelse($tracks as $i => $track)
                <a href="{{ $track['external_urls']['spotify'] ?? '#' }}" target="_blank" class="track-card">
                    <div class="track-number">{{ $i + 1 }}</div>
                    <div class="track-cover">
                        @if(!empty($track['album']['images'][0]['url']))
                            <img src="{{ $track['album']['images'][0]['url'] }}" alt="cover">
                        @else
                            🎵
                        @endif
                    </div>
                    <div class="track-info">
                        <div class="track-name">{{ $track['name'] ?? 'Unknown Track' }}</div>
                        <div class="track-artist">{{ collect($track['artists'] ?? [])->pluck('name')->join(', ') }}</div>
                    </div>
                    @php
                        $ms = $track['duration_ms'] ?? 0;
                        $mins = floor($ms / 60000);
                        $secs = str_pad(floor(($ms % 60000) / 1000), 2, '0', STR_PAD_LEFT);
                    @endphp
                    @if($ms > 0)
                        <div class="track-duration">{{ $mins }}:{{ $secs }}</div>
                    @endif
                </a>
            @empty
                <div class="no-tracks">
                    <div class="no-tracks-icon">🎶</div>
                    <p>No recommendations yet.<br>
                        <span style="font-size:0.82rem;color:rgba(241,240,255,0.25)">Spotify recommendations require a connected
                            account.</span>
                    </p>
                </div>
            @endforelse
        </div>
    </div>
@endsection