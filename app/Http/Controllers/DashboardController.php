<?php

namespace App\Http\Controllers;

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
        // Get all devices with their latest data
        $devices = DB::table('device_data')
            ->select('device_id', 'name', 'data', 'last_seen_at', 'is_online')
            ->orderBy('last_seen_at', 'desc')
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
        // Get the device data
        $device = DB::table('device_data')
            ->where('device_id', $id)
            ->first();

        if (!$device) {
            abort(404, 'Device not found');
        }

        // Get recent history for this device
        $historyTableName = "device_{$id}_histories";

        $history = [];
        if (DB::getSchemaBuilder()->hasTable($historyTableName)) {
            $history = DB::table($historyTableName)
                ->select('data', 'recorded_at')
                ->orderBy('recorded_at', 'desc')
                ->limit(60) // Last 60 entries (about 1 hour if entries are per minute)
                ->get();
        }

        return view('device-detail', [
            'device' => $device,
            'history' => $history
        ]);
    }
}