<?php

namespace App\Http\Controllers;

use App\Models\DeviceData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with IoT device data
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get all devices with their latest data using Eloquent model
        $devices = DeviceData::select('device_id', 'name', 'device_type', 'payload', 'is_online', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('dashboard', [
            'devices' => $devices
        ]);
    }

    /**
     * Display detailed information for a specific device
     *
     * @param string $id Device ID
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        // Get the device data using the model
        $device = DeviceData::where('device_id', $id)->first();

        if (!$device) {
            abort(404, 'Device not found');
        }

        // Get recent history for this device using the model's static method
        $history = DeviceData::getHistory($id, 60); // Last 60 entries (about 1 hour if entries are per minute)

        return view('device-detail', [
            'device' => $device,
            'history' => $history
        ]);
    }
}