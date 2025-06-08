<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Aroi Admin') }} - Dashboard</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .status-card {
            border-left: 4px solid #007bff;
            transition: transform 0.2s;
        }
        .status-card:hover {
            transform: translateY(-2px);
        }
        .status-open {
            border-left-color: #28a745;
        }
        .status-closed {
            border-left-color: #dc3545;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-shop me-2"></i>Aroi Asia Admin - {{ $locationName }}
            </a>
            <div class="d-flex">
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-light">
                        <i class="bi bi-box-arrow-right me-1"></i>Logg ut
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Welcome Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card status-card">
                    <div class="card-body text-center">
                        <h1 class="h3 mb-3">Hei, <strong>{{ $username }}</strong>!</h1>
                        <p class="lead mb-0">Velkommen tilbake til {{ $locationName }} Admin Panel</p>
                        <small class="text-muted">
                            <i class="bi bi-clock me-1"></i>Sist oppdatert: {{ now()->format('d.m.Y H:i:s') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status and Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-8">
                <!-- Location Status -->
                <div class="card status-card {{ $isOpen ? 'status-open' : 'status-closed' }} mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-1">
                                    <i class="bi bi-{{ $isOpen ? 'check-circle-fill text-success' : 'x-circle-fill text-danger' }} me-2"></i>
                                    Status: {{ $isOpen ? 'Åpent' : 'Stengt' }}
                                </h5>
                                @if($openTime && $closeTime)
                                    <p class="card-text text-muted mb-0">
                                        Åpningstid i dag: {{ $openTime }} - {{ $closeTime }}
                                    </p>
                                @endif
                            </div>
                            <div>
                                <button type="button" class="btn btn-{{ $isOpen ? 'success' : 'danger' }}" data-bs-toggle="modal" data-bs-target="#statusModal">
                                    {{ $isOpen ? 'Åpent' : 'Stengt' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Statistics -->
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card status-card text-center">
                            <div class="card-body">
                                <i class="bi bi-calendar-day text-primary" style="font-size: 2rem;"></i>
                                <h3 class="mt-2 mb-1">{{ $todayOrders }}</h3>
                                <p class="text-muted mb-0">Ordre i dag</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card status-card text-center">
                            <div class="card-body">
                                <i class="bi bi-clock text-warning" style="font-size: 2rem;"></i>
                                <h3 class="mt-2 mb-1">{{ $pendingOrders }}</h3>
                                <p class="text-muted mb-0">Ventende ordre</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card status-card text-center">
                            <div class="card-body">
                                <i class="bi bi-exclamation-triangle text-danger" style="font-size: 2rem;"></i>
                                <h3 class="mt-2 mb-1">{{ $unpaidOrders }}</h3>
                                <p class="text-muted mb-0">Ubetalte ordre</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Quick Actions -->
                <div class="card status-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Hurtighandlinger</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="/admin/orders" class="btn btn-primary">
                                <i class="bi bi-list-ul me-2"></i>Se alle ordre
                            </a>
                            <a href="/profile" class="btn btn-secondary">
                                <i class="bi bi-person-gear me-2"></i>Rediger profil
                            </a>
                            <a href="/admin/dashboard" class="btn btn-dark">
                                <i class="bi bi-gear me-2"></i>Innstillinger
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        @if($recentOrders && count($recentOrders) > 0)
        <div class="row">
            <div class="col-12">
                <div class="card status-card">
                    <div class="card-header">
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
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Endre status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Vil du endre status for {{ $locationName }}?</p>
                    <p>Nåværende status: <strong>{{ $isOpen ? 'Åpent' : 'Stengt' }}</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                    <form method="POST" action="/admin/dashboard/toggle-status" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-{{ $isOpen ? 'danger' : 'success' }}">
                            {{ $isOpen ? 'Stengt' : 'Åpent' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Auto-refresh every 2 minutes like the old PHP system -->
    <script>
        setTimeout(function() {
            window.location.reload();
        }, 120000); // 2 minutes
    </script>
</body>
</html>
