<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Services\PublicId;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    public function index(Request $request)
    {
        $q = Gateway::query()->latest();
        if ($request->has('distributor_id')) {
            $dist = $request->query('distributor_id');
            $q->where('distributor_id', $dist ?: null);
        }
        return response()->json(['ok' => true, 'items' => $q->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'theme' => 'nullable|string',
            'subtitle' => 'nullable|string',
            'tag' => 'nullable|string',
            'phone' => 'nullable|string',
            'qr_image' => 'nullable|string',
            'redirect_url' => 'nullable|string',
            'distributor_id' => 'nullable|string',
            'is_withdraw_active' => 'nullable|boolean',
            'require_name_on_tag' => 'nullable|boolean',
            'require_tag' => 'nullable|boolean',
            'require_phone_on_tag' => 'nullable|boolean',
            'require_email_on_tag' => 'nullable|boolean',
        ]);
        $item = Gateway::create(array_merge([
            'public_id' => PublicId::make('gw_'),
            'theme' => $data['theme'] ?? 'chime',
            'is_withdraw_active' => (bool) ($data['is_withdraw_active'] ?? true),
            'require_name_on_tag' => (bool) ($data['require_name_on_tag'] ?? true),
            'require_tag' => (bool) ($data['require_tag'] ?? true),
            'require_phone_on_tag' => (bool) ($data['require_phone_on_tag'] ?? true),
            'require_email_on_tag' => (bool) ($data['require_email_on_tag'] ?? false),
        ], $data));
        return response()->json(['ok' => true, 'item' => $item], 201);
    }

    public function update(Request $request, string $publicId)
    {
        $item = Gateway::query()->where('public_id', $publicId)->firstOrFail();
        $item->fill($request->only([
            'name','subtitle','tag','phone','theme','qr_image','redirect_url',
            'is_withdraw_active','require_name_on_tag','require_tag','require_phone_on_tag','require_email_on_tag'
        ]));
        $item->save();
        return response()->json(['ok' => true, 'item' => $item]);
    }

    public function destroy(string $publicId)
    {
        Gateway::query()->where('public_id', $publicId)->delete();
        return response()->json(['ok' => true]);
    }
}
