<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeviceData extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'device_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'device_id',
        'name',
        'device_type',
        'is_online',
        'payload',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_online' => 'boolean',
        'payload' => 'array',
    ];

    /**
     * Create history table for a device if it doesn't exist
     *
     * @param string $deviceId
     * @return void
     */
    public static function createHistoryTableIfNotExists(string $deviceId): void
    {
        $tableName = self::getHistoryTableName($deviceId);

        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function ($table) {
                $table->id();
                $table->json('payload')->nullable();
                $table->string('device_type')->default('sensor');
                $table->boolean('is_online')->default(true);
                $table->timestamp('recorded_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            });
        }
    }

    /**
     * Save snapshot of device data to history table
     *
     * @return bool
     */
    public function saveToHistory(): bool
    {
        $tableName = self::getHistoryTableName($this->device_id);

        // Create history table if not exists
        self::createHistoryTableIfNotExists($this->device_id);

        // Save data to history table
        return DB::table($tableName)->insert([
            'payload' => json_encode($this->payload),
            'device_type' => $this->device_type,
            'is_online' => $this->is_online,
            'recorded_at' => now(),
        ]);
    }

    /**
     * Get device history data
     *
     * @param string $deviceId
     * @param int $limit
     * @return array
     */
    public static function getHistory(string $deviceId, int $limit = 10): array
    {
        $tableName = self::getHistoryTableName($deviceId);

        if (!Schema::hasTable($tableName)) {
            return [];
        }

        return DB::table($tableName)
            ->orderBy('recorded_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get history table name for device
     *
     * @param string $deviceId
     * @return string
     */
    public static function getHistoryTableName(string $deviceId): string
    {
        return "device_{$deviceId}_histories";
    }
}