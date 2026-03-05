<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VibeSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'image_path',
        'caption',
        'keywords',
        'genre_hints',
        'energy',
        'valence',
        'tempo',
        'acousticness',
        'playlist_id',
        'playlist_url',
        'playlist_name',
    ];

    protected $casts = [
        'keywords' => 'array',
        'genre_hints' => 'array',
        'energy' => 'float',
        'valence' => 'float',
        'tempo' => 'float',
        'acousticness' => 'float',
    ];

    /**
     * The vibe session belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
