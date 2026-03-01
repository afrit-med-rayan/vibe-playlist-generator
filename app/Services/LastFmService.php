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
     * Map AI-extracted keywords to Last.fm mood tags.
     * Returns the best-matching Last.fm tag strings.
     */
    public function getMoodTags(array $keywords, float $energy, float $valence): array
    {
        // Start from the AI's keywords
        $raw = array_map('strtolower', $keywords);

        // Supplement with audio-feature derived tags
        $raw = array_merge($raw, $this->inferTagsFromFeatures($energy, $valence));

        // Validate against Last.fm — only keep tags that actually exist
        $valid = [];
        foreach (array_unique($raw) as $tag) {
            $info = $this->getTagInfo($tag);
            if ($info && ($info['taggings'] ?? 0) > 100) {
                $valid[] = $tag;
            }
            if (count($valid) >= 4) {
                break;
            }
        }

        // Fall back to genre tags if nothing matched
        if (empty($valid)) {
            $valid = $this->inferTagsFromFeatures($energy, $valence);
        }

        return array_values(array_unique($valid));
    }

    /**
     * Get top tracks for a Last.fm tag.
     *
     * @return array<int, array{name: string, artist: string, url: string, playcount: int}>
     */
    public function getTracksByTag(string $tag, int $limit = 20): array
    {
        $response = Http::timeout(10)->get($this->baseUrl, [
            'method' => 'tag.getTopTracks',
            'tag' => $tag,
            'api_key' => $this->apiKey,
            'format' => 'json',
            'limit' => $limit,
        ]);

        if (!$response->successful()) {
            Log::warning('LastFM tag.getTopTracks failed', ['tag' => $tag, 'status' => $response->status()]);
            return [];
        }

        $tracks = $response->json('tracks.track', []);

        return array_map(fn($t) => [
            'name' => $t['name'] ?? 'Unknown',
            'artist' => is_array($t['artist'] ?? null) ? ($t['artist']['name'] ?? 'Unknown') : ($t['artist'] ?? 'Unknown'),
            'url' => $t['url'] ?? '',
            'playcount' => (int) ($t['playcount'] ?? 0),
        ], $tracks);
    }

    /**
     * Get similar artists for a given artist name.
     *
     * @return array<int, array{name: string, url: string, match: float}>
     */
    public function getSimilarArtists(string $artist, int $limit = 6): array
    {
        $response = Http::timeout(10)->get($this->baseUrl, [
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
        $response = Http::timeout(8)->get($this->baseUrl, [
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
     * Derive mood tags from energy/valence scores.
     */
    private function inferTagsFromFeatures(float $energy, float $valence): array
    {
        if ($energy > 0.7 && $valence > 0.6) {
            return ['pop', 'dance', 'happy'];
        } elseif ($energy > 0.7 && $valence <= 0.5) {
            return ['rock', 'metal', 'intense'];
        } elseif ($energy <= 0.4 && $valence > 0.6) {
            return ['acoustic', 'folk', 'chill'];
        } elseif ($energy <= 0.4 && $valence <= 0.4) {
            return ['ambient', 'melancholy', 'sad'];
        } elseif ($energy > 0.4 && $energy <= 0.7 && $valence > 0.5) {
            return ['indie', 'alternative', 'feel-good'];
        } else {
            return ['chill', 'study', 'lo-fi'];
        }
    }
}
