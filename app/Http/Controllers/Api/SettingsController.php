<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function global()
    {
        return response()->json(['ok' => true, 'settings' => SettingsService::global()]);
    }

    public function frontend()
    {
        return response()->json(['ok' => true, 'settings' => SettingsService::frontend()]);
    }

    public function updateGlobal(Request $request)
    {
        $current = SettingsService::global();
        Setting::putValue('global_settings', array_merge($current, $request->all()));
        return response()->json(['ok' => true, 'settings' => SettingsService::global()]);
    }

    public function updateFrontend(Request $request)
    {
        $current = SettingsService::frontend();
        Setting::putValue('frontend_settings', array_merge($current, $request->all()));
        return response()->json(['ok' => true, 'settings' => SettingsService::frontend()]);
    }
}
