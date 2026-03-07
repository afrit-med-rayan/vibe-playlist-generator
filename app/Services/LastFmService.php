<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LastFmService — Primary music data API (mood tags + track discovery)
 *
 * Uses the Last.fm public API (no user auth required for search/tag endpoints).
 * Docs: https://www.last.fm/api
 */
class LastFmService
{
    private string $baseUrl = 'https://ws.audioscrobbler.com/2.0/';
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.lastfm.api_key', '');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Mood Tags
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Map AI-extracted keywords + genre hints to Last.fm mood tags.
     *
     * Priority order:
     *   1. genre_hints from AI (cultural/artistic style detection — most specific)
     *   2. keyword-to-genre direct map (scene words → music genres)
     *   3. audio-feature derived tags (energy + valence bucketing)
     *
     * Returns up to 5 validated Last.fm tags.
     */
    public function getMoodTags(array $keywords, float $energy, float $valence, array $genreHints = []): array
    {
        $candidates = [];

        // ── Priority 1: genre hints from AI style detection (already genre-specific) ──
        foreach ($genreHints as $hint) {
            $candidates[] = strtolower(trim($hint));
        }

        // ── Priority 2: keyword → genre direct map ──
        foreach ($keywords as $kw) {
            $mapped = $this->keywordToGenres(strtolower($kw));
            foreach ($mapped as $genre) {
                if (!in_array($genre, $candidates)) {
                    $candidates[] = $genre;
                }
            }
        }

        // ── Priority 3: audio-feature tags (always append as fallback) ──
        foreach ($this->inferTagsFromFeatures($energy, $valence) as $tag) {
            if (!in_array($tag, $candidates)) {
                $candidates[] = $tag;
            }
        }

        // Validate against Last.fm — only keep tags that actually exist there
        $valid = [];
        foreach (array_unique($candidates) as $tag) {
            $info = $this->getTagInfo($tag);
            if ($info && ($info['taggings'] ?? 0) > 50) {
                $valid[] = $tag;
            }
            if (count($valid) >= 5) {
                break;
            }
        }

        // Final fallback
        if (empty($valid)) {
            $valid = $this->inferTagsFromFeatures($energy, $valence);
        }

        Log::info('LastFm getMoodTags', [
            'genreHints' => $genreHints,
            'topKeywords' => array_slice($keywords, 0, 5),
            'energy' => $energy,
            'valence' => $valence,
            'resolvedTags' => $valid,
        ]);

        return array_values(array_unique($valid));
    }

    /**
     * Get top tracks for a Last.fm tag with optional randomisation.
     *
     * @return array<int, array{name: string, artist: string, url: string, playcount: int}>
     */
    public function getTracksByTag(string $tag, int $limit = 30, int $page = 1): array
    {
        $response = Http::timeout(10)->withUserAgent('vibe-playlist-generator/1.0')->get($this->baseUrl, [
            'method' => 'tag.getTopTracks',
            'tag' => $tag,
            'api_key' => $this->apiKey,
            'format' => 'json',
            'limit' => $limit,
            'page' => $page,
        ]);

        if (!$response->successful()) {
            Log::warning('LastFM tag.getTopTracks failed', ['tag' => $tag, 'status' => $response->status()]);
            return [];
        }

        $tracks = $response->json('tracks.track', []);

        return array_map(fn($t) => [
            'name' => $t['name'] ?? 'Unknown',
            'artist' => is_array($t['artist'] ?? null)
                ? ($t['artist']['name'] ?? 'Unknown')
                : ($t['artist'] ?? 'Unknown'),
            'url' => $t['url'] ?? '',
            'playcount' => (int) ($t['playcount'] ?? 0),
        ], $tracks);
    }

    /**
     * Fetch tracks from MULTIPLE tags and merge / deduplicate.
     * This is the main method used by VibeController to build diverse playlists.
     *
     * @param  array  $tags      Ordered list of Last.fm tags (most specific first)
     * @param  int    $total     Total tracks to collect
     * @return array<int, array{name: string, artist: string, url: string, playcount: int}>
     */
    public function getTracksByTags(array $tags, int $total = 30): array
    {
        if (empty($tags)) {
            return [];
        }

        // Distribute track slots across tags; primary tag gets the lion's share
        $slots = $this->distributeSlots($tags, $total);
        $allTracks = [];
        $seenKeys = [];

        foreach ($tags as $i => $tag) {
            $limit = $slots[$i] ?? 10;

            // Randomly pick page 1 or 2 so we don't always get the same "top" tracks
            $page = rand(1, 2);
            $tracks = $this->getTracksByTag($tag, $limit + 5, $page);

            foreach ($tracks as $track) {
                $key = strtolower($track['artist'] . '|' . $track['name']);
                if (!isset($seenKeys[$key])) {
                    $seenKeys[$key] = true;
                    $track['_source_tag'] = $tag;
                    $allTracks[] = $track;
                }
                if (count($allTracks) >= $total) {
                    break 2;
                }
            }
        }

        // Shuffle to avoid always showing the same tracks in the same order
        shuffle($allTracks);

        return array_slice($allTracks, 0, $total);
    }

    /**
     * Get similar artists for a given artist name.
     *
     * @return array<int, array{name: string, url: string, match: float}>
     */
    public function getSimilarArtists(string $artist, int $limit = 6): array
    {
        $response = Http::timeout(10)->withUserAgent('vibe-playlist-generator/1.0')->get($this->baseUrl, [
            'method' => 'artist.getSimilar',
            'artist' => $artist,
            'api_key' => $this->apiKey,
            'format' => 'json',
            'limit' => $limit,
        ]);

        if (!$response->successful()) {
            return [];
        }

        return array_map(fn($a) => [
            'name' => $a['name'] ?? 'Unknown',
            'url' => $a['url'] ?? '',
            'match' => (float) ($a['match'] ?? 0),
        ], $response->json('similarartists.artist', []));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function getTagInfo(string $tag): ?array
    {
        $response = Http::timeout(8)->withUserAgent('vibe-playlist-generator/1.0')->get($this->baseUrl, [
            'method' => 'tag.getInfo',
            'tag' => $tag,
            'api_key' => $this->apiKey,
            'format' => 'json',
        ]);

        if (!$response->successful()) {
            return null;
        }

        return $response->json('tag');
    }

    /**
     * Distribute track quota across tags proportionally.
     * Primary tag (index 0) gets ~50%, subsequent tags share the rest.
     */
    private function distributeSlots(array $tags, int $total): array
    {
        $count = count($tags);
        if ($count === 1) {
            return [$total];
        }

        $slots = [];
        $primary = (int) round($total * 0.50);
        $remaining = $total - $primary;
        $slots[] = $primary;

        $secondary = $count - 1;
        $perTag = (int) floor($remaining / $secondary);
        $leftover = $remaining - ($perTag * $secondary);

        for ($i = 1; $i < $count; $i++) {
            $slots[] = $perTag + ($i === 1 ? $leftover : 0);
        }

        return $slots;
    }

    /**
     * Map individual scene/mood keywords to music genre tags.
     * Supplements AI genre_hints with per-keyword genre inference.
     */
    private function keywordToGenres(string $keyword): array
    {
        $map = [
            // Cultural / world
            'zellij' => ['arabic', 'world music', 'oriental'],
            'mosaic' => ['world music', 'ambient'],
            'arabesque' => ['arabic', 'oriental'],
            'geometric' => ['world music', 'ambient'],
            'moroccan' => ['arabic', 'world music'],
            'algerian' => ['arabic', 'world music', 'rai'],
            'tribal' => ['world music', 'ethnic'],
            'mandala' => ['indian', 'meditation'],
            'bohemian' => ['indie folk', 'folk'],
            'hippie' => ['psychedelic', 'classic rock', 'folk'],
            'psychedelic' => ['psychedelic', 'classic rock'],
            'tie-dye' => ['psychedelic', '60s'],
            'groovy' => ['funk', 'soul', '70s'],

            // Nature
            'forest' => ['folk', 'acoustic', 'ambient'],
            'beach' => ['reggae', 'tropical', 'chill'],
            'ocean' => ['ambient', 'chill'],
            'desert' => ['ambient', 'world music'],
            'mountain' => ['folk', 'acoustic'],
            'tropical' => ['reggae', 'tropical'],
            'jungle' => ['world music', 'tropical'],
            'rain' => ['lo-fi', 'ambient'],
            'snow' => ['ambient', 'classical'],

            // Urban / electronic
            'neon' => ['synthwave', 'electronic'],
            'city' => ['hip-hop', 'electronic'],
            'graffiti' => ['hip-hop', 'rap'],
            'futuristic' => ['electronic', 'synthwave'],
            'cyberpunk' => ['cyberpunk', 'synthwave'],

            // Mood
            'romantic' => ['romantic', 'jazz', 'soul'],
            'melancholy' => ['melancholy', 'sad', 'indie'],
            'peaceful' => ['ambient', 'acoustic', 'chill'],
            'dark' => ['dark', 'gothic', 'alternative'],
            'epic' => ['epic', 'cinematic', 'orchestral'],
            'party' => ['dance', 'edm', 'pop'],
            'meditation' => ['meditation', 'ambient', 'zen'],

            // Retro / artistic
            'vintage' => ['classic rock', 'jazz', 'oldies'],
            'retro' => ['retro', 'classic rock'],
            'gothic' => ['gothic', 'dark', 'alternative'],
            'grunge' => ['grunge', 'alternative rock'],
            'punk' => ['punk', 'rock'],
            'minimalist' => ['ambient', 'classical', 'minimal'],
        ];

        return $map[$keyword] ?? [];
    }

    /**
     * Derive mood tags from energy/valence scores.
     * More granular than before — 9 zones instead of 6.
     */
    private function inferTagsFromFeatures(float $energy, float $valence): array
    {
        // High energy
        if ($energy > 0.75 && $valence > 0.65) {
            return ['dance', 'happy', 'pop'];
        }
        if ($energy > 0.75 && $valence > 0.40) {
            return ['rock', 'energetic', 'alternative'];
        }
        if ($energy > 0.75 && $valence <= 0.40) {
            return ['metal', 'hard rock', 'intense'];
        }

        // Mid energy
        if ($energy > 0.50 && $valence > 0.65) {
            return ['indie', 'feel-good', 'alternative'];
        }
        if ($energy > 0.50 && $valence > 0.35) {
            return ['indie', 'alternative', 'pop rock'];
        }
        if ($energy > 0.50 && $valence <= 0.35) {
            return ['dark', 'alternative', 'post-rock'];
        }

        // Low energy
        if ($energy <= 0.50 && $valence > 0.65) {
            return ['acoustic', 'folk', 'chill'];
        }
        if ($energy <= 0.50 && $valence > 0.35) {
            return ['lo-fi', 'study', 'chill'];
        }

        // Low energy + low valence
        return ['ambient', 'melancholy', 'sad'];
    }
}
