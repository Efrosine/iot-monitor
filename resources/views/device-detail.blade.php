<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $device->name ?? 'Device ' . $device->device_id }} - Details | IoT Monitor</title>

    <!-- Tailwind CSS with daisyUI -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="min-h-screen bg-base-200">
    <div class="navbar bg-base-100 shadow-md">
        <div class="flex-1">
            <a href="/" class="btn btn-ghost text-xl">IoT Device Monitor</a>
        </div>
        <div class="flex-none">
            <a href="/dashboard" class="btn btn-ghost">Dashboard</a>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="flex items-center gap-2 mb-6">
            <a href="/dashboard" class="btn btn-ghost btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Back
            </a>
            <h1 class="text-2xl font-bold">
                {{ $device->name ?? 'Device ' . $device->device_id }}
                <span class="status {{ $device->is_online ? 'status-success' : 'status-error' }} status-md"></span>
            </h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Device Info Card -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mb-4">Device Information</h2>
                    <div class="overflow-x-auto">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td class="font-medium">Device ID</td>
                                    <td>{{ $device->device_id }}</td>
                                </tr>
                                <tr>
                                    <td class="font-medium">Name</td>
                                    <td>{{ $device->name ?? 'Unnamed Device' }}</td>
                                </tr>
                                <tr>
                                    <td class="font-medium">Status</td>
                                    <td>
                                        <div class="badge {{ $device->is_online ? 'badge-success' : 'badge-error' }}">
                                            {{ $device->is_online ? 'Online' : 'Offline' }}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-medium">Device Type</td>
                                    <td>
                                        <div class="badge {{ $device->device_type === 'sensor' ? 'badge-info' : 'badge-warning' }}">
                                            {{ ucfirst($device->device_type ?? 'sensor') }}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-medium">Last Update</td>
                                    <td>{{ \Carbon\Carbon::parse($device->updated_at)->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                @foreach ($device->payload as $key => $value)
                                    <tr>
                                        <td class="font-medium">{{ ucfirst($key) }}</td>
                                        <td>{{ $value }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Charts/Controls Card -->
            <div class="card bg-base-100 shadow-xl lg:col-span-2">
                <div class="card-body">
                    <h2 class="card-title mb-4">
                        @if ($device->device_type === 'sensor')
                            Data History
                        @else
                            Device Controls
                        @endif
                    </h2>

                    <div class="flex flex-col gap-8 h-96 overflow-auto">
                        @if ($device->device_type === 'sensor')
                            <!-- Sensor Charts -->
                            @foreach ($device->payload as $key => $value)
                                <div>
                                    <h3 class="font-medium text-base mb-2">{{ ucfirst($key) }}</h3>
                                    <div class="h-64">
                                        <canvas id="chart-{{ $key }}"></canvas>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <!-- Actuator Controls -->
                            <div class="flex flex-col gap-6">
                                @foreach ($device->payload as $key => $value)
                                    <div class="card bg-base-200 p-4">
                                        <div class="flex items-center justify-between">
                                            <h3 class="font-medium text-lg">{{ ucfirst($key) }}</h3>
                                            <label class="swap">
                                                <input type="checkbox" class="toggle toggle-primary toggle-lg actuator-control" 
                                                    data-device="{{ $device->device_id }}" 
                                                    data-key="{{ $key }}" 
                                                    {{ $value == 'on' ? 'checked' : '' }}/>
                                                <div class="swap-on text-success font-medium ml-2">ON</div>
                                                <div class="swap-off text-error font-medium ml-2">OFF</div>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- History Table -->
        <div class="card bg-base-100 shadow-xl mt-6">
            <div class="card-body">
                <h2 class="card-title mb-4">History Log</h2>
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Type</th>
                                @foreach ($device->payload as $key => $value)
                                    <th>{{ ucfirst($key) }}</th>
                                @endforeach
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($history as $entry)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($entry->recorded_at)->format('Y-m-d H:i:s') }}</td>
                                    <td>
                                        <div class="badge {{ $entry->device_type === 'sensor' ? 'badge-info' : 'badge-warning' }}">
                                            {{ ucfirst($entry->device_type ?? 'sensor') }}
                                        </div>
                                    </td>
                                    @foreach (json_decode($entry->payload, true) as $value)
                                        <td>{{ $value }}</td>
                                    @endforeach
                                    <td>
                                        <div class="badge {{ $entry->is_online ? 'badge-success' : 'badge-error' }}">
                                            {{ $entry->is_online ? 'Online' : 'Offline' }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Store charts globally
        let chartInstances = {};

        // Helper function to capitalize first letter
        function ucfirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        // Parse history data for charts
        const historyData = @json($history);
        const deviceType = "{{ $device->device_type }}";

        // Initialize charts when page loads
        document.addEventListener('DOMContentLoaded', () => {
            const payload = @json($device->payload);
            const keys = Object.keys(payload);

            // For sensor devices, create charts
            if (deviceType === 'sensor') {
                // Create all charts
                keys.forEach(key => {
                    const ctx = document.getElementById('chart-' + key).getContext('2d');

                    // Reverse history data for charts to show most recent data on the right
                    const reversedHistory = [...historyData].reverse();

                    const labels = reversedHistory.map(entry => {
                        const date = new Date(entry.recorded_at);
                        return date.toLocaleTimeString();
                    });

                    const data = reversedHistory.map(entry => {
                        const payload = JSON.parse(entry.payload);
                        return payload[key];
                    });

                    // Create the chart instance
                    chartInstances[key] = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: ucfirst(key),
                                data: data,
                                borderColor: key === 'temp' ? 'rgb(255, 99, 132)' : 'rgb(75, 192, 192)',
                                tension: 0.1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: false
                                }
                            }
                        }
                    });
                });
            } 
            // For actuator devices, add event listeners to toggle controls
            else {
                const toggleControls = document.querySelectorAll('.actuator-control');
                toggleControls.forEach(control => {
                    control.addEventListener('change', function(e) {
                        const deviceId = this.dataset.device;
                        const key = this.dataset.key;
                        const value = this.checked ? 'on' : 'off';
                        
                        // Create payload with the current state
                        const payload = {};
                        payload[key] = value;
                        
                        // Send the update to the server
                        fetch(`/api/device-data/${deviceId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                device_id: deviceId,
                                payload: payload,
                                device_type: 'actuator'
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                console.log('Successfully updated device state');
                            } else {
                                console.error('Failed to update device state');
                                // Revert the toggle if the update failed
                                this.checked = !this.checked;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            // Revert the toggle if there was an error
                            this.checked = !this.checked;
                        });
                    });
                });
            }
        });
    </script>
</body>

</html>