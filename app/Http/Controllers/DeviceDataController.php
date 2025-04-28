<?php

namespace App\Http\Controllers;

use App\Models\DeviceData;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DeviceDataController extends Controller
{
    /**
     * Store device data from IoT device
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate incoming request
        $validated = $request->validate([
            'device_id' => 'required|string',
            'payload' => 'required|array',
            'name' => 'nullable|string',
        ]);

        // Update or create device data record using Eloquent
        $deviceData = DeviceData::updateOrCreate(
            ['device_id' => $validated['device_id']],
            [
                'name' => $validated['name'] ?? null,
                'payload' => $validated['payload'],
                'is_online' => true,
            ]
        );

        // Get the current timestamp
        $now = Carbon::now();

        // Check if we need to save to history
        $shouldSaveToHistory = true;

        // Get the latest history entry for this device
        $latestHistory = DeviceData::getHistory($validated['device_id'], 1);

        if (count($latestHistory) > 0) {
            $lastRecordedAt = Carbon::parse($latestHistory[0]->recorded_at);
            // Only save to history if it's been at least 1 minute since the last entry
            $shouldSaveToHistory = $lastRecordedAt->diffInMinutes($now) >= 1;
        }

        // If it's time to save to history, do it
        if ($shouldSaveToHistory) {
            $deviceData->saveToHistory();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data received successfully',
            'updated' => $shouldSaveToHistory,
            'timestamp' => $now,
        ]);
    }

    /**
     * Get latest device data
     *
     * @param string $id Device ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $device = DeviceData::where('device_id', $id)->first();

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'device' => $device,
        ]);
    }

    /**
     * Get device history data
     *
     * @param string $id Device ID
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function history($id, Request $request)
    {
        $limit = $request->input('limit', 100);

        // First check if device exists
        $device = DeviceData::where('device_id', $id)->first();

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device not found'
            ], 404);
        }

        // Get history data
        $history = DeviceData::getHistory($id, $limit);

        return response()->json([
            'status' => 'success',
            'device_id' => $id,
            'history' => $history,
        ]);
    }
}