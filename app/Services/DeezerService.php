<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DeezerService — Secondary music API (playable playlist generation)
 *
 * Deezer's public search endpoint requires NO authentication.
 * Cross-references Last.fm tracks to fetch Deezer metadata:
 * album art, 30-second preview URLs, and deep-links.
 *
 * Docs: https://developers.deezer.com/api
 */
class DeezerService
{
    private string $baseUrl = 'https://api.deezer.com';

    // ──────────────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Search for a single track on Deezer.
     *
     * @return array|null  Deezer track object or null if not found
     */
    public function searchTrack(string $title, string $artist = ''): ?array
    {
        $query = $artist ? "{$artist} {$title}" : $title;

        $response = Http::timeout(8)->get("{$this->baseUrl}/search", [
            'q' => $query,
            'limit' => 5,
        ]);

        if (!$response->successful()) {
            Log::warning('Deezer search failed', ['query' => $query, 'status' => $response->status()]);
            return null;
        }

        $results = $response->json('data', []);

        if (empty($results)) {
            return null;
        }

        // Pick the best match (first result after artist name check)
        foreach ($results as $track) {
            $deezerArtist = strtolower($track['artist']['name'] ?? '');
            if ($artist && stripos($deezerArtist, strtolower($artist)) === false) {
                continue;
            }
            return $this->normalizeTrack($track);
        }

        // Fall back to first result if no artist match
        return $this->normalizeTrack($results[0]);
    }

    /**
     * Build an enriched playlist from Last.fm track list by cross-referencing Deezer.
     *
     * @param  array  $lastFmTracks  [{name, artist, url, playcount}, ...]
     * @return array                 Deezer-enriched tracks [{title, artist, album, artwork, preview, deezer_url}, ...]
     */
    public function buildPlaylist(array $lastFmTracks): array
    {
        $playlist = [];

        foreach ($lastFmTracks as $track) {
            $deezer = $this->searchTrack($track['name'], $track['artist']);

            if ($deezer) {
                $playlist[] = $deezer;
            } else {
                // Keep the Last.fm track data even without Deezer enrichment
                $playlist[] = [
                    'id' => null,
                    'title' => $track['name'],
                    'artist' => $track['artist'],
                    'album' => null,
                    'artwork' => null,
                    'preview' => null,
                    'deezer_url' => $track['url'] ?? null,
                    'duration' => null,
                ];
            }

            // Limit playlist to 15 tracks to stay within reasonable API call limits
            if (count($playlist) >= 15) {
                break;
            }
        }

        return $playlist;
    }

    /**
     * Get the top chart tracks from Deezer for a given genre ID.
     * Useful as a fallback when Last.fm returns no tracks.
     */
    public function getChartByGenre(int $genreId = 0, int $limit = 15): array
    {
        $response = Http::timeout(8)->get("{$this->baseUrl}/chart/{$genreId}/tracks", [
            'limit' => $limit,
        ]);

        if (!$response->successful()) {
            return [];
        }

        return array_map(
            fn($t) => $this->normalizeTrack($t),
            $response->json('data', [])
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function normalizeTrack(array $track): array
    {
        return [
            'id' => $track['id'] ?? null,
            'title' => $track['title'] ?? $track['title_short'] ?? 'Unknown',
            'artist' => $track['artist']['name'] ?? 'Unknown',
            'album' => $track['album']['title'] ?? null,
            'artwork' => $track['album']['cover_medium'] ?? $track['album']['cover'] ?? null,
            'preview' => $track['preview'] ?? null,   // 30-second MP3 URL
            'deezer_url' => $track['link'] ?? null,
            'duration' => $track['duration'] ?? null,   // seconds
        ];
    }
}
