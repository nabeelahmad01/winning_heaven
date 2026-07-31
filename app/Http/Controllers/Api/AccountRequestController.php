<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountRequest;
use App\Models\GameAccount;
use App\Services\PublicId;
use App\Services\Realtime;
use Illuminate\Http\Request;

class AccountRequestController extends Controller
{
    public function index(Request $request)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $q = AccountRequest::query()->latest();
        if ($email = $request->query('email')) {
            $q->where('user_email', strtolower($email));
        }
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        return response()->json(['ok' => true, 'items' => $q->limit(200)->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['game_title' => 'required|string']);
        $user = $request->user();
        $item = AccountRequest::create([
            'public_id' => PublicId::make('ar_'),
            'user_email' => $user->email,
            'game_title' => $data['game_title'],
            'status' => 'PENDING',
            'distributor_id' => $user->distributor_id,
        ]);
        Realtime::publish('requests', ['distributorId' => $user->distributor_id]);
        return response()->json(['ok' => true, 'item' => $item], 201);
    }

    public function update(Request $request, string $publicId)
    {
        $item = AccountRequest::query()->where('public_id', $publicId)->firstOrFail();
        $data = $request->validate([
            'status' => 'required|string',
            'game_account_username' => 'nullable|string',
            'game_account_password' => 'nullable|string',
            'rejection_reason' => 'nullable|string',
        ]);
        $item->fill($data);
        $item->processed_by = $request->user()?->email;
        $item->save();

        if (in_array($item->status, ['READY', 'COMPLETED'], true)) {
            GameAccount::query()->updateOrCreate(
                ['user_email' => $item->user_email, 'game_title' => $item->game_title],
                [
                    'username' => $item->game_account_username,
                    'password' => $item->game_account_password,
                    'status' => 'READY',
                ]
            );
        }

        Realtime::publish('requests', ['status' => $item->status]);
        return response()->json(['ok' => true, 'item' => $item]);
    }
}
