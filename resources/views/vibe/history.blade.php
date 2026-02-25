@extends('layouts.app')

@section('title', 'Your Vibe History')

@section('content')
    <style>
        /* ─── Page header ─── */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .page-header-left h1 {
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 0.2rem;
        }

        .page-header-left p {
            color: rgba(241, 240, 255, 0.5);
            font-size: 0.875rem;
        }

        .btn-new-vibe {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.65rem 1.35rem;
            border-radius: 10px;
            background: linear-gradient(135deg, #7C3AED, #5B21B6);
            color: #fff;
            font-weight: 700;
            font-size: 0.875rem;
            font-family: inherit;
            border: none;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 0 24px rgba(124, 58, 237, 0.35);
        }

        .btn-new-vibe:hover {
            transform: translateY(-1px);
            box-shadow: 0 0 40px rgba(124, 58, 237, 0.5);
        }

        /* ─── History grid ─── */
        .history-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .history-card {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
            transition: all 0.25s;
            position: relative;
        }

        .history-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }

        .card-thumb {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
            background: rgba(124, 58, 237, 0.15);
            flex-shrink: 0;
        }

        .card-thumb-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.2), rgba(29, 185, 84, 0.1));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }

        /* playlist badge */
        .playlist-badge {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.6rem;
            border-radius: 99px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .playlist-badge.has-playlist {
            background: rgba(29, 185, 84, 0.9);
            color: #000;
        }

        .playlist-badge.no-playlist {
            background: rgba(10, 10, 26, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: rgba(241, 240, 255, 0.5);
        }

        .card-body {
            padding: 1.15rem 1.25rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .card-caption {
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-keywords {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
        }

        .kw-chip {
            padding: 0.18rem 0.55rem;
            border-radius: 99px;
            font-size: 0.68rem;
            font-weight: 600;
            background: rgba(124, 58, 237, 0.15);
            border: 1px solid rgba(124, 58, 237, 0.3);
            color: #A78BFA;
        }

        .card-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 0.75rem;
            border-top: 1px solid rgba(255, 255, 255, 0.07);
        }

        .card-date {
            font-size: 0.75rem;
            color: rgba(241, 240, 255, 0.35);
        }

        .card-attrs {
            display: flex;
            gap: 0.75rem;
        }

        .attr-pill {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.72rem;
            color: rgba(241, 240, 255, 0.4);
        }

        .attr-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        /* ─── Empty state ─── */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 5rem 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .empty-icon {
            font-size: 4rem;
            opacity: 0.25;
        }

        .empty-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: rgba(241, 240, 255, 0.5);
        }

        .empty-sub {
            font-size: 0.875rem;
            color: rgba(241, 240, 255, 0.3);
            max-width: 340px;
        }

        /* ─── Pagination ─── */
        .pagination-wrap {
            display: flex;
            justify-content: center;
        }

        .pagination-wrap nav {
            display: flex;
            gap: 0.4rem;
            align-items: center;
        }

        .pagination-wrap a,
        .pagination-wrap span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 0.6rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
        }

        .pagination-wrap a {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(241, 240, 255, 0.7);
        }

        .pagination-wrap a:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
        }

        .pagination-wrap span[aria-current] {
            background: linear-gradient(135deg, #7C3AED, #5B21B6);
            border: 1px solid rgba(124, 58, 237, 0.5);
            color: #fff;
            font-weight: 700;
        }

        .pagination-wrap span:not([aria-current]) {
            color: rgba(241, 240, 255, 0.25);
        }
    </style>

    <div class="page-header">
        <div class="page-header-left">
            <h1>🕓 Vibe History</h1>
            <p>{{ $sessions->total() }} session{{ $sessions->total() !== 1 ? 's' : '' }} created</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn-new-vibe">+ New Vibe</a>
    </div>

    <div class="history-grid">
        @forelse($sessions as $session)
            <a href="{{ route('vibe.result', $session->id) }}" class="history-card">
                @if($session->image_path)
                    <img src="{{ Storage::url($session->image_path) }}" alt="vibe thumbnail" class="card-thumb">
                @else
                    <div class="card-thumb-placeholder">🎨</div>
                @endif

                @if($session->playlist_url)
                    <div class="playlist-badge has-playlist">♪ Playlist Saved</div>
                @else
                    <div class="playlist-badge no-playlist">No playlist yet</div>
                @endif

                <div class="card-body">
                    <div class="card-caption">
                        {{ $session->caption ?? 'Untitled Vibe #' . $session->id }}
                    </div>

                    @if($session->keywords && count($session->keywords) > 0)
                        <div class="card-keywords">
                            @foreach(array_slice($session->keywords, 0, 4) as $kw)
                                <span class="kw-chip">{{ $kw }}</span>
                            @endforeach
                            @if(count($session->keywords) > 4)
                                <span class="kw-chip"
                                    style="background:rgba(255,255,255,0.06);border-color:rgba(255,255,255,0.1);color:rgba(241,240,255,0.4)">
                                    +{{ count($session->keywords) - 4 }}
                                </span>
                            @endif
                        </div>
                    @endif

                    <div class="card-meta">
                        <div class="card-date">{{ $session->created_at->format('M d, Y') }}</div>
                        <div class="card-attrs">
                            <div class="attr-pill">
                                <div class="attr-dot" style="background:linear-gradient(135deg,#7C3AED,#EC4899)"></div>
                                {{ round($session->energy * 100) }}% energy
                            </div>
                            <div class="attr-pill">
                                <div class="attr-dot" style="background:linear-gradient(135deg,#1DB954,#4ade80)"></div>
                                {{ round($session->valence * 100) }}% happy
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <div class="empty-state">
                <div class="empty-icon">🎵</div>
                <div class="empty-title">No vibe sessions yet</div>
                <div class="empty-sub">Upload your first photo on the dashboard to get started — your history will appear here.
                </div>
                <a href="{{ route('dashboard') }}" class="btn-new-vibe" style="margin-top:0.5rem">+ Create Your First Vibe</a>
            </div>
        @endforelse
    </div>

    @if($sessions->hasPages())
        <div class="pagination-wrap">
            {{ $sessions->links() }}
        </div>
    @endif
@endsection