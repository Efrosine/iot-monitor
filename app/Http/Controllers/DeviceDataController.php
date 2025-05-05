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
            'device_type' => 'nullable|string|in:sensor,actuator',
            'is_online' => 'nullable|boolean',
        ]);

        // Update or create device data record using Eloquent
        $deviceData = DeviceData::updateOrCreate(
            ['device_id' => $validated['device_id']],
            [
                'name' => $validated['name'] ?? null,
                'device_type' => $validated['device_type'] ?? 'sensor',
                'payload' => $validated['payload'],
                'is_online' => $validated['is_online'] ?? true,
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

    /**
     * Update a specific device's data (particularly for actuators)
     *
     * @param string $id Device ID
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        // Validate incoming request
        $validated = $request->validate([
            'device_id' => 'required|string',
            'payload' => 'required|array',
            'device_type' => 'required|string|in:actuator',
        ]);

        // First check if device exists
        $device = DeviceData::where('device_id', $id)->first();

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device not found'
            ], 404);
        }

        // For actuators, we only update the specific payload keys that were provided
        // This allows partial updates of actuator states
        $currentPayload = $device->payload;
        foreach ($validated['payload'] as $key => $value) {
            $currentPayload[$key] = $value;
        }

        // Update the device data
        $device->update([
            'payload' => $currentPayload,
            'is_online' => true, // Assume device is online if it's receiving updates
        ]);

        // Save the state change to history
        $device->saveToHistory();

        return response()->json([
            'status' => 'success',
            'message' => 'Device state updated successfully',
            'device' => $device,
        ]);
    }

    /**
     * Get actuator status (on/off)
     *
     * @param string $id Device ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActuatorStatus($id)
    {
        // Find the device by ID
        $device = DeviceData::where('device_id', $id)
            ->where('device_type', 'actuator')
            ->first();

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Actuator device not found'
            ], 404);
        }

        // Extract the on/off status from the payload
        $status = [
            'device_id' => $device->device_id,
            'is_online' => $device->is_online,
            'status' => isset($device->payload['on']) ? $device->payload['on'] : false
        ];

        return response()->json([
            'status' => 'success',
            'actuator' => $status,
        ]);
    }

    /**
     * Get all actuator devices with their on/off status
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllActuators()
    {
        // Find all devices with type 'actuator'
        $actuators = DeviceData::where('device_type', 'actuator')
            ->get();

        if ($actuators->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No actuator devices found',
                'actuators' => []
            ]);
        }

        // Format the response data
        $formattedActuators = $actuators->map(function ($device) {
            return [
                'device_id' => $device->device_id,
                'name' => $device->name,
                'is_online' => $device->is_online,
                'status' => isset($device->payload['on']) ? $device->payload['on'] : false,
                'last_updated' => $device->updated_at
            ];
        });

        return response()->json([
            'status' => 'success',
            'count' => $actuators->count(),
            'actuators' => $formattedActuators,
        ]);
    }
}