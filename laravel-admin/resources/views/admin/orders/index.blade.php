@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Ordrer - {{ $locationName }}</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                <i class="fas fa-sync-alt me-1"></i>Oppdater
            </button>
        </div>
        <small class="text-muted">Siste oppdatering: <span id="lastUpdate">{{ now()->format('H:i:s') }}</span></small>
    </div>
</div>

<!-- Alert for orders missing kitchen print -->
@php
    $ordersWithoutPrint = $orders->filter(function($order) {
        return $order->paid && $order->wcstatus != 'completed';
    });
@endphp

@if($ordersWithoutPrint->count() > 0)
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <h5 class="alert-heading">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        ⚠️ {{ $ordersWithoutPrint->count() }} ordre(r) mangler kjøkkenprint!
    </h5>
    <p class="mb-2">
        <strong>Kritisk:</strong> Følgende ordre(r) har ikke blitt mottatt av PCKasse.
        Det automatiske kallet til PCKasse fra WooCommerce feilet, så kjøkkenet har IKKE fått ordre(ne).
    </p>
    <ul class="mb-2">
        @foreach($ordersWithoutPrint as $order)
            <li>
                <strong>Ordre #{{ $order->ordreid }}</strong> - {{ $order->full_name }}
                ({{ $order->datetime->diffForHumans() }})
                <button class="btn btn-sm btn-danger ms-2 sync-status-btn"
                        data-order-id="{{ $order->id }}"
                        onclick="event.preventDefault();">
                    <i class="bi bi-arrow-repeat me-1"></i>Prøv på nytt
                </button>
            </li>
        @endforeach
    </ul>
    <hr>
    <p class="mb-0">
        <i class="bi bi-info-circle me-1"></i>
        Systemet vil prøve å trigge PCKasse på nytt via QueueGetOrders.aspx og bekrefte at ordren mottas.
    </p>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>Alle</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Ventende</option>
                            <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Ubetalte</option>
                            <option value="not_sent" {{ request('status') == 'not_sent' ? 'selected' : '' }}>Ikke sendt til POS</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date" class="form-label">Dato</label>
                        <input type="date" name="date" id="date" class="form-control" value="{{ request('date') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="search" class="form-label">Søk</label>
                        <input type="text" name="search" id="search" class="form-control"
                               placeholder="Søk etter kunde, telefon eller ordre ID" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-1"></i>Søk
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                @if($orders->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ordre ID</th>
                                    <th>Kunde</th>
                                    <th>Telefon</th>
                                    <th>E-post</th>
                                    <th>Tidspunkt</th>
                                    <th>Hentetid</th>
                                    <th>Status</th>
                                    <th>Handlinger</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $order)
                                <tr class="order-card {{ $order->paid && $order->wcstatus != 'completed' ? 'table-danger' : '' }}">
                                    <td>
                                        <strong>#{{ $order->ordreid }}</strong>
                                        @if($order->paid && $order->wcstatus != 'completed')
                                            <span class="text-danger ms-1" title="MANGLER KJØKKENPRINT! PCKasse har ikke mottatt ordren.">
                                                <i class="bi bi-exclamation-triangle-fill"></i>
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $order->full_name }}</td>
                                    <td>{{ $order->telefon }}</td>
                                    <td>{{ $order->epost }}</td>
                                    <td>{{ $order->datetime->format('d.m.Y H:i') }}</td>
                                    <td>
                                        @if($order->hentes)
                                            <strong>{{ $order->hentes }}</strong>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <!-- Payment Status -->
                                        @if($order->paid)
                                            <span class="badge bg-success status-badge">Betalt</span>
                                        @else
                                            <span class="badge bg-danger status-badge">Ubetalt</span>
                                        @endif

                                        <!-- Order Status -->
                                        @if($order->ordrestatus == 0)
                                            <span class="badge bg-warning status-badge">Ventende</span>
                                        @elseif($order->ordrestatus == 1)
                                            <span class="badge bg-info status-badge">Under behandling</span>
                                        @elseif($order->ordrestatus == 2)
                                            <span class="badge bg-success status-badge">Klar</span>
                                        @elseif($order->ordrestatus == 3)
                                            <span class="badge bg-secondary status-badge">Fullført</span>
                                        @endif

                                        <!-- PCK Kitchen Print Status (WooCommerce = completed means PCK has printed) -->
                                        @if($order->wcstatus == 'completed')
                                            <span class="badge bg-success status-badge" title="PCKasse har mottatt og skrevet ut ordren i kjøkkenet">
                                                ✅ Kjøkkenprint OK
                                            </span>
                                        @else
                                            <span class="badge bg-danger status-badge" title="PCKasse har IKKE mottatt ordren - ingen kjøkkenprint!">
                                                ⚠️ Mangler kjøkkenprint
                                            </span>
                                        @endif

                                        <!-- WooCommerce Status Detail -->
                                        @if($order->wcstatus)
                                            @if($order->wcstatus == 'processing')
                                                <span class="badge bg-info status-badge" title="WooCommerce status">WC: Behandles</span>
                                            @elseif($order->wcstatus == 'pending')
                                                <span class="badge bg-warning status-badge" title="WooCommerce status">WC: Ventende</span>
                                            @elseif($order->wcstatus != 'completed')
                                                <span class="badge bg-secondary status-badge" title="WooCommerce status">WC: {{ $order->wcstatus }}</span>
                                            @endif
                                        @endif

                                        <!-- SMS Status -->
                                        @if($order->sms)
                                            <span class="badge bg-info status-badge">SMS sendt</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="showOrderDetails({{ $order->id }})" title="Se detaljer">
                                                <i class="bi bi-eye"></i>
                                            </button>

                                            @if(!$order->paid)
                                                <button type="button" class="btn btn-sm btn-outline-success mark-paid-btn"
                                                        data-order-id="{{ $order->id }}" title="Marker som betalt">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            @endif

                                            @if($order->curl == 0)
                                                <button type="button" class="btn btn-sm btn-outline-info send-pos-btn"
                                                        data-order-id="{{ $order->id }}" title="Send til POS">
                                                    <i class="bi bi-send"></i>
                                                </button>
                                            @endif

                                            @if(!$order->sms)
                                                <button type="button" class="btn btn-sm btn-outline-warning send-sms-btn"
                                                        data-order-id="{{ $order->id }}" title="Send SMS">
                                                    <i class="bi bi-chat-text"></i>
                                                </button>
                                            @endif

                                            <!-- Quick status check button -->
                                            @if($order->paid)
                                                <button type="button" class="btn btn-sm btn-outline-info check-wc-status-btn"
                                                        data-order-id="{{ $order->id }}"
                                                        title="Sjekk WooCommerce-status (uten retry)">
                                                    <i class="bi bi-search"></i>
                                                </button>
                                            @endif

                                            <!-- Sync button - highlight in red if no kitchen print -->
                                            @if($order->paid)
                                                @if($order->wcstatus != 'completed')
                                                    <button type="button" class="btn btn-sm btn-danger sync-status-btn"
                                                            data-order-id="{{ $order->id }}"
                                                            data-wc-status="{{ $order->wcstatus }}"
                                                            title="⚠️ INGEN KJØKKENPRINT! Klikk for å trigge PCKasse">
                                                        <i class="bi bi-exclamation-triangle"></i>
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-outline-success sync-status-btn"
                                                            data-order-id="{{ $order->id }}"
                                                            data-wc-status="{{ $order->wcstatus }}"
                                                            title="Re-synkroniser med WooCommerce og PCK">
                                                        <i class="bi bi-arrow-repeat"></i>
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $orders->appends(request()->input())->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <h4 class="text-muted mt-3">Ingen ordrer funnet</h4>
                        <p class="text-muted">Prøv å endre filterkriteriene</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

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
// Configure default fetch options for all AJAX requests
const defaultFetchOptions = {
    credentials: 'same-origin',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Cache-Control': 'no-cache'
    }
};

// Helper function for making authenticated fetch requests
function authenticatedFetch(url, options = {}) {
    const mergedOptions = {
        ...defaultFetchOptions,
        ...options,
        headers: {
            ...defaultFetchOptions.headers,
            ...options.headers
        }
    };

    return fetch(url, mergedOptions)
        .then(response => {
            if (!response.ok) {
                if (response.status === 401) {
                    console.log('Session expired, reloading page');
                    window.location.reload();
                    return Promise.reject(new Error('Session expired'));
                }
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response;
        });
}

// Show order details
function showOrderDetails(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    document.querySelector('#orderDetailsModal .modal-title').textContent = 'Ordre detaljer';
    modal.show();

    // Show loading indicator
    document.getElementById('orderDetailsContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Laster ordre detaljer...</span>
            </div>
            <p class="mt-2">Laster ordre detaljer...</p>
        </div>
    `;

    authenticatedFetch(`/admin/orders/${orderId}`, {
        headers: {
            'Accept': 'text/html'
        }
    })
        .then(response => response.text())
        .then(html => {
            console.log('AJAX Response length:', html.length);
            console.log('Response contains navbar:', html.includes('navbar') || html.includes('Aroi Admin'));

            // Only use content inside the .order-details div if it exists
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            const orderDetails = tempDiv.querySelector('.order-details');

            if (orderDetails) {
                document.getElementById('orderDetailsContent').innerHTML = orderDetails.innerHTML;
            } else {
                document.getElementById('orderDetailsContent').innerHTML = html;
            }
        })
        .catch(error => {
            console.error('Error loading order details:', error);
            document.getElementById('orderDetailsContent').innerHTML = `
                <div class="alert alert-danger">
                    <h6>Feil ved lasting av ordre detaljer</h6>
                    <p>Kunne ikke laste ordre detaljer. Prøv å oppdatere siden.</p>
                    <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                        <i class="fas fa-sync me-1"></i>Oppdater siden
                    </button>
                </div>
            `;
        });
}

// Mark order as paid
document.querySelectorAll('.mark-paid-btn').forEach(button => {
    button.addEventListener('click', function() {
        const orderId = this.dataset.orderId;

        if (confirm('Er du sikker på at du vil markere denne ordren som betalt?')) {
            fetch(`/admin/orders/${orderId}/mark-paid`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Feil: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('En feil oppstod');
            });
        }
    });
});

// Send to POS
document.querySelectorAll('.send-pos-btn').forEach(button => {
    button.addEventListener('click', function() {
        const orderId = this.dataset.orderId;

        if (confirm('Vil du sende denne ordren til POS-systemet?')) {
            fetch(`/admin/orders/${orderId}/send-pos`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Feil: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('En feil oppstod');
            });
        }
    });
});

// Send SMS
document.querySelectorAll('.send-sms-btn').forEach(button => {
    button.addEventListener('click', function() {
        const orderId = this.dataset.orderId;

        if (confirm('Vil du sende SMS til kunden?')) {
            fetch(`/admin/orders/${orderId}/send-sms`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Feil: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('En feil oppstod');
            });
        }
    });
});

// Quick check WooCommerce status (without retry)
document.querySelectorAll('.check-wc-status-btn').forEach(button => {
    button.addEventListener('click', function() {
        const orderId = this.dataset.orderId;
        const btn = this;
        const originalHTML = btn.innerHTML;

        // Show loading spinner
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
        btn.disabled = true;

        fetch(`/admin/orders/${orderId}/check-wc-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let statusEmoji = data.status === 'completed' ? '✅' : '⚠️';
                let statusText = data.status === 'completed' ? 'FULLFØRT - Kjøkkenprint OK!' : 'IKKE FULLFØRT - Mangler kjøkkenprint!';
                alert(`${statusEmoji} WooCommerce-status oppdatert\n\n${statusText}\n\nStatus: ${data.status}`);
                location.reload();
            } else {
                alert('Feil: ' + data.message);
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('En feil oppstod ved sjekk av status');
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        });
    });
});

// Sync order status with WooCommerce and PCKasse
document.querySelectorAll('.sync-status-btn').forEach(button => {
    button.addEventListener('click', function() {
        const orderId = this.dataset.orderId;
        const btn = this;
        const originalHTML = btn.innerHTML;

        // Show loading spinner
        btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i>';
        btn.disabled = true;

        fetch(`/admin/orders/${orderId}/sync-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message with details
                let message = '✅ Synkronisering fullført!\n\n';
                if (data.actions && data.actions.length > 0) {
                    message += 'Handlinger utført:\n' + data.actions.join('\n');
                }
                alert(message);
                location.reload();
            } else {
                // Show issues
                let message = '⚠️ Synkronisering fullført med problemer:\n\n';
                if (data.issues && data.issues.length > 0) {
                    message += 'Problemer:\n' + data.issues.join('\n');
                }
                if (data.actions && data.actions.length > 0) {
                    message += '\n\nHandlinger utført:\n' + data.actions.join('\n');
                }
                alert(message);

                // Reload anyway to show updated status
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('En feil oppstod under synkronisering');
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        });
    });
});
</script>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.spin {
    animation: spin 1s linear infinite;
    display: inline-block;
}

/* Highlight orders missing kitchen print */
.table-danger {
    background-color: #f8d7da !important;
}

.table-danger:hover {
    background-color: #f5c2c7 !important;
}

/* Pulsing animation for warning icon */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.text-danger i {
    animation: pulse 2s ease-in-out infinite;
}
</style>

@endpush
