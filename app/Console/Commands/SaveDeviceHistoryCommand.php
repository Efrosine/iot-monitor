<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Carbon;

class SaveDeviceHistoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'device:save-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save the current state of all devices to their respective history tables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // Get all devices from the device_data table
        $devices = DB::table('device_data')->get();

        if ($devices->isEmpty()) {
            $this->info('No devices found.');
            return;
        }

        $this->info('Saving history for ' . $devices->count() . ' devices...');

        foreach ($devices as $device) {
            $historyTableName = "device_{$device->device_id}_histories";

            // Create history table if it doesn't exist
            if (!Schema::hasTable($historyTableName)) {
                Artisan::call('device:create-history-table', [
                    'device_id' => $device->device_id
                ]);
            }

            // Insert current device data into history table
            DB::table($historyTableName)->insert([
                'data' => $device->data, // Already JSON-encoded
                'recorded_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->info('Device history saved successfully at ' . $now);
    }
}