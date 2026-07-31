<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeletedUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DeletedPlayerController extends Controller
{
    public function index()
    {
        return response()->json(['ok' => true, 'items' => DeletedUser::query()->latest('deleted_at')->limit(200)->get()]);
    }

    public function restore(Request $request, string $email)
    {
        $row = DeletedUser::query()->where('email', strtolower($email))->firstOrFail();
        $snap = $row->snapshot ?? [];
        User::query()->updateOrCreate(
            ['email' => $row->email],
            [
                'name' => $snap['name'] ?? 'Restored Player',
                'password' => $snap['password'] ?? Hash::make(bin2hex(random_bytes(4))),
                'role' => 'user',
                'status' => 'ACTIVE',
                'referral_code' => $snap['referral_code'] ?? strtoupper(substr(md5($row->email), 0, 8)),
                'distributor_id' => null,
                'former_distributor_id' => $row->former_distributor_id,
            ]
        );
        $row->delete();
        return response()->json(['ok' => true]);
    }
}
