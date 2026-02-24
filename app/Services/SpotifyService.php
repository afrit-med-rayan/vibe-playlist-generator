<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SpotifyService
{
    private string $baseUrl = 'https://api.spotify.com/v1';

    /**
     * Refresh the access token using the refresh token.
     */
    public function refreshAccessToken(string $refreshToken): ?array
    {
        $response = Http::asForm()->withBasicAuth(
            config('services.spotify.client_id'),
            config('services.spotify.client_secret')
        )->post('https://accounts.spotify.com/api/token', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ]);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Get track recommendations based on vibe audio features.
     */
    public function getRecommendations(
        string $token,
        float $energy,
        float $valence,
        float $tempo,
        float $acousticness = 0.5,
        int $limit = 20
    ): array {
        $response = Http::withToken($token)->get("{$this->baseUrl}/recommendations", [
            'limit' => $limit,
            'seed_genres' => $this->inferGenres($energy, $valence),
            'target_energy' => $energy,
            'target_valence' => $valence,
            'target_tempo' => $tempo,
            'target_acousticness' => $acousticness,
        ]);

        if ($response->successful()) {
            return $response->json('tracks', []);
        }

        return [];
    }

    /**
     * Create a new Spotify playlist for the user.
     */
    public function createPlaylist(
        string $token,
        string $spotifyUserId,
        string $name,
        string $description = ''
    ): ?array {
        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/users/{$spotifyUserId}/playlists", [
                'name' => $name,
                'description' => $description,
                'public' => false,
            ]);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Add tracks to a Spotify playlist.
     */
    public function addTracksToPlaylist(string $token, string $playlistId, array $trackUris): bool
    {
        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/playlists/{$playlistId}/tracks", [
                'uris' => $trackUris,
            ]);

        return $response->successful();
    }

    /**
     * Get the current Spotify user's profile.
     */
    public function getCurrentUser(string $token): ?array
    {
        $response = Http::withToken($token)->get("{$this->baseUrl}/me");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Infer seed genres from energy and valence values.
     */
    private function inferGenres(float $energy, float $valence): string
    {
        if ($energy > 0.7 && $valence > 0.6) {
            return 'pop,dance';
        } elseif ($energy > 0.7 && $valence <= 0.6) {
            return 'metal,rock';
        } elseif ($energy <= 0.4 && $valence > 0.6) {
            return 'acoustic,folk';
        } elseif ($energy <= 0.4 && $valence <= 0.4) {
            return 'ambient,classical';
        } elseif ($energy > 0.4 && $energy <= 0.7 && $valence > 0.5) {
            return 'indie,pop';
        } else {
            return 'chill,study';
        }
    }
}
