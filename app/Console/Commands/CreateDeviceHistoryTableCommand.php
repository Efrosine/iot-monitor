<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        $tableName = "device_{$deviceId}_histories";

        if (Schema::hasTable($tableName)) {
            $this->info("Table {$tableName} already exists.");
            return;
        }

        $this->info("Creating history table for device: {$deviceId}");

        Schema::create($tableName, function ($table) {
            $table->id();
            $table->json('data');
            $table->timestamp('recorded_at');
            $table->timestamps();

            // Add index on recorded_at for faster time-series queries
            $table->index('recorded_at');
        });

        $this->info("Table {$tableName} created successfully.");
    }
}