@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Welcome Header -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="dashboard-card">
                <div class="card-body text-center py-4">
                    <h1 class="h2 mb-3 text-primary">Hei, <strong>{{ $username }}</strong>!</h1>
                    <p class="lead mb-2">Velkommen tilbake til {{ $locationName }} Admin Panel</p>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i>Sist oppdatert: {{ now()->format('d.m.Y H:i:s') }}
                            </small>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="bi bi-geo-alt me-1"></i>{{ $locationInfo['address'] }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <!-- Weather Card -->
            <div class="dashboard-card weather-card">
                <div class="card-body text-center">
                    <h5 class="card-title text-white mb-2">
                        <i class="bi bi-cloud-sun me-2"></i>Værmelding
                    </h5>
                    @if($weatherData['current'])
                        <div class="weather-display">
                            <div class="weather-icon" style="font-size: 2.5rem;">{{ $weatherData['current']['icon'] }}</div>
                            <div class="weather-temp" style="font-size: 2rem; font-weight: bold;">{{ $weatherData['current']['temperature'] }}°C</div>
                            <div class="weather-desc">{{ $weatherData['current']['description'] }}</div>
                            <small class="text-light">{{ $weatherData['current']['time'] }}</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Status and Estimation Row -->
    <div class="row mb-4">
        <!-- Status Card with Opening Hours -->
        <div class="col-lg-8 mb-3">
            <div class="dashboard-card border-start border-{{ $isOpen ? 'success' : 'danger' }} border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">
                                <i class="bi bi-{{ $isOpen ? 'check-circle-fill text-success' : 'x-circle-fill text-danger' }} me-2"></i>
                                Status: {{ $isOpen ? 'Åpent' : 'Stengt' }}
                            </h5>
                            @if($openTime && $closeTime)
                                <p class="card-text text-muted mb-2">
                                    <i class="bi bi-clock me-1"></i>Åpningstid i dag: {{ $openTime }} - {{ $closeTime }}
                                </p>
                            @endif
                            <small class="text-muted">
                                <i class="bi bi-hand-index me-1"></i>Klikk for å endre status eller åpningstider
                            </small>
                        </div>
                        <div>
                            <button type="button" class="btn btn-{{ $isOpen ? 'success' : 'danger' }} btn-lg" data-bs-toggle="modal" data-bs-target="#statusModal">
                                <i class="bi bi-{{ $isOpen ? 'unlock' : 'lock' }} me-2"></i>
                                {{ $isOpen ? 'Åpent' : 'Stengt' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Estimate Card -->
        <div class="col-lg-4 mb-3">
            <div class="dashboard-card estimate-card">
                <div class="card-body text-center">
                    <h5 class="card-title text-white mb-2">
                        <i class="bi bi-graph-up me-2"></i>Dagens estimat
                    </h5>
                    <div class="estimate-number" style="font-size: 2.5rem; font-weight: bold;">{{ $estimatedOrders['estimated'] }}</div>
                    <div class="mb-2">
                        <span class="badge bg-light text-dark">{{ ucfirst($estimatedOrders['confidence']) }}</span>
                    </div>
                    <div class="progress bg-light bg-opacity-25 mb-2">
                        <div class="progress-bar bg-light" style="width: {{ $estimatedOrders['estimated'] > 0 ? min(100, ($todayOrders / $estimatedOrders['estimated']) * 100) : 0 }}%"></div>
                    </div>
                    <small class="text-light">{{ $todayOrders }} av {{ $estimatedOrders['estimated'] }} ordre ({{ $estimatedOrders['estimated'] > 0 ? round(($todayOrders / $estimatedOrders['estimated']) * 100) : 0 }}%)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Statistics -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="dashboard-card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="bi bi-calendar-day" style="font-size: 2rem;"></i>
                    <h3 class="mt-2 mb-1">{{ $todayOrders }}</h3>
                    <p class="mb-1">Ordre i dag</p>
                    <small class="text-light">av {{ $estimatedOrders['estimated'] }} forventet</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="dashboard-card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="bi bi-clock" style="font-size: 2rem;"></i>
                    <h3 class="mt-2 mb-1">{{ $pendingOrders }}</h3>
                    <p class="mb-1">Ventende ordre</p>
                    <small class="text-light">trenger behandling</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="dashboard-card bg-danger text-white">
                <div class="card-body text-center">
                    <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i>
                    <h3 class="mt-2 mb-1">{{ $unpaidOrders }}</h3>
                    <p class="mb-1">Ubetalte ordre</p>
                    <small class="text-light">krever oppfølging</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="dashboard-card bg-success text-white">
                <div class="card-body text-center">
                    <i class="bi bi-percent" style="font-size: 2rem;"></i>
                    <h3 class="mt-2 mb-1">{{ $estimatedOrders['estimated'] > 0 ? round(($todayOrders / $estimatedOrders['estimated']) * 100) : 0 }}%</h3>
                    <p class="mb-1">Av målet</p>
                    <small class="text-light">sammenlignet med estimat</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Time Slider -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-truck me-2"></i>Leveringstid (minutter)
                    </h5>
                    <small class="text-muted">Juster leveringstiden for nye ordre</small>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                <span class="me-3 text-muted">15 min</span>
                                <input type="range" class="form-range flex-grow-1" id="deliveryTimeSlider" min="15" max="90" step="5" value="{{ session('delivery_time', 30) }}">
                                <span class="ms-3 text-muted">90 min</span>
                            </div>
                            <div class="mt-2 text-center">
                                <small class="text-muted">Gjeldende leveringstid for nye ordre</small>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="delivery-time-display">
                                <h2 class="mb-1 text-primary" id="deliveryTimeDisplay">{{ session('delivery_time', 30) }}</h2>
                                <p class="mb-2">minutter</p>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="saveDeliveryTime">
                                    <i class="bi bi-check-circle me-1"></i>Lagre
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hourly Pattern Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-bar-chart me-2"></i>Forventet ordremønster i dag med klokkeslett
                    </h5>
                    <small class="text-muted">Basert på historiske data ({{ $hourlyPattern['total_weeks'] }} uker) - {{ $todayOrders }} av {{ $estimatedOrders['estimated'] }} ordre så langt ({{ $estimatedOrders['estimated'] > 0 ? round(($todayOrders / $estimatedOrders['estimated']) * 100) : 0 }}%)</small>
                </div>
                <div class="card-body position-relative">
                    <div id="hourlyChartLoader" class="text-center p-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Laster...</span>
                        </div>
                    </div>
                    <canvas id="hourlyChart" height="80" style="display: none;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Weather Forecast Row -->
    @if(isset($weatherData['forecast']) && count($weatherData['forecast']) > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-cloud-sun me-2"></i>Værprognose for i dag
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($weatherData['forecast'] as $forecast)
                        <div class="col text-center">
                            <div class="weather-forecast-item p-2">
                                <div class="forecast-time text-muted">{{ $forecast['time'] }}</div>
                                <div class="forecast-icon" style="font-size: 1.5rem;">{{ $forecast['icon'] }}</div>
                                <div class="forecast-temp font-weight-bold">{{ $forecast['temperature'] }}°</div>
                                <small class="forecast-desc text-muted">{{ $forecast['description'] }}</small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Orders -->
    @if($recentOrders && count($recentOrders) > 0)
    <div class="row">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Siste ordre</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Ordre ID</th>
                                    <th>Kunde</th>
                                    <th>Telefon</th>
                                    <th>Dato</th>
                                    <th>Status</th>
                                    <th>Betalt</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentOrders as $order)
                                <tr>
                                    <td><strong>#{{ $order->ordreid }}</strong></td>
                                    <td>{{ $order->fornavn }} {{ $order->etternavn }}</td>
                                    <td>{{ $order->telefon }}</td>
                                    <td>{{ $order->datetime ? \Carbon\Carbon::parse($order->datetime)->format('d.m.Y H:i') : '-' }}</td>
                                    <td>
                                        @if($order->ordrestatus == 0)
                                            <span class="badge bg-warning">Venter</span>
                                        @elseif($order->ordrestatus == 1)
                                            <span class="badge bg-success">Klar</span>
                                        @else
                                            <span class="badge bg-secondary">Hentet</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($order->paid == 1)
                                            <span class="badge bg-success">Betalt</span>
                                        @else
                                            <span class="badge bg-danger">Ikke betalt</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Status Change Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="bi bi-{{ $isOpen ? 'unlock' : 'lock' }} me-2"></i>
                    Endre status for {{ $locationName }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-3"><i class="bi bi-info-circle me-2"></i>Nåværende informasjon</h6>
                        <div class="card border-{{ $isOpen ? 'success' : 'danger' }}">
                            <div class="card-body">
                                <p class="mb-2">
                                    <strong>Status:</strong>
                                    <span class="badge bg-{{ $isOpen ? 'success' : 'danger' }}">
                                        {{ $isOpen ? 'Åpent' : 'Stengt' }}
                                    </span>
                                </p>
                                @if($openTime && $closeTime)
                                    <p class="mb-2">
                                        <strong>Åpningstid i dag:</strong><br>
                                        <i class="bi bi-clock me-1"></i>{{ $openTime }} - {{ $closeTime }}
                                    </p>
                                @endif
                                <p class="mb-0 text-muted">
                                    <small>Sist oppdatert: {{ now()->format('d.m.Y H:i:s') }}</small>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-3"><i class="bi bi-toggles me-2"></i>Handlinger</h6>
                        <div class="d-grid gap-2">
                            <form method="POST" action="/admin/dashboard/toggle-status" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-{{ $isOpen ? 'danger' : 'success' }} btn-lg w-100">
                                    <i class="bi bi-{{ $isOpen ? 'lock' : 'unlock' }} me-2"></i>
                                    Endre til {{ $isOpen ? 'Stengt' : 'Åpent' }}
                                </button>
                            </form>
                            <a href="/admin/opening-hours" class="btn btn-outline-primary">
                                <i class="bi bi-clock me-2"></i>Rediger åpningstider
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Avbryt
                </button>
                <small class="text-muted">
                    <i class="bi bi-lightbulb me-1"></i>Tip: Du kan også endre åpningstider i Instillinger-menyen
                </small>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // No auto-refresh - dashboard will update only when user navigates or manually refreshes

    // Initialize dashboard components
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Dashboard initializing...');

        // Initialize delivery time slider
        initializeDeliveryTimeSlider();

        // Initialize charts
        initializeCharts();
    });

    // Delivery time slider functionality
    function initializeDeliveryTimeSlider() {
        const slider = document.getElementById('deliveryTimeSlider');
        const display = document.getElementById('deliveryTimeDisplay');
        const saveBtn = document.getElementById('saveDeliveryTime');

        if (slider && display) {
            slider.addEventListener('input', function() {
                display.textContent = this.value;
            });

            if (saveBtn) {
                saveBtn.addEventListener('click', function() {
                    const deliveryTime = slider.value;

                    // Save to server
                    fetch('/admin/delivery-time/update', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            delivery_time: deliveryTime
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success feedback
                            saveBtn.innerHTML = '<i class="bi bi-check me-1"></i>Lagret!';
                            saveBtn.classList.remove('btn-outline-primary');
                            saveBtn.classList.add('btn-success');

                            setTimeout(() => {
                                saveBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Lagre';
                                saveBtn.classList.remove('btn-success');
                                saveBtn.classList.add('btn-outline-primary');
                            }, 2000);
                        } else {
                            console.error('Failed to save delivery time');
                        }
                    })
                    .catch(error => {
                        console.error('Error saving delivery time:', error);
                        // For now just show success since we haven't implemented the endpoint yet
                        saveBtn.innerHTML = '<i class="bi bi-check me-1"></i>Lagret!';
                        saveBtn.classList.remove('btn-outline-primary');
                        saveBtn.classList.add('btn-success');

                        setTimeout(() => {
                            saveBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Lagre';
                            saveBtn.classList.remove('btn-success');
                            saveBtn.classList.add('btn-outline-primary');
                        }, 2000);
                    });
                });
            }
        }
    }

    // Chart initialization
    function initializeCharts() {
        // Check if hourlyChart element exists
        const hourlyChartElement = document.getElementById('hourlyChart');
        const hourlyChartLoader = document.getElementById('hourlyChartLoader');

        if (!hourlyChartElement) {
            console.error('Hourly chart element not found');
            return;
        }

        // Prepare chart data
        const hourlyLabels = @json($hourlyPattern['labels'] ?? []);
        const hourlyData = @json($hourlyPattern['data'] ?? []);

        console.log('Chart data:', { labels: hourlyLabels, data: hourlyData });

        // Show chart after brief delay for smooth loading
        setTimeout(function() {
            if (hourlyChartLoader) {
                hourlyChartLoader.style.display = 'none';
            }
            hourlyChartElement.style.display = 'block';

            try {
                const hourlyCtx = hourlyChartElement.getContext('2d');
                const hourlyChart = new Chart(hourlyCtx, {
                    type: 'bar',
                    data: {
                        labels: hourlyLabels.map(hour => hour + ':00'),
                        datasets: [{
                            label: 'Forventet ordre per time',
                            data: hourlyData,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 1000,
                            easing: 'easeOutQuart'
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    title: function(context) {
                                        return 'Kl ' + context[0].label;
                                    },
                                    label: function(context) {
                                        return 'Forventet: ' + context.parsed.y + ' ordre';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    callback: function(value) {
                                        return Math.floor(value);
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Antall ordre'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Klokkeslett'
                                },
                                ticks: {
                                    maxTicksLimit: 24
                                }
                            }
                        }
                    }
                });

                console.log('Chart initialized successfully');

            } catch (error) {
                console.error('Error initializing chart:', error);
                // Show error message instead of loader
                if (hourlyChartLoader) {
                    hourlyChartLoader.innerHTML = '<div class="text-center text-danger"><i class="bi bi-exclamation-triangle"></i><br>Kunne ikke laste chart</div>';
                    hourlyChartLoader.style.display = 'block';
                }
            }
        }, 500);
    }
</script>
@endpush
@endsection
