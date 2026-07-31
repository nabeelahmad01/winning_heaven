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
        $user = $request->user();
        $q = SupportMessage::query()->latest();
        if ($email = $request->query('email')) {
            if ($user && method_exists($user, 'isStaff') && $user->isStaff()) {
                $q->where('user_email', strtolower($email));
            } else {
                $q->where('user_email', strtolower($user?->email ?? $email));
            }
        } elseif ($user && method_exists($user, 'isStaff') && ! $user->isStaff()) {
            $q->where('user_email', strtolower((string) $user->email));
        }
        return response()->json(['ok' => true, 'items' => $q->limit(300)->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'message' => 'nullable|string',
            'user_email' => 'nullable|email',
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
        $email = strtolower($data['user_email'] ?? $user->email);
        if ($user && method_exists($user, 'isStaff') && ! $user->isStaff()) {
            $email = strtolower((string) $user->email);
        }
        $senderType = $data['sender_type'] ?? ($user->isStaff() ? 'admin' : 'player');

        $msg = SupportMessage::create([
            'public_id' => PublicId::make('sm_'),
            'user_email' => $email,
            'user_name' => $user->name,
            'message' => $message,
            'attachment' => $attachment,
            'has_attachment' => ! empty($attachment),
            'sender_type' => $senderType,
            'sender_email' => $user->email,
            'distributor_id' => $user->distributor_id,
        ]);
        Realtime::publish('support', ['distributorId' => $user->distributor_id, 'senderType' => $senderType]);
        return response()->json(['ok' => true, 'item' => $msg], 201);
    }
}
