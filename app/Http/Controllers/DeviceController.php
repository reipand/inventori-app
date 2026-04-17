<?php

namespace App\Http\Controllers;

use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    // POST /api/devices/register
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fcm_token'   => 'required|string',
            'device_info' => 'nullable|array',
        ]);

        UserDevice::updateOrCreate(
            ['fcm_token' => $validated['fcm_token']],
            ['user_id' => auth()->id(), 'device_info' => $validated['device_info'] ?? null]
        );

        return response()->json(['success' => true]);
    }

    // DELETE /api/devices/unregister
    public function unregister(Request $request): JsonResponse
    {
        $request->validate(['fcm_token' => 'required|string']);

        UserDevice::where('user_id', auth()->id())
            ->where('fcm_token', $request->fcm_token)
            ->delete();

        return response()->json(['success' => true]);
    }
}
