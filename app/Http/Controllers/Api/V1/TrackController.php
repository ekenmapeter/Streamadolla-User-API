<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Track;
use Illuminate\Http\Request;

class TrackController extends Controller
{
    public function index(Request $request)
    {
        $limit = min((int) $request->input('limit', 50), 100);

        $tracks = Track::live()
            ->with('artist:id,name')
            ->orderByDesc('approved_at')
            ->limit($limit)
            ->get()
            ->map(fn (Track $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'artist_name' => $t->artist_name ?? $t->artist?->name,
                'platform' => $t->platform,
                'media_url' => $t->media_url,
                'genre' => $t->genre,
                'duration_seconds' => (int) $t->duration_seconds,
                'streams_served' => (int) $t->streams_served,
                'stream_budget' => (int) $t->stream_budget,
            ]);

        return response()->json([
            'success' => true,
            'tracks' => $tracks,
        ]);
    }
}