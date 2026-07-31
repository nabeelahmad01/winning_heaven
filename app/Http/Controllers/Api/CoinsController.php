<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoinsNotification;
use App\Models\Game;
use App\Models\Transaction;
use App\Services\FreeplayService;
use App\Services\Realtime;
use Illuminate\Http\Request;

class CoinsController extends Controller
{
    public function index(Request $request)
    {
        $q = CoinsNotification::query()->latest('notified_at');
        if ($status = $request->query('status')) {
            $statuses = array_filter(array_map('trim', explode(',', $status)));
            count($statuses) > 1 ? $q->whereIn('status', $statuses) : $q->where('status', $statuses[0] ?? $status);
        }
        if ($dist = $request->query('distributor_id')) {
            $q->where('distributor_id', $dist);
        }
        if ($email = $request->query('email')) {
            $q->where('user_email', strtolower($email));
        }
        return response()->json(['ok' => true, 'items' => $q->limit(200)->get()]);
    }

    public function update(Request $request, string $publicId)
    {
        $item = CoinsNotification::query()->where('public_id', $publicId)->firstOrFail();
        $data = $request->validate([
            'status' => 'required|string',
            'hold_note' => 'nullable|string',
            'processed_by' => 'nullable|string',
            'read' => 'nullable|boolean',
        ]);

        $prev = $item->status;
        $item->status = $data['status'];
        $item->hold_note = $data['hold_note'] ?? $item->hold_note;
        $item->processed_by = $data['processed_by'] ?? $request->user()?->email;
        $item->read = array_key_exists('read', $data) ? (bool) $data['read'] : true;
        $item->save();

        // HOLD on withdraw deduct → fail parent
        if ($item->status === 'HOLD' && (float) $item->bonus_applied === -1.0 && (float) $item->total_coins < 0) {
            $tx = Transaction::query()->where('public_id', $item->transaction_id)->first();
            if ($tx && in_array($tx->status, ['PENDING_COINS', 'PENDING'], true)) {
                $tx->status = 'FAILED';
                $tx->note = $item->hold_note ?: 'Coins staff invalidated withdraw.';
                $tx->coins_hold_note = $item->hold_note;
                $tx->coins_hold_at = now();
                $tx->save();
                $item->status = 'FAILED';
                $item->save();
                Realtime::publish('transactions', ['distributorId' => $tx->distributor_id]);
            }
        }

        if ($item->status === 'COMPLETED' && $prev !== 'COMPLETED' && $item->transaction_id) {
            $tx = Transaction::query()->where('public_id', $item->transaction_id)->first();
            if ($tx) {
                $tx->allotted_by = $item->processed_by;
                $tx->coins_allotted_at = now();

                if ($tx->type === 'WITHDRAW' && $tx->status === 'PENDING_COINS') {
                    $tx->status = 'PENDING';
                    if ($tx->is_freeplay_withdraw || $item->is_freeplay_withdraw) {
                        $cap = FreeplayService::cashoutCap();
                        $tx->payout_amount = $cap;
                        $tx->amount = $cap;
                        $tx->is_freeplay_withdraw = true;
                        $tx->note = 'Freeplay win capped at $' . number_format($cap, 0) . ' max cashout.';
                    }
                } elseif (in_array($tx->type, ['DEPOSIT', 'BONUS'], true)) {
                    $tx->status = 'SUCCESS';
                }
                $tx->save();
                Realtime::publish('transactions', ['distributorId' => $tx->distributor_id]);
            }

            // Pool adjust (skip usedCoins bump for freeplay withdraw)
            $gameTitle = $item->game_title;
            $amount = (float) $item->total_coins;
            if ($gameTitle && $amount != 0.0) {
                $game = Game::query()->where('title', $gameTitle)->first();
                if ($game) {
                    $deduct = abs($amount);
                    $game->available_coins = max(0, (float) $game->available_coins - $deduct);
                    if (!$item->is_freeplay_withdraw) {
                        $game->used_coins = (float) $game->used_coins + $deduct;
                    }
                    $game->save();
                }
            }
        }

        Realtime::publish('coins', ['distributorId' => $item->distributor_id]);
        return response()->json(['ok' => true, 'item' => $item->fresh()]);
    }
}
