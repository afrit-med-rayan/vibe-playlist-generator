<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('spotify')
            ->scopes(['playlist-modify-private', 'user-read-email'])
            ->redirect();
    }

    public function callback()
    {
        try {
            $spotifyUser = Socialite::driver('spotify')->user();

            $user = User::updateOrCreate([
                'spotify_id' => $spotifyUser->id,
            ], [
                'name' => $spotifyUser->name,
                'email' => $spotifyUser->email,
                'spotify_token' => $spotifyUser->token,
                'spotify_refresh_token' => $spotifyUser->refreshToken,
                'avatar' => $spotifyUser->avatar,
                'password' => null, // No password for OAuth users
            ]);

            Auth::login($user);

            return redirect('/dashboard');
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Spotify authentication failed.');
        }
    }
}
