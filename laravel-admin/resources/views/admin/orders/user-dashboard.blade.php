@extends('layouts.admin')

@section('content')
<div class="container-fluid px-2">
    <!-- Header with location info and controls -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h4 class="mb-0">{{ $locationName }}</h4>
                            <small>{{ now()->format('d. F Y') }}</small>
                        </div>
                        <div class="col-6 text-end">
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input" type="checkbox" id="locationStatus"
                                       {{ $isOpen ? 'checked' : '' }} onchange="toggleLocationStatus()">
                                <label class="form-check-label text-white" for="locationStatus">
                                    {{ $isOpen ? 'ÅPEN' : 'STENGT' }}
                                </label>
                            </div>
                            <button class="btn btn-sm btn-light ms-2" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Status Tabs -->
    <ul class="nav nav-tabs mb-3" id="orderTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active position-relative" id="new-orders-tab" data-bs-toggle="tab" data-bs-target="#new-orders" type="button">
                NYE ORDRER
                @if($newOrders->count() > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        {{ $newOrders->count() }}
                    </span>
                @endif
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link position-relative" id="ready-orders-tab" data-bs-toggle="tab" data-bs-target="#ready-orders" type="button">
                KLAR FOR HENTING
                @if($readyOrders->count() > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                        {{ $readyOrders->count() }}
                    </span>
                @endif
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="completed-orders-tab" data-bs-toggle="tab" data-bs-target="#completed-orders" type="button">
                HENTET ({{ $completedOrders->count() }})
            </button>
        </li>
    </ul>

    <!-- Order Content -->
    <div class="tab-content" id="orderTabContent">
        <!-- New Orders -->
        <div class="tab-pane fade show active" id="new-orders" role="tabpanel">
            @if($newOrders->count() > 0)
                <div class="row g-3">
                    @foreach($newOrders as $order)
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-warning h-100">
                                <div class="card-header bg-warning text-dark">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">#{{ $order->ordreid }}</h5>
                                        <span class="badge bg-dark">{{ $order->datetime->format('H:i') }}</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title">{{ $order->fornavn }} {{ $order->etternavn }}</h6>
                                    <p class="mb-1"><i class="fas fa-phone me-1"></i>{{ $order->telefon }}</p>
                                    <p class="mb-2"><i class="fas fa-clock me-1"></i>Henting: {{ $order->hentetid ?? 'Ikke spesifisert' }}</p>
                                    
                                    <!-- Order items preview -->
                                    <div class="order-items mb-3">
                                        <small class="text-muted">{{ $order->ekstrainfo }}</small>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-success" onclick="markOrderReady({{ $order->id }})">
                                            <i class="fas fa-check me-1"></i>KLAR FOR HENTING
                                        </button>
                                        <button class="btn btn-outline-primary btn-sm" onclick="showOrderDetails({{ $order->id }})">
                                            <i class="fas fa-eye me-1"></i>Se detaljer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h4>Ingen nye ordrer</h4>
                    <p class="text-muted">Nye ordrer vil vises her automatisk</p>
                </div>
            @endif
        </div>

        <!-- Ready Orders -->
        <div class="tab-pane fade" id="ready-orders" role="tabpanel">
            @if($readyOrders->count() > 0)
                <div class="row g-3">
                    @foreach($readyOrders as $order)
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-info h-100">
                                <div class="card-header bg-info text-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">#{{ $order->ordreid }}</h5>
                                        <span class="badge bg-white text-dark">
                                            @if($order->sms)
                                                SMS SENDT
                                            @else
                                                VENTER
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title">{{ $order->fornavn }} {{ $order->etternavn }}</h6>
                                    <p class="mb-1"><i class="fas fa-phone me-1"></i>{{ $order->telefon }}</p>
                                    <p class="mb-2"><i class="fas fa-clock me-1"></i>Klar siden: {{ $order->updated_at->format('H:i') }}</p>
                                    
                                    <div class="d-grid gap-2">
                                        @if(!$order->sms)
                                            <button class="btn btn-warning" onclick="sendReadySMS({{ $order->id }})">
                                                <i class="fas fa-sms me-1"></i>SEND SMS
                                            </button>
                                        @endif
                                        <button class="btn btn-success" onclick="markOrderCompleted({{ $order->id }})">
                                            <i class="fas fa-check-double me-1"></i>HENTET
                                        </button>
                                        <button class="btn btn-outline-primary btn-sm" onclick="showOrderDetails({{ $order->id }})">
                                            <i class="fas fa-eye me-1"></i>Se detaljer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-clock fa-3x text-info mb-3"></i>
                    <h4>Ingen ordrer klar for henting</h4>
                </div>
            @endif
        </div>

        <!-- Completed Orders -->
        <div class="tab-pane fade" id="completed-orders" role="tabpanel">
            @if($completedOrders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Ordre</th>
                                <th>Kunde</th>
                                <th>Telefon</th>
                                <th>Hentet</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($completedOrders as $order)
                                <tr>
                                    <td>#{{ $order->ordreid }}</td>
                                    <td>{{ $order->fornavn }} {{ $order->etternavn }}</td>
                                    <td>{{ $order->telefon }}</td>
                                    <td>{{ $order->updated_at->format('H:i') }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="showOrderDetails({{ $order->id }})">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <p class="text-muted">Ingen hentede ordrer i dag</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ordre detaljer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Laster...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-refresh every 60 seconds
setInterval(function() {
    location.reload();
}, 60000);

// Update last refresh time
function updateLastRefresh() {
    document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString('no-NO');
}

// Toggle location status
function toggleLocationStatus() {
    const checkbox = document.getElementById('locationStatus');
    
    fetch('{{ route('admin.dashboard.toggle-status') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            status: checkbox.checked ? 1 : 0
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Kunne ikke oppdatere status');
            checkbox.checked = !checkbox.checked;
        }
    });
}

// Mark order as ready
function markOrderReady(orderId) {
    if (confirm('Marker ordre som klar for henting?')) {
        fetch(`/admin/orders/${orderId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                status: 1
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Feil: ' + data.message);
            }
        });
    }
}

// Send ready SMS
function sendReadySMS(orderId) {
    if (confirm('Send SMS til kunden om at ordren er klar?')) {
        fetch(`/admin/orders/${orderId}/send-sms`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('SMS sendt!');
                location.reload();
            } else {
                alert('Feil: ' + data.message);
            }
        });
    }
}

// Mark order as completed
function markOrderCompleted(orderId) {
    if (confirm('Marker ordre som hentet?')) {
        fetch(`/admin/orders/${orderId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                status: 2
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Feil: ' + data.message);
            }
        });
    }
}

// Show order details
function showOrderDetails(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    modal.show();
    
    fetch(`/admin/orders/${orderId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('orderDetailsContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('orderDetailsContent').innerHTML = '<p class="text-danger">Kunne ikke laste ordre detaljer</p>';
        });
}

// Add sound notification for new orders
let lastOrderCount = {{ $newOrders->count() }};

function checkNewOrders() {
    fetch('/api/orders/count')
        .then(response => response.json())
        .then(data => {
            if (data.count > lastOrderCount) {
                // Play notification sound
                const audio = new Audio('/sounds/notification.mp3');
                audio.play();
                
                // Show browser notification if permitted
                if (Notification.permission === "granted") {
                    new Notification("Ny ordre!", {
                        body: "Det har kommet inn en ny ordre",
                        icon: "/favicon.ico"
                    });
                }
                
                // Refresh page
                setTimeout(() => location.reload(), 1000);
            }
            lastOrderCount = data.count;
        });
}

// Check for new orders every 10 seconds
setInterval(checkNewOrders, 10000);

// Request notification permission
if (Notification.permission === "default") {
    Notification.requestPermission();
}
</script>

<style>
.order-items {
    max-height: 60px;
    overflow-y: auto;
    font-size: 0.875rem;
}

.nav-tabs .nav-link {
    font-weight: bold;
}

.card {
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .card-body {
        padding: 0.75rem;
    }
    
    .btn {
        font-size: 0.875rem;
    }
}
</style>
@endpush
@endsection