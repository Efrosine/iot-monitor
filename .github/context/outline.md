/\_
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
| - Use Laravel Livewire for real-time web functionality, leveraging its polling feature to update the frontend every second with the latest device data.
| - Livewire simplifies the implementation of real-time updates without requiring extensive JavaScript, ensuring seamless integration with Laravel.
|
| Tables:
| - device_data (stores properties each device)
| - device*{device_id}\_histories (dynamic tables to store per-minute history snapshots)
|
| API Endpoints:
| - POST /api/device-data => Receive and update device data
| - GET /api/device-data/{id} => Fetch latest device data
|
| Additional:
| - Auto-create history table if not exists when device first sends data.
| - Use JSON column to store flexible device payloads (temperature, humidity, etc.).
| - Use Chart.js for visualizing history data, providing interactive and customizable charts for better data representation.
| Development Environment:
| - Using Laravel Sail for local development, providing a Docker-based environment for consistent and easy setup.

| Data:
| - Temperature Sensor Payload:
| - `temp`: Current temperature reading from the sensor.
| - `hum`: Current humidity reading from the sensor.

|
| Focus:
| - Clean code
| - Minimal database query overhead
| - Dynamic table management
| - Scalable for hundreds of devices
\_/
