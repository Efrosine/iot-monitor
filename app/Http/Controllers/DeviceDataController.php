<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
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
            'data' => 'required|array',
            'name' => 'nullable|string',
        ]);

        // Get the current timestamp
        $now = Carbon::now();

        // Update or create device data record
        DB::table('device_data')->updateOrInsert(
            ['device_id' => $validated['device_id']],
            [
                'name' => $validated['name'] ?? null,
                'data' => json_encode($validated['data']),
                'last_seen_at' => $now,
                'is_online' => true,
                'updated_at' => $now,
                'created_at' => DB::raw('IFNULL(created_at, NOW())'),
            ]
        );

        // Check if history table exists for this device, if not create it
        $historyTableName = "device_{$validated['device_id']}_histories";

        if (!Schema::hasTable($historyTableName)) {
            Artisan::call('device:create-history-table', [
                'device_id' => $validated['device_id']
            ]);
        }

        // Check if we need to save to history
        // Get the latest history entry for this device
        $latestHistory = DB::table($historyTableName)
            ->orderBy('recorded_at', 'desc')
            ->first();

        $shouldSaveToHistory = true;

        if ($latestHistory) {
            $lastRecordedAt = Carbon::parse($latestHistory->recorded_at);
            // Only save to history if it's been at least 1 minute since the last entry
            $shouldSaveToHistory = $lastRecordedAt->diffInMinutes($now) >= 1;
        }

        // If it's time to save to history, do it
        if ($shouldSaveToHistory) {
            DB::table($historyTableName)->insert([
                'data' => json_encode($validated['data']),
                'recorded_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data received successfully',
            'latest_history' => $latestHistory,
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
        $device = DB::table('device_data')
            ->where('device_id', $id)
            ->first();

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device not found'
            ], 404);
        }

        // Parse JSON data
        $device->data = json_decode($device->data);

        return response()->json([
            'status' => 'success',
            'device' => $device,
        ]);
    }
}