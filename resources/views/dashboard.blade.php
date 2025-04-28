<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IoT Device Monitor - Dashboard</title>

    <!-- Tailwind CSS with daisyUI -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <script>
        // Function to update device data every 1 second
        function updateDeviceData() {
            const deviceCards = document.querySelectorAll('[data-device-id]');

            deviceCards.forEach(card => {
                const deviceId = card.getAttribute('data-device-id');

                fetch(`/api/device-data/${deviceId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Update status indicator
                        const statusIndicator = card.querySelector('.status');
                        if (data.is_online) {
                            statusIndicator.classList.remove('status-error');
                            statusIndicator.classList.add('status-success');
                        } else {
                            statusIndicator.classList.remove('status-success');
                            statusIndicator.classList.add('status-error');
                        }

                        // Update last seen time
                        const lastSeen = card.querySelector('.last-seen');
                        const lastSeenDate = new Date(data.last_seen_at);
                        const timeString = lastSeenDate.toLocaleTimeString();
                        lastSeen.textContent = timeString;

                        // Update device data
                        const dataContainer = card.querySelector('.device-data');
                        dataContainer.innerHTML = '';

                        Object.entries(JSON.parse(data.data)).forEach(([key, value]) => {
                            const dataItem = document.createElement('div');
                            dataItem.classList.add('flex', 'justify-between', 'mb-1');
                            dataItem.innerHTML = `
                                <span class="text-sm opacity-80">${key}:</span>
                                <span class="text-sm font-semibold">${value}</span>
                            `;
                            dataContainer.appendChild(dataItem);
                        });
                    })
                    .catch(error => console.error('Error fetching device data:', error));
            });
        }

        // Start periodic updates when the page loads
        document.addEventListener('DOMContentLoaded', () => {
            updateDeviceData();
            setInterval(updateDeviceData, 1000); // Update every 1 second
        });
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

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Device Dashboard</h1>
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-title">Total Devices</div>
                    <div class="stat-value">{{ count($devices) }}</div>
                </div>
                <div class="stat">
                    <div class="stat-title">Online Devices</div>
                    <div class="stat-value text-success">
                        {{ $devices->where('is_online', true)->count() }}
                    </div>
                </div>
                <div class="stat">
                    <div class="stat-title">Offline Devices</div>
                    <div class="stat-value text-error">
                        {{ $devices->where('is_online', false)->count() }}
                    </div>
                </div>
            </div>
        </div>

        @if ($devices->isEmpty())
            <div class="alert alert-info">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    class="stroke-current shrink-0 w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>No devices found. Connect a device to get started.</span>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($devices as $device)
                    <div class="card bg-base-100 shadow-xl" data-device-id="{{ $device->device_id }}">
                        <div class="card-body">
                            <div class="flex justify-between items-center mb-2">
                                <h2 class="card-title">
                                    {{ $device->name ?? 'Device ' . $device->device_id }}
                                    <div class="status {{ $device->is_online ? 'status-success' : 'status-error' }} status-md">
                                    </div>
                                </h2>
                                <div class="badge badge-neutral">{{ $device->device_id }}</div>
                            </div>

                            <div class="text-sm opacity-70 mb-4">
                                Last seen: <span
                                    class="last-seen">{{ \Carbon\Carbon::parse($device->last_seen_at)->toTimeString() }}</span>
                            </div>

                            <div class="divider my-0"></div>

                            <div class="device-data">
                                @foreach (json_decode($device->data, true) as $key => $value)
                                    <div class="flex justify-between mb-1">
                                        <span class="text-sm opacity-80">{{ $key }}:</span>
                                        <span class="text-sm font-semibold">{{ $value }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="card-actions justify-end mt-4">
                                <a href="{{ route('device.detail', $device->device_id) }}" class="btn btn-primary btn-sm">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</body>

</html>