<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameAccount;
use Illuminate\Http\Request;

class GameAccountController extends Controller
{
    public function index(Request $request)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $q = GameAccount::query()->latest();
        if ($email = $request->query('email')) {
            $q->where('user_email', strtolower($email));
        }
        return response()->json(['ok' => true, 'items' => $q->limit(300)->get()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'user_email' => 'required|email',
            'game_title' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|string',
            'status' => 'nullable|string',
        ]);
        $item = GameAccount::query()->updateOrCreate(
            ['user_email' => strtolower($data['user_email']), 'game_title' => $data['game_title']],
            [
                'username' => $data['username'],
                'password' => $data['password'],
                'status' => $data['status'] ?? 'READY',
            ]
        );
        return response()->json(['ok' => true, 'item' => $item]);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'user_email' => 'required|email',
            'game_title' => 'required|string',
        ]);
        GameAccount::query()
            ->where('user_email', strtolower($data['user_email']))
            ->where('game_title', $data['game_title'])
            ->delete();
        return response()->json(['ok' => true]);
    }
}
