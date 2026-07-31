<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\PushSubscription;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PublicId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        $q = Promotion::query()->latest()->limit(100);
        $email = strtolower(trim((string) $request->query('email', '')));
        if ($email !== '') {
            $user = User::query()->where('email', $email)->first();
            $items = $q->get()->filter(function (Promotion $p) use ($user) {
                $group = strtolower((string) ($p->target_group ?: 'all'));
                if ($group === 'all') {
                    return true;
                }
                if (!$user) {
                    return false;
                }
                if ($group === 'subscribed') {
                    return (bool) $user->is_subscribed;
                }
                if ($group === 'unsubscribed') {
                    return !$user->is_subscribed;
                }
                if ($group === 'active') {
                    return Transaction::query()
                        ->where('user_email', $user->email)
                        ->where('type', 'DEPOSIT')
                        ->where('status', 'APPROVED')
                        ->exists();
                }
                return true;
            })->values();
            return response()->json(['ok' => true, 'items' => $items, 'promotions' => $items]);
        }
        return response()->json(['ok' => true, 'items' => $q->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'message' => 'nullable|string',
            'target_group' => 'nullable|in:all,subscribed,unsubscribed,active',
            'image' => 'nullable|string',
            'promo_type' => 'nullable|in:message,freeplay,deposit_bonus',
            'freeplay_amount' => 'nullable|numeric',
            'bonus_percent' => 'nullable|numeric',
            'send_email' => 'nullable|boolean',
            'send_push' => 'nullable|boolean',
        ]);

        $item = Promotion::create([
            'public_id' => PublicId::make('promo_'),
            'title' => $data['title'],
            'message' => $data['message'] ?? null,
            'target_group' => $data['target_group'] ?? 'subscribed',
            'image' => $data['image'] ?? null,
            'promo_type' => $data['promo_type'] ?? 'message',
            'freeplay_amount' => $data['freeplay_amount'] ?? 0,
            'bonus_percent' => $data['bonus_percent'] ?? 0,
        ]);

        $players = $this->targetPlayers($item->target_group);
        $emailed = 0;
        $pushed = 0;

        if ($request->boolean('send_email', true)) {
            $emailed = $this->emailPlayers($players, $item);
        }
        if ($request->boolean('send_push', true)) {
            $pushed = $this->countPushTargets($players);
        }

        return response()->json([
            'ok' => true,
            'item' => $item,
            'reach' => [
                'players' => $players->count(),
                'emailed' => $emailed,
                'push_targets' => $pushed,
            ],
        ], 201);
    }

    public function destroy(string $publicId)
    {
        Promotion::query()->where('public_id', $publicId)->delete();
        return response()->json(['ok' => true]);
    }

    public function claim(Request $request)
    {
        $data = $request->validate(['public_id' => 'required|string']);
        $promo = Promotion::query()->where('public_id', $data['public_id'])->firstOrFail();
        $user = $request->user();

        if ($promo->promo_type === 'deposit_bonus' && (float) $promo->bonus_percent > 0) {
            $user->pending_deposit_bonus_percent = $promo->bonus_percent;
            $user->pending_bonus_promo_id = $promo->public_id;
            $user->pending_bonus_promo_title = $promo->title;
            $user->save();
            return response()->json(['ok' => true, 'armed' => 'deposit_bonus', 'percent' => $promo->bonus_percent]);
        }

        if ($promo->promo_type === 'freeplay' && (float) $promo->freeplay_amount > 0) {
            $user->pending_bonus_freeplay = $promo->freeplay_amount;
            $user->pending_bonus_promo_id = $promo->public_id;
            $user->pending_bonus_promo_title = $promo->title;
            $user->save();
            return response()->json(['ok' => true, 'armed' => 'freeplay', 'amount' => $promo->freeplay_amount]);
        }

        return response()->json(['ok' => true, 'armed' => 'message']);
    }

    private function targetPlayers(string $group)
    {
        $group = strtolower($group ?: 'all');
        $players = User::query()->latest()->limit(3000)->get()->filter(fn (User $u) => !$u->isStaff());

        if ($group === 'subscribed') {
            $players = $players->filter(fn (User $u) => (bool) $u->is_subscribed);
        } elseif ($group === 'unsubscribed') {
            $players = $players->filter(fn (User $u) => !$u->is_subscribed);
        } elseif ($group === 'active') {
            $emails = Transaction::query()
                ->where('type', 'DEPOSIT')
                ->where('status', 'APPROVED')
                ->distinct()
                ->pluck('user_email')
                ->map(fn ($e) => strtolower((string) $e))
                ->all();
            $set = array_flip($emails);
            $players = $players->filter(fn (User $u) => isset($set[strtolower($u->email)]));
        }

        return $players->take(2000)->values();
    }

    private function emailPlayers($players, Promotion $item): int
    {
        $mailer = (string) config('mail.default');
        $hasCreds = filled(config('mail.mailers.smtp.username')) && filled(config('mail.mailers.smtp.password'));
        if ($mailer === 'log' || !$hasCreds) {
            return 0;
        }
        $count = 0;
        $lobbyUrl = rtrim((string) config('app.url'), '/').'/lobby';
        foreach ($players as $player) {
            try {
                Mail::send('emails.promotion', [
                    'title' => $item->title,
                    'message' => $item->message ?? '',
                    'image' => $item->image,
                    'lobbyUrl' => $lobbyUrl,
                ], function ($message) use ($player, $item) {
                    $message->to($player->email)->subject('Winning Heaven — '.$item->title);
                });
                $count++;
            } catch (\Throwable $e) {
                \Log::warning('Promo mail failed for '.$player->email.': '.$e->getMessage());
            }
        }
        return $count;
    }

    private function countPushTargets($players): int
    {
        if (!class_exists(PushSubscription::class)) {
            return 0;
        }
        $emails = $players->pluck('email')->filter()->all();
        if (!$emails) {
            return 0;
        }
        return PushSubscription::query()->whereIn('user_email', $emails)->count();
    }
}
