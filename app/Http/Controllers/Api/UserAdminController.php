<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeletedUser;
use App\Models\User;
use App\Services\PublicId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserAdminController extends Controller
{
    public function index(Request $request)
    {
        $q = User::query()->latest();
        if ($search = $request->query('search')) {
            $q->where(function ($qq) use ($search) {
                $qq->where('email', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%");
            });
        }
        if ($role = $request->query('role')) {
            $q->where('role', $role);
        }
        if ($request->boolean('staff_only')) {
            $q->where(function ($qq) {
                $qq->where('role', '!=', 'user')->orWhereNotNull('roles');
            });
        }
        return response()->json(['ok' => true, 'items' => $q->limit(200)->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'nullable|string',
            'roles' => 'nullable|array',
            'allowed_game_ids' => 'nullable|array',
            'distributor_id' => 'nullable|string',
            'agent_code' => 'nullable|string',
        ]);
        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'],
            'role' => $data['role'] ?? 'user',
            'roles' => $data['roles'] ?? [],
            'allowed_game_ids' => $data['allowed_game_ids'] ?? [],
            'distributor_id' => $data['distributor_id'] ?? null,
            'agent_code' => $data['agent_code'] ?? null,
            'referral_code' => strtoupper(substr(md5($data['email'] . time()), 0, 8)),
            'status' => 'ACTIVE',
        ]);
        return response()->json(['ok' => true, 'item' => $user], 201);
    }

    public function update(Request $request, string $id)
    {
        $user = User::query()->where('id', $id)->orWhere('email', $id)->firstOrFail();
        $data = $request->validate([
            'status' => 'nullable|string',
            'name' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'coins' => 'nullable|numeric',
            'roles' => 'nullable|array',
            'role' => 'nullable|string',
            'allowed_game_ids' => 'nullable|array',
            'is_subscribed' => 'nullable|boolean',
        ]);
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
            $user->session_revoked = true;
        }
        $user->fill($data);
        $user->save();
        return response()->json(['ok' => true, 'item' => $user]);
    }

    public function destroy(Request $request, string $id)
    {
        $user = User::query()->where('id', $id)->orWhere('email', $id)->firstOrFail();
        DeletedUser::query()->updateOrCreate(
            ['email' => $user->email],
            [
                'snapshot' => $user->toArray(),
                'deleted_at' => now(),
                'deleted_by' => $request->user()?->email,
                'deleted_entity_type' => 'player',
                'former_distributor_id' => $user->distributor_id,
            ]
        );
        $user->delete();
        return response()->json(['ok' => true]);
    }

    public function subscribe(Request $request)
    {
        $user = $request->user();
        $user->is_subscribed = true;
        $user->save();
        return response()->json(['ok' => true]);
    }
}
