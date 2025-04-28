/_
|--------------------------------------------------------------------------
| Context for GitHub Copilot
|--------------------------------------------------------------------------
| Project: IoT Device Monitoring Web App
| Framework: Laravel 11
| Description:
| - Handle real-time monitoring for multiple IoT devices.
| - Devices POST their latest data via API (JSON payload).
| - Server stores latest device data in 'device*data' table.
| - Every 1 second, frontend pulls latest device data via GET API (polling).
| - Every 1 minute, server saves a snapshot of each device's data into
| a dedicated history table, named by device_id (e.g., device_device001_histories).
|
| Tables:
| - device_data (stores latest data for each device)
| - device*{device_id}\_histories (dynamic tables to store per-minute history snapshots)
|
| API Endpoints:
| - POST /api/device-data => Receive and update device data
| - GET /api/device-data/{id} => Fetch latest device data
|
| Additional:
| - Auto-create history table if not exists when device first sends data.
| - Use JSON column to store flexible device payloads (temperature, humidity, etc.).
| - Use Laravel Scheduler to automate 1-minute snapshot saving.
|
| Focus:
| - Clean code
| - Minimal database query overhead
| - Dynamic table management
| - Scalable for hundreds of devices
_/
