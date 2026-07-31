<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Services\PublicId;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function index()
    {
        return response()->json(['ok' => true, 'items' => Game::query()->orderBy('title')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'badge' => 'nullable|string',
            'image' => 'nullable|string',
            'link' => 'nullable|string',
            'open_panel_link' => 'nullable|string',
            'available_coins' => 'nullable|numeric',
        ]);
        $game = Game::create([
            'public_id' => PublicId::make('game_'),
            'title' => $data['title'],
            'badge' => $data['badge'] ?? 'none',
            'image' => $data['image'] ?? null,
            'link' => $data['link'] ?? null,
            'open_panel_link' => $data['open_panel_link'] ?? null,
            'available_coins' => $data['available_coins'] ?? 0,
        ]);
        return response()->json(['ok' => true, 'item' => $game], 201);
    }

    public function update(Request $request, string $publicId)
    {
        $game = Game::query()->where('public_id', $publicId)->firstOrFail();
        $game->fill($request->only([
            'title','badge','image','link','open_panel_link','available_coins','used_coins'
        ]));
        $game->save();
        return response()->json(['ok' => true, 'item' => $game]);
    }

    public function destroy(string $publicId)
    {
        Game::query()->where('public_id', $publicId)->delete();
        return response()->json(['ok' => true]);
    }
}
