<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShiftReport;
use App\Services\PublicId;
use Illuminate\Http\Request;

class ShiftReportController extends Controller
{
    public function index()
    {
        return response()->json(['ok' => true, 'items' => ShiftReport::query()->latest()->limit(100)->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'shift_name' => 'required|string',
            'notes' => 'nullable|string',
            'total_loaded' => 'nullable|numeric',
            'shift_date' => 'nullable|date',
        ]);
        $item = ShiftReport::create([
            'public_id' => PublicId::make('shift_'),
            'staff_email' => $request->user()->email,
            'shift_name' => $data['shift_name'],
            'notes' => $data['notes'] ?? null,
            'total_loaded' => $data['total_loaded'] ?? 0,
            'shift_date' => $data['shift_date'] ?? now(),
        ]);
        return response()->json(['ok' => true, 'item' => $item], 201);
    }
}
