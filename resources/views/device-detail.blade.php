<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Details - {{ $device->name ?? $device->device_id }}</title>

    <!-- Tailwind CSS with daisyUI -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <!-- Chart.js for visualizing data history -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Function to update device data every 1 second
        function updateDeviceData() {
            const deviceId = document.getElementById('device-container').getAttribute('data-device-id');

            fetch(`/api/device-data/${deviceId}`)
                .then(response => response.json())
                .then(data => {
                    // Update status indicator
                    const statusIndicator = document.querySelector('.status');
                    if (data.is_online) {
                        statusIndicator.classList.remove('status-error');
                        statusIndicator.classList.add('status-success');
                        document.getElementById('device-status').textContent = 'Online';
                    } else {
                        statusIndicator.classList.remove('status-success');
                        statusIndicator.classList.add('status-error');
                        document.getElementById('device-status').textContent = 'Offline';
                    }

                    // Update last seen time
                    const lastSeenDate = new Date(data.last_seen_at);
                    document.getElementById('last-seen').textContent = lastSeenDate.toLocaleString();

                    // Update device data
                    const dataTable = document.getElementById('device-data-table');
                    const tbody = dataTable.querySelector('tbody');
                    tbody.innerHTML = '';

                    const parsedData = JSON.parse(data.data);
                    Object.entries(parsedData).forEach(([key, value]) => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${key}</td>
                            <td>${value}</td>
                        `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => console.error('Error fetching device data:', error));
        }

        // Start periodic updates when the page loads
        document.addEventListener('DOMContentLoaded', () => {
            updateDeviceData();
            setInterval(updateDeviceData, 1000); // Update every 1 second
            initializeCharts();
        });

        // Initialize charts for history visualization
        function initializeCharts() {
            const chartData = @json($history);
            if (!chartData || chartData.length === 0) return;

            // Extract all possible sensor keys from the first history entry
            const firstDataPoint = JSON.parse(chartData[0].data);
            const sensorKeys = Object.keys(firstDataPoint);

            // Prepare data for charts
            const timeLabels = chartData.map(entry => {
                const date = new Date(entry.recorded_at);
                return date.toLocaleTimeString();
            }).reverse();

            // Create separate charts for each sensor type
            sensorKeys.forEach(key => {
                createChart(key, timeLabels, chartData, key);
            });
        }

        function createChart(elementId, labels, chartData, dataKey) {
            // Create a container for the chart
            const chartContainer = document.createElement('div');
            chartContainer.className = 'card bg-base-100 shadow-xl mb-6';
            chartContainer.innerHTML = `
                <div class="card-body">
                    <h2 class="card-title">${dataKey} History</h2>
                    <canvas id="chart-${elementId}"></canvas>
                </div>
            `;

            // Add the container to the charts section
            document.getElementById('history-charts').appendChild(chartContainer);

            // Extract data values for this specific sensor
            const values = chartData.map(entry => {
                const data = JSON.parse(entry.data);
                return data[dataKey] || null;
            }).reverse();

            // Create the chart
            const ctx = document.getElementById(`chart-${elementId}`).getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: dataKey,
                        data: values,
                        borderColor: 'hsl(var(--p))',
                        backgroundColor: 'rgba(var(--p), 0.1)',
                        tension: 0.1,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: false
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });
        }
    </script>
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

    <div class="container mx-auto px-4 py-8" id="device-container" data-device-id="{{ $device->device_id }}">
        <div class="flex items-center mb-6">
            <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-sm mr-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>
            <h1 class="text-2xl font-bold">{{ $device->name ?? 'Device ' . $device->device_id }}</h1>
            <div class="status {{ $device->is_online ? 'status-success' : 'status-error' }} status-md ml-2"></div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Device Info Card -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">Device Information</h2>
                    <div class="overflow-x-auto">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <th>Device ID</th>
                                    <td>{{ $device->device_id }}</td>
                                </tr>
                                <tr>
                                    <th>Name</th>
                                    <td>{{ $device->name ?? 'Not set' }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td id="device-status">{{ $device->is_online ? 'Online' : 'Offline' }}</td>
                                </tr>
                                <tr>
                                    <th>Last Seen</th>
                                    <td id="last-seen">
                                        {{ \Carbon\Carbon::parse($device->last_seen_at)->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>First Registered</th>
                                    <td>{{ \Carbon\Carbon::parse($device->created_at)->format('Y-m-d H:i:s') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Current Data Card -->
            <div class="card bg-base-100 shadow-xl lg:col-span-2">
                <div class="card-body">
                    <h2 class="card-title">Current Sensor Data</h2>
                    <div class="overflow-x-auto">
                        <table class="table" id="device-data-table">
                            <thead>
                                <tr>
                                    <th>Sensor</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (json_decode($device->data, true) as $key => $value)
                                    <tr>
                                        <td>{{ $key }}</td>
                                        <td>{{ $value }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Charts Section -->
        <h2 class="text-xl font-bold mt-8 mb-4">Sensor History (Last Hour)</h2>
        @if ($history->isEmpty())
            <div class="alert alert-info">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    class="stroke-current shrink-0 w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>No history data available for this device yet. It will start collecting after the device has been
                    online for a minute.</span>
            </div>
        @else
            <div id="history-charts" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Charts will be dynamically inserted here by JavaScript -->
            </div>
        @endif
    </div>
</body>

</html>