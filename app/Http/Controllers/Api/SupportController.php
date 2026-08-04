<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Services\PublicId;
use App\Services\Realtime;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $user = $request->user() ?? auth('web')->user() ?? auth()->user();
        $q = SupportMessage::query()->latest();
        $requestedEmail = trim((string) $request->query('email'));

        $isStaff = $user && method_exists($user, 'isStaff') && $user->isStaff();

        if ($isStaff) {
            if ($requestedEmail !== '') {
                $q->where('user_email', strtolower($requestedEmail));
                // Automatically mark player messages as READ when staff views thread
                SupportMessage::where('user_email', strtolower($requestedEmail))
                    ->where('sender_type', 'player')
                    ->where('read', false)
                    ->update(['read' => true]);
            }
        } else {
            $effectiveEmail = strtolower($user?->email ?? $requestedEmail);
            if ($effectiveEmail !== '') {
                $q->where('user_email', $effectiveEmail);
            } else {
                return response()->json(['ok' => true, 'items' => []]);
            }
        }
        return response()->json(['ok' => true, 'items' => $q->limit(300)->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'message' => 'nullable|string',
            'user_email' => 'nullable|string',
            'attachment' => 'nullable|string',
            'sender_type' => 'nullable|string',
        ]);
        $user = $request->user();
        $message = trim((string) ($data['message'] ?? ''));
        $attachment = $data['attachment'] ?? null;
        if ($message === '' && empty($attachment)) {
            return response()->json(['ok' => false, 'error' => 'Message or attachment required'], 422);
        }
        if ($message === '' && ! empty($attachment)) {
            $message = 'Attachment';
        }
        $email = strtolower(trim((string) ($data['user_email'] ?? $user?->email ?? '')));
        if (! $email) {
            return response()->json(['ok' => false, 'error' => 'Valid user email or chat ID is required'], 422);
        }
        if ($user && method_exists($user, 'isStaff') && ! $user->isStaff()) {
            $email = strtolower((string) $user->email);
        }
        $isStaff = $user && method_exists($user, 'isStaff') && $user->isStaff();
        $senderType = $data['sender_type'] ?? ($isStaff ? 'admin' : 'player');
        $userName = $user?->name ?? ($isStaff ? 'Admin' : 'Guest Visitor');
        $senderEmail = $user?->email ?? $email;
        $distributorId = $user?->distributor_id ?? null;

        $msg = SupportMessage::create([
            'public_id' => PublicId::make('sm_'),
            'user_email' => $email,
            'user_name' => $userName,
            'message' => $message,
            'attachment' => $attachment,
            'has_attachment' => ! empty($attachment),
            'sender_type' => $senderType,
            'sender_email' => $senderEmail,
            'distributor_id' => $distributorId,
        ]);
        Realtime::publish('support', ['distributorId' => $distributorId, 'senderType' => $senderType]);
        return response()->json(['ok' => true, 'item' => $msg], 201);
    }
}
