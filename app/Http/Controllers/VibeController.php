<?php

namespace App\Http\Controllers;

use App\Models\VibeSession;
use App\Services\LastFmService;
use App\Services\DeezerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class VibeController extends Controller
{
    public function __construct(
        private LastFmService $lastFm,
        private DeezerService $deezer
    ) {
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Dashboard
    // ──────────────────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $sessions = Auth::user()->vibeSessions()->latest()->take(5)->get();
        return view('dashboard', compact('sessions'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Step 1 of pipeline: Image → AI analysis
    // ──────────────────────────────────────────────────────────────────────────

    public function analyze(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240',
        ]);

        $user = Auth::user();
        $image = $request->file('image');

        // Store uploaded image
        $path = $image->store('vibes', 'public');

        // Call Python AI microservice
        $aiServiceUrl = env('AI_SERVICE_URL', 'http://localhost:8001');

        try {
            $response = Http::timeout(300)   // longer timeout — multi-prompt BLIP needs more time
                ->attach('image', file_get_contents($image->getRealPath()), $image->getClientOriginalName())
                ->post("{$aiServiceUrl}/analyze-image");

            if (!$response->successful()) {
                return back()->withErrors(['image' => 'AI service failed to analyze the image. Please try again.']);
            }

            $vibe = $response->json();

        } catch (\Exception $e) {
            return back()->withErrors(['image' => 'Could not connect to AI service: ' . $e->getMessage()]);
        }

        // Persist vibe session — now also stores genre_hints
        $session = VibeSession::create([
            'user_id' => $user->id,
            'image_path' => $path,
            'caption' => $vibe['caption'] ?? null,
            'keywords' => $vibe['keywords'] ?? [],
            'genre_hints' => $vibe['genre_hints'] ?? [],   // NEW: AI cultural/style genres
            'energy' => $vibe['energy'] ?? 0.5,
            'valence' => $vibe['valence'] ?? 0.5,
            'tempo' => $vibe['tempo'] ?? 120,
            'acousticness' => $vibe['acousticness'] ?? 0.5,
        ]);

        return redirect()->route('vibe.result', $session->id);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Step 2: Show result — Last.fm tags (multi) → Deezer track previews
    // ──────────────────────────────────────────────────────────────────────────

    public function result(VibeSession $session)
    {
        $this->authorize('view', $session);

        // 1. Resolve Last.fm mood tags — genre_hints are now Priority 1
        $moodTags = $this->lastFm->getMoodTags(
            $session->keywords ?? [],
            $session->energy ?? 0.5,
            $session->valence ?? 0.5,
            $session->genre_hints ?? [],   // pass AI genre hints
        );

        // 2. Fetch tracks from ALL resolved tags for a coherent, diverse playlist
        $lastFmTracks = $this->lastFm->getTracksByTags($moodTags, 30);

        // Fallback: if multi-tag fetch returned nothing, use the primary tag
        if (empty($lastFmTracks) && !empty($moodTags)) {
            $lastFmTracks = $this->lastFm->getTracksByTag($moodTags[0], 20);
        }

        // 3. Enrich with Deezer (artwork + preview URLs)
        $tracks = $this->deezer->buildPlaylist($lastFmTracks);

        return view('vibe.result', compact('session', 'tracks', 'moodTags'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Step 3: "Save playlist" — persist playlist metadata to session
    // ──────────────────────────────────────────────────────────────────────────

    public function createPlaylist(Request $request, VibeSession $session)
    {
        $this->authorize('update', $session);

        $moodTags = $this->lastFm->getMoodTags(
            $session->keywords ?? [],
            $session->energy ?? 0.5,
            $session->valence ?? 0.5,
            $session->genre_hints ?? [],
        );

        $lastFmTracks = $this->lastFm->getTracksByTags($moodTags, 30);
        if (empty($lastFmTracks) && !empty($moodTags)) {
            $lastFmTracks = $this->lastFm->getTracksByTag($moodTags[0], 20);
        }

        $tracks = $this->deezer->buildPlaylist($lastFmTracks);

        if (empty($tracks)) {
            return back()->withErrors(['playlist' => 'No tracks found for this vibe. Try a different image.']);
        }

        $keywords = $session->keywords ?? [];
        $topKeywords = implode(', ', array_slice($keywords, 0, 3));
        $playlistName = 'Vibe: ' . ($topKeywords ?: ucfirst($moodTags[0] ?? 'chill')) . ' 🎵';

        $session->update([
            'playlist_name' => $playlistName,
            'playlist_url' => count($tracks) > 0 ? ($tracks[0]['deezer_url'] ?? null) : null,
        ]);

        return redirect()->route('vibe.result', $session->id)
            ->with('success', "Playlist \"{$playlistName}\" saved! 🎶");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // History
    // ──────────────────────────────────────────────────────────────────────────

    public function history()
    {
        $sessions = Auth::user()->vibeSessions()->latest()->paginate(12);
        return view('vibe.history', compact('sessions'));
    }
}
