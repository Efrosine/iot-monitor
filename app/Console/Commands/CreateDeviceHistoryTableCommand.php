<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use App\Models\DeviceData;

class CreateDeviceHistoryTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'device:create-history-table {device_id : The ID of the device}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a history table for a specific device';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deviceId = $this->argument('device_id');
        $tableName = DeviceData::getHistoryTableName($deviceId);

        if (Schema::hasTable($tableName)) {
            $this->info("Table {$tableName} already exists.");
            return;
        }

        $this->info("Creating history table for device: {$deviceId}");

        // Use the model method to create the table for consistency
        DeviceData::createHistoryTableIfNotExists($deviceId);

        $this->info("Table {$tableName} created successfully.");
    }
}