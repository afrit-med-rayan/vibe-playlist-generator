@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 1.75rem;
            align-items: start;
        }

        /* ─── Upload Card ─── */
        .upload-card {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            padding: 2rem;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.35rem;
            letter-spacing: -0.02em;
        }

        .card-subtitle {
            font-size: 0.875rem;
            color: rgba(241, 240, 255, 0.55);
            margin-bottom: 1.75rem;
        }

        /* ─── Drop zone ─── */
        .dropzone {
            border: 2px dashed rgba(124, 58, 237, 0.45);
            border-radius: 14px;
            background: rgba(124, 58, 237, 0.05);
            padding: 3rem 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: all 0.25s;
            position: relative;
            overflow: hidden;
            text-align: center;
            min-height: 280px;
        }

        .dropzone:hover,
        .dropzone.drag-over {
            border-color: rgba(124, 58, 237, 0.8);
            background: rgba(124, 58, 237, 0.1);
            transform: scale(1.005);
        }

        .dropzone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .dropzone-icon {
            font-size: 3rem;
            transition: transform 0.3s;
        }

        .dropzone:hover .dropzone-icon {
            transform: scale(1.1) rotate(-5deg);
        }

        .dropzone-title {
            font-size: 1rem;
            font-weight: 600;
            color: rgba(241, 240, 255, 0.9);
        }

        .dropzone-hint {
            font-size: 0.8rem;
            color: rgba(241, 240, 255, 0.4);
        }

        .dropzone-or {
            font-size: 0.78rem;
            color: rgba(241, 240, 255, 0.35);
            position: relative;
        }

        .dropzone-or::before,
        .dropzone-or::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40px;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }

        .dropzone-or::before {
            right: calc(100% + 10px);
        }

        .dropzone-or::after {
            left: calc(100% + 10px);
        }

        .dropzone-btn {
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            background: rgba(124, 58, 237, 0.2);
            border: 1px solid rgba(124, 58, 237, 0.4);
            color: #A78BFA;
            font-size: 0.85rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            pointer-events: none;
            transition: all 0.2s;
        }

        /* ─── Image preview ─── */
        .image-preview-wrap {
            display: none;
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            max-height: 280px;
        }

        .image-preview-wrap img {
            width: 100%;
            height: 280px;
            object-fit: cover;
            display: block;
        }

        .image-preview-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(10, 10, 26, 0.8) 0%, transparent 50%);
            display: flex;
            align-items: flex-end;
            padding: 1rem 1.25rem;
        }

        .preview-change-btn {
            font-size: 0.78rem;
            color: rgba(241, 240, 255, 0.7);
            background: rgba(10, 10, 26, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 6px;
            padding: 0.35rem 0.75rem;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
        }

        .preview-change-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        /* ─── Vibe params hint ─── */
        .vibe-params {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 1.25rem;
        }

        .param-chip {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 0.75rem;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.07);
            font-size: 0.78rem;
            color: rgba(241, 240, 255, 0.6);
        }

        .param-chip .chip-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7C3AED, #1DB954);
            flex-shrink: 0;
        }

        /* ─── Submit button ─── */
        .btn-analyze {
            width: 100%;
            margin-top: 1.5rem;
            padding: 0.95rem;
            border-radius: 12px;
            background: linear-gradient(135deg, #7C3AED, #5B21B6);
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            font-family: inherit;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.25s;
            box-shadow: 0 0 30px rgba(124, 58, 237, 0.35);
        }

        .btn-analyze:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 0 50px rgba(124, 58, 237, 0.5);
        }

        .btn-analyze:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-analyze .spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ─── Sidebar ─── */
        .sidebar-card {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            padding: 1.5rem;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .sidebar-title {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: rgba(241, 240, 255, 0.5);
            margin-bottom: 1.25rem;
        }

        .session-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .session-item {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            padding: 0.75rem;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.07);
            text-decoration: none;
            color: inherit;
            transition: all 0.2s;
        }

        .session-item:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.15);
            transform: translateX(2px);
        }

        .session-thumb {
            width: 46px;
            height: 46px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
            background: rgba(124, 58, 237, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            overflow: hidden;
        }

        .session-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .session-meta {
            flex: 1;
            min-width: 0;
        }

        .session-caption {
            font-size: 0.82rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: rgba(241, 240, 255, 0.9);
        }

        .session-date {
            font-size: 0.72rem;
            color: rgba(241, 240, 255, 0.4);
            margin-top: 0.15rem;
        }

        .session-arrow {
            color: rgba(241, 240, 255, 0.2);
            font-size: 0.85rem;
        }

        .session-item:hover .session-arrow {
            color: rgba(241, 240, 255, 0.6);
        }

        .empty-sessions {
            text-align: center;
            padding: 2rem 1rem;
            color: rgba(241, 240, 255, 0.35);
            font-size: 0.875rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .empty-sessions .empty-icon {
            font-size: 2rem;
            opacity: 0.4;
        }

        .sidebar-footer {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.07);
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            color: rgba(241, 240, 255, 0.4);
            text-decoration: none;
            transition: color 0.2s;
        }

        .sidebar-footer a:hover {
            color: rgba(241, 240, 255, 0.8);
        }

        /* ─── Welcome banner ─── */
        .welcome-banner {
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.15), rgba(29, 185, 84, 0.1));
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .welcome-text h2 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }

        .welcome-text p {
            font-size: 0.85rem;
            color: rgba(241, 240, 255, 0.55);
        }

        .spotify-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.85rem;
            border-radius: 99px;
            background: rgba(29, 185, 84, 0.15);
            border: 1px solid rgba(29, 185, 84, 0.3);
            color: #4ade80;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }

        @media (max-width: 900px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="welcome-banner">
        <div class="welcome-text">
            <h2>Hey, {{ Auth::user()->name ?? 'there' }} 👋</h2>
            <p>Upload a photo below. AI detects the mood &rarr; Last.fm tags &rarr; Deezer playlist with 30s previews.</p>
        </div>
        <span class="spotify-chip"
            style="background:rgba(124,58,237,0.15);border-color:rgba(124,58,237,0.35);color:#a78bfa;">
            🎧 Last.fm + Deezer
        </span>
    </div>

    <div class="dashboard-grid">
        <!-- Upload card -->
        <div class="upload-card">
            <div class="card-title">Analyze Your Vibe</div>
            <p class="card-subtitle">Drop any image — a scene, mood board, or moment — and get a matching playlist.</p>

            <form id="vibeForm" method="POST" action="{{ route('vibe.analyze') }}" enctype="multipart/form-data">
                @csrf

                <!-- Image preview (shown after selection) -->
                <div class="image-preview-wrap" id="previewWrap">
                    <img id="previewImg" src="" alt="Preview">
                    <div class="image-preview-overlay">
                        <button type="button" class="preview-change-btn" onclick="resetUpload()">↩ Change Image</button>
                    </div>
                </div>

                <!-- Drop zone (hidden after selection) -->
                <div class="dropzone" id="dropzone">
                    <input type="file" name="image" id="imageInput" accept="image/*">
                    <div class="dropzone-icon">🎨</div>
                    <div class="dropzone-title">Drop your photo here</div>
                    <div class="dropzone-or">or</div>
                    <div class="dropzone-btn">Browse Files</div>
                    <div class="dropzone-hint">JPG · PNG · WEBP · up to 10MB</div>
                </div>

                <div class="vibe-params">
                    <div class="param-chip"><span class="chip-dot"></span> Energy detected</div>
                    <div class="param-chip"><span class="chip-dot"></span> Valence analyzed</div>
                    <div class="param-chip"><span class="chip-dot"></span> Tempo mapped</div>
                    <div class="param-chip"><span class="chip-dot"></span> Acousticness scored</div>
                </div>

                <button type="submit" class="btn-analyze" id="analyzeBtn" disabled>
                    <span class="spinner" id="spinner"></span>
                    <span id="btnText">✦ Analyze My Vibe</span>
                </button>
            </form>
        </div>

        <!-- Sidebar -->
        <aside class="sidebar-card">
            <div class="sidebar-title">Recent Sessions</div>
            <div class="session-list">
                @forelse($sessions as $session)
                    <a href="{{ route('vibe.result', $session->id) }}" class="session-item">
                        <div class="session-thumb">
                            @if($session->image_path)
                                <img src="{{ Storage::url($session->image_path) }}" alt="vibe">
                            @else
                                🎵
                            @endif
                        </div>
                        <div class="session-meta">
                            <div class="session-caption">
                                {{ $session->caption ?? 'Vibe #' . $session->id }}
                            </div>
                            <div class="session-date">
                                {{ $session->created_at->diffForHumans() }}
                            </div>
                        </div>
                        <span class="session-arrow">›</span>
                    </a>
                @empty
                    <div class="empty-sessions">
                        <span class="empty-icon">🎶</span>
                        Your vibe sessions will appear here.
                    </div>
                @endforelse
            </div>

            @if($sessions->count() > 0)
                <div class="sidebar-footer">
                    <a href="{{ route('vibe.history') }}">View All Sessions →</a>
                </div>
            @endif
        </aside>
    </div>
@endsection

@push('scripts')
    <script>
        const imageInput = document.getElementById('imageInput');
        const previewWrap = document.getElementById('previewWrap');
        const previewImg = document.getElementById('previewImg');
        const dropzone = document.getElementById('dropzone');
        const analyzeBtn = document.getElementById('analyzeBtn');
        const spinner = document.getElementById('spinner');
        const btnText = document.getElementById('btnText');

        function showPreview(file) {
            if (!file || !file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = e => {
                previewImg.src = e.target.result;
                previewWrap.style.display = 'block';
                dropzone.style.display = 'none';
                analyzeBtn.disabled = false;
            };
            reader.readAsDataURL(file);
        }

        function resetUpload() {
            imageInput.value = '';
            previewWrap.style.display = 'none';
            dropzone.style.display = 'flex';
            analyzeBtn.disabled = true;
        }

        imageInput.addEventListener('change', e => {
            if (e.target.files[0]) showPreview(e.target.files[0]);
        });

        // Drag & drop events
        dropzone.addEventListener('dragover', e => {
            e.preventDefault();
            dropzone.classList.add('drag-over');
        });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('drag-over'));
        dropzone.addEventListener('drop', e => {
            e.preventDefault();
            dropzone.classList.remove('drag-over');
            const file = e.dataTransfer.files[0];
            if (file) {
                const dt = new DataTransfer();
                dt.items.add(file);
                imageInput.files = dt.files;
                showPreview(file);
            }
        });

        // Loading state on submit
        document.getElementById('vibeForm').addEventListener('submit', () => {
            analyzeBtn.disabled = true;
            spinner.style.display = 'block';
            btnText.textContent = 'Analyzing...';
        });
    </script>
@endpush