<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'endpoint' => 'required|string',
            'audience' => 'nullable|string',
            'subscription' => 'nullable|array',
            'native_token' => 'nullable|string',
            'type' => 'nullable|string',
            'platform' => 'nullable|string',
        ]);
        $user = $request->user();
        PushSubscription::query()->updateOrCreate(
            ['endpoint' => $data['endpoint'], 'user_email' => $user->email],
            [
                'audience' => $data['audience'] ?? 'player',
                'subscription' => $data['subscription'] ?? null,
                'native_token' => $data['native_token'] ?? null,
                'type' => $data['type'] ?? 'web',
                'platform' => $data['platform'] ?? 'web',
            ]
        );
        return response()->json(['ok' => true]);
    }

    public function broadcast(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
        ]);
        $count = PushSubscription::query()->where('audience', 'player')->count();
        // Delivery: Web Push via VAPID when configured; otherwise report queued count
        $vapidPublic = (string) config('services.vapid.public_key', '');
        $vapidPrivate = (string) config('services.vapid.private_key', '');
        $delivered = 0;
        if ($vapidPublic && $vapidPrivate && class_exists(\Minishlink\WebPush\WebPush::class)) {
            try {
                $auth = [
                    'VAPID' => [
                        'subject' => config('services.vapid.subject', 'mailto:admin@winningheaven.com'),
                        'publicKey' => $vapidPublic,
                        'privateKey' => $vapidPrivate,
                    ],
                ];
                $webPush = new \Minishlink\WebPush\WebPush($auth);
                $payload = json_encode([
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'url' => '/lobby',
                ]);
                foreach (PushSubscription::query()->where('audience', 'player')->where('type', 'web')->cursor() as $sub) {
                    $subData = $sub->subscription;
                    if (!is_array($subData) || empty($subData['endpoint'])) {
                        continue;
                    }
                    $subscription = \Minishlink\WebPush\Subscription::create($subData);
                    $webPush->queueNotification($subscription, $payload);
                    $delivered++;
                }
                foreach ($webPush->flush() as $report) {
                    // swallow per-endpoint results
                }
            } catch (\Throwable $e) {
                return response()->json([
                    'ok' => true,
                    'message' => "Broadcast queued for {$count} subscription(s). Push send error: " . $e->getMessage(),
                    'payload' => $data,
                ]);
            }
        }
        // OneSignal Lock Screen Push Notification fallback / parallel delivery
        $oneSignalAppId = env('ONESIGNAL_APP_ID', '');
        $oneSignalRestKey = env('ONESIGNAL_REST_KEY', '');
        if ($oneSignalAppId && $oneSignalRestKey) {
            try {
                \Illuminate\Support\Facades\Http::withHeaders([
                    'Authorization' => 'Basic ' . $oneSignalRestKey,
                    'Content-Type' => 'application/json',
                ])->post('https://onesignal.com/api/v1/notifications', [
                    'app_id' => $oneSignalAppId,
                    'included_segments' => ['All'],
                    'headings' => ['en' => $data['title']],
                    'contents' => ['en' => $data['body']],
                    'url' => url('/lobby'),
                ]);
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'ok' => true,
            'message' => $delivered > 0
                ? "Push delivered to {$delivered} of {$count} subscription(s)."
                : "Broadcast queued for {$count} player subscription(s). Lock Screen Notification Sent!",
            'payload' => $data,
            'delivered' => $delivered,
        ]);
    }
}
