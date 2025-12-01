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
                            <button class="btn btn-sm btn-light ms-2" onclick="manualRefresh()" id="refreshBtn">
                                <i class="fas fa-sync-alt" id="refreshIcon"></i>
                            </button>
                            <small class="text-white ms-2">Sist oppdatert: <span id="lastUpdate">{{ now()->format('H:i:s') }}</span></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Time Settings -->
    <div class="row mb-2">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-clock text-primary me-2"></i>
                            <span class="fw-bold me-2">Forberedelsestid:</span>
                            <span class="badge bg-primary px-3 py-1">
                                <span id="delivery-time-display">{{ $deliveryTime ?? 30 }}</span> min
                            </span>
                        </div>
                        <div class="d-flex align-items-center" style="flex: 0 0 350px;">
                            <small class="text-muted me-2">10</small>
                            <input type="range"
                                   class="form-range"
                                   id="delivery-time-slider"
                                   min="10"
                                   max="90"
                                   step="5"
                                   value="{{ $deliveryTime ?? 30 }}"
                                   style="flex: 1;">
                            <small class="text-muted ms-2">90</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert for orders missing kitchen print -->
    @php
        $ordersWithoutPrint = $newOrders->filter(function($order) {
            return $order->wcstatus != 'completed';
        });
    @endphp

    @if($ordersWithoutPrint->count() > 0)
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
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
                            <div class="card border-warning h-100 {{ $order->wcstatus != 'completed' ? 'border-danger order-missing-print' : '' }}">
                                <div class="card-header {{ $order->wcstatus != 'completed' ? 'bg-danger text-white' : 'bg-warning text-dark' }}">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="mb-0">
                                            #{{ $order->ordreid }}
                                            @if($order->wcstatus != 'completed')
                                                <i class="bi bi-exclamation-triangle-fill pulsing-icon" title="MANGLER KJØKKENPRINT!"></i>
                                            @endif
                                        </h5>
                                        <div class="text-end">
                                            <div class="badge bg-dark">{{ $order->datetime ? $order->datetime->format('D d.m') : 'N/A' }}</div>
                                            <div class="badge bg-dark">{{ $order->datetime ? $order->datetime->format('H:i') : 'N/A' }}</div>
                                        </div>
                                    </div>
                                    @if($order->wcstatus != 'completed')
                                        <div class="mb-2">
                                            <small class="fw-bold">⚠️ INGEN KJØKKENPRINT - PCK har ikke mottatt!</small>
                                        </div>
                                    @endif
                                    <button class="btn btn-light w-100" onclick="showOrderDetails({{ $order->id }})" style="min-height: 44px;">
                                        <i class="fas fa-eye me-1"></i>Se detaljer
                                    </button>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title">{{ $order->fornavn }} {{ $order->etternavn }}</h6>
                                    <p class="mb-1"><i class="fas fa-phone me-1"></i>{{ $order->telefon }}</p>
                                    @if($order->hentes)
                                        <p class="mb-2"><i class="fas fa-clock me-1"></i>Henting: <strong>{{ $order->hentes }}</strong></p>
                                    @endif

                                    <!-- PCK Status Badge -->
                                    @if($order->wcstatus == 'completed')
                                        <div class="alert alert-success alert-sm py-1 px-2 mb-2">
                                            <small><i class="bi bi-check-circle-fill me-1"></i>Kjøkkenprint OK</small>
                                        </div>
                                    @else
                                        <div class="alert alert-danger alert-sm py-1 px-2 mb-2">
                                            <small><i class="bi bi-exclamation-triangle-fill me-1"></i>Mangler kjøkkenprint</small>
                                        </div>
                                    @endif

                                    <!-- SMS Status Badge -->
                                    @if($order->sms)
                                        <div class="alert alert-info alert-sm py-1 px-2 mb-2">
                                            <small><i class="bi bi-chat-dots-fill me-1"></i>SMS sendt til kunde</small>
                                        </div>
                                    @else
                                        <div class="alert alert-warning alert-sm py-1 px-2 mb-2">
                                            <small><i class="bi bi-chat-dots me-1"></i>SMS ikke sendt ennå</small>
                                        </div>
                                    @endif

                                    <!-- Order items preview -->
                                    <div class="order-items mb-3">
                                        <small class="text-muted">{{ $order->ekstrainfo }}</small>
                                    </div>

                                    <!-- Main Action Buttons (Touch-Optimized) -->
                                    <div class="d-grid gap-2 mb-3">
                                        <button class="btn btn-success" onclick="markOrderReady({{ $order->id }})" style="min-height: 50px; font-size: 1.1rem;">
                                            <i class="fas fa-check me-1"></i>KLAR FOR HENTING
                                        </button>
                                        <button class="btn btn-outline-secondary" onclick="markOrderReadySilent({{ $order->id }})" style="min-height: 44px;">
                                            <i class="fas fa-check-circle me-1"></i>Klar (uten SMS)
                                        </button>
                                    </div>

                                    <!-- Status Check Buttons (for problem orders) -->
                                    @if($order->wcstatus != 'completed' || !$order->sms)
                                    <div class="d-grid gap-2">
                                        @if($order->wcstatus != 'completed')
                                            <button class="btn btn-outline-info check-wc-status-btn"
                                                    data-order-id="{{ $order->id }}" style="min-height: 40px;">
                                                <i class="bi bi-search me-1"></i>Sjekk status
                                            </button>
                                            <button class="btn btn-danger sync-status-btn"
                                                    data-order-id="{{ $order->id }}" style="min-height: 40px;">
                                                <i class="bi bi-arrow-repeat me-1"></i>Trigger PCKasse
                                            </button>
                                        @endif

                                        @if(!$order->sms)
                                            <button class="btn btn-outline-warning send-sms-btn"
                                                    data-order-id="{{ $order->id }}" style="min-height: 40px;">
                                                <i class="bi bi-chat-dots me-1"></i>Send SMS nå
                                            </button>
                                        @endif
                                    </div>
                                    @endif
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
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <h5 class="mb-0">#{{ $order->ordreid }}</h5>
                                            <small>{{ $order->datetime ? $order->datetime->format('D d.m H:i') : 'N/A' }}</small>
                                        </div>
                                        <span class="badge bg-white text-dark">
                                            @if($order->sms)
                                                SMS SENDT
                                            @else
                                                VENTER
                                            @endif
                                        </span>
                                    </div>
                                    <button class="btn btn-light w-100" onclick="showOrderDetails({{ $order->id }})" style="min-height: 44px;">
                                        <i class="fas fa-eye me-1"></i>Se detaljer
                                    </button>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title">{{ $order->fornavn }} {{ $order->etternavn }}</h6>
                                    <p class="mb-1"><i class="fas fa-phone me-1"></i>{{ $order->telefon }}</p>
                                    @if($order->hentes)
                                        <p class="mb-3"><i class="fas fa-clock me-1"></i>Henting: <strong>{{ $order->hentes }}</strong></p>
                                    @endif

                                    <!-- Main Action Buttons (Touch-Optimized) -->
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-success" onclick="markOrderCompleted({{ $order->id }})" style="min-height: 50px; font-size: 1.1rem;">
                                            <i class="fas fa-check-double me-1"></i>HENTET
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="markOrderCompletedSilent({{ $order->id }})" style="min-height: 44px;">
                                            <i class="fas fa-check-double me-1"></i>Hentet (uten SMS)
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
                                    <td>
                                        <strong>#{{ $order->ordreid }}</strong><br>
                                        <small class="text-muted">{{ $order->datetime ? $order->datetime->format('d.m H:i') : 'N/A' }}</small>
                                    </td>
                                    <td>{{ $order->fornavn }} {{ $order->etternavn }}</td>
                                    <td>{{ $order->telefon }}</td>
                                    <td>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>{{ $order->hentet_tid ? $order->hentet_tid->format('H:i') : 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-outline-primary" onclick="showOrderDetails({{ $order->id }})" style="min-height: 40px; min-width: 100px;">
                                            <i class="fas fa-eye me-1"></i>Detaljer
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

// Auto-refresh removed - using dynamic updates instead
// This prevents session issues and improves user experience

// Update last refresh time
function updateLastRefresh() {
    document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString('no-NO');
}

// Toggle location status
function toggleLocationStatus() {
    const checkbox = document.getElementById('locationStatus');

    fetch('{{ route('admin.dashboard.toggle-status') }}', {
        method: 'POST',
        credentials: 'same-origin',  // CRITICAL: Send cookies with request
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
        },
        body: JSON.stringify({
            status: checkbox.checked ? 1 : 0
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Status oppdatert!');
        } else {
            alert('Kunne ikke oppdatere status');
            checkbox.checked = !checkbox.checked;
        }
    })
    .catch(error => {
        console.error('Error toggling status:', error);
        alert('Kunne ikke oppdatere status: ' + error.message);
        checkbox.checked = !checkbox.checked;
    });
}

// Get fresh CSRF token from meta tag
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

// Mark order as ready
function markOrderReady(orderId) {
    fetch(`/admin/orders/${orderId}/status`, {
        method: 'PATCH',
        credentials: 'same-origin',  // CRITICAL: Send cookies with request
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
        },
        body: JSON.stringify({
            status: 1
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Refresh order lists dynamically instead of full reload
            refreshOrderLists(true);
        } else {
            console.error('Feil ved oppdatering:', data.message);
        }
    })
    .catch(error => {
        console.error('Error marking order ready:', error);
    });
}

// Mark order as ready (silent mode - no SMS)
function markOrderReadySilent(orderId) {
    fetch(`/admin/orders/${orderId}/status`, {
        method: 'PATCH',
        credentials: 'same-origin',  // CRITICAL: Send cookies with request
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
        },
        body: JSON.stringify({
            status: 1,
            silent: true
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Refresh order lists dynamically instead of full reload
            refreshOrderLists(true);
        } else {
            console.error('Feil ved oppdatering:', data.message);
        }
    })
    .catch(error => {
        console.error('Error marking order ready (silent):', error);
    });
}

// Mark order as completed
function markOrderCompleted(orderId) {
    fetch(`/admin/orders/${orderId}/status`, {
        method: 'PATCH',
        credentials: 'same-origin',  // CRITICAL: Send cookies with request
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
        },
        body: JSON.stringify({
            status: 2
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            refreshOrderLists(true);
        } else {
            console.error('Feil ved oppdatering:', data.message);
        }
    })
    .catch(error => {
        console.error('Error marking order completed:', error);
    });
}

// Mark order as completed (silent mode - no SMS)
function markOrderCompletedSilent(orderId) {
    fetch(`/admin/orders/${orderId}/status`, {
        method: 'PATCH',
        credentials: 'same-origin',  // CRITICAL: Send cookies with request
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
        },
        body: JSON.stringify({
            status: 2,
            silent: true
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            refreshOrderLists(true);
        } else {
            console.error('Feil ved oppdatering:', data.message);
        }
    })
    .catch(error => {
        console.error('Error marking order completed (silent):', error);
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



// Add sound notification for new orders
let lastOrderCount = {{ $newOrders->count() }};
let lastReadyCount = {{ $readyOrders->count() }};
let lastCompletedCount = {{ $completedOrders->count() }};
let isCheckingOrders = false;  // Prevent overlapping requests

function checkNewOrders() {
    // Prevent race conditions from overlapping requests
    if (isCheckingOrders) {
        console.log('Skipping order check - previous request still in progress');
        return;
    }

    isCheckingOrders = true;

    authenticatedFetch('/api/orders/count', {
        headers: {
            'Accept': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            let hasChanges = false;

            // Check if new orders count changed
            if (data.count > lastOrderCount) {
                // Play notification sound for NEW orders only
                try {
                    const audio = new Audio('/sounds/notification.mp3');
                    audio.play().catch(e => {
                        console.log('Notification sound not available:', e.message);
                    });
                } catch (e) {
                    console.log('Could not play notification sound');
                }

                // Show browser notification if permitted
                if (Notification.permission === "granted") {
                    new Notification("Ny ordre!", {
                        body: "Det har kommet inn en ny ordre",
                        icon: "{{ asset('images/logo.png') }}"
                    });
                } else if (Notification.permission === "default") {
                    // Request permission if not yet asked
                    Notification.requestPermission();
                }
                hasChanges = true;
            } else if (data.count !== lastOrderCount) {
                // Count changed (decreased or increased)
                hasChanges = true;
            }

            // Update counts
            lastOrderCount = data.count;

            // Always refresh UI to catch any status changes
            // This ensures the page updates when orders move between tabs
            refreshOrderLists();

            // Update last refresh time
            updateLastRefresh();
        })
        .catch(error => {
            console.error('Error checking new orders:', error);
            // Don't show error to user, just log it
        })
        .finally(() => {
            isCheckingOrders = false;  // Always reset flag to allow next request
        });
}

// Refresh order lists dynamically without page reload
let isRefreshing = false;  // Prevent overlapping refresh requests

function refreshOrderLists(showLoader = false) {
    // Prevent race conditions from overlapping refresh requests
    if (isRefreshing) {
        console.log('Skipping refresh - previous refresh still in progress');
        return;
    }

    isRefreshing = true;

    if (showLoader) {
        const refreshIcon = document.getElementById('refreshIcon');
        if (refreshIcon) {
            refreshIcon.classList.add('fa-spin');
        }
    }

    fetch(window.location.href, {
        credentials: 'same-origin',  // CRITICAL: Send cookies with request
        headers: {
            'Accept': 'text/html',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        // Update new orders tab
        const newOrdersContent = doc.querySelector('#new-orders');
        if (newOrdersContent) {
            document.querySelector('#new-orders').innerHTML = newOrdersContent.innerHTML;
        }

        // Update ready orders tab
        const readyOrdersContent = doc.querySelector('#ready-orders');
        if (readyOrdersContent) {
            document.querySelector('#ready-orders').innerHTML = readyOrdersContent.innerHTML;
        }

        // Update completed orders tab
        const completedOrdersContent = doc.querySelector('#completed-orders');
        if (completedOrdersContent) {
            document.querySelector('#completed-orders').innerHTML = completedOrdersContent.innerHTML;
        }

        // Update badge counts
        const newOrdersTab = doc.querySelector('#new-orders-tab');
        const readyOrdersTab = doc.querySelector('#ready-orders-tab');
        const completedOrdersTab = doc.querySelector('#completed-orders-tab');

        if (newOrdersTab) {
            document.querySelector('#new-orders-tab').innerHTML = newOrdersTab.innerHTML;
        }
        if (readyOrdersTab) {
            document.querySelector('#ready-orders-tab').innerHTML = readyOrdersTab.innerHTML;
        }
        if (completedOrdersTab) {
            document.querySelector('#completed-orders-tab').innerHTML = completedOrdersTab.innerHTML;
        }

        // Update CSRF token to prevent 419 errors
        const newCsrfToken = doc.querySelector('meta[name="csrf-token"]');
        if (newCsrfToken) {
            const currentCsrfToken = document.querySelector('meta[name="csrf-token"]');
            if (currentCsrfToken) {
                currentCsrfToken.setAttribute('content', newCsrfToken.getAttribute('content'));
                console.log('CSRF token updated from server');
            }
        }

        // Update last refresh time
        updateLastRefresh();

        if (showLoader) {
            const refreshIcon = document.getElementById('refreshIcon');
            if (refreshIcon) {
                refreshIcon.classList.remove('fa-spin');
            }
        }

        console.log('Order lists updated successfully');
    })
    .catch(error => {
        console.error('Error refreshing order lists:', error);
        // Fallback to page reload only on error
        location.reload();
    })
    .finally(() => {
        isRefreshing = false;  // Always reset flag to allow next refresh
        if (showLoader) {
            const refreshIcon = document.getElementById('refreshIcon');
            if (refreshIcon) {
                refreshIcon.classList.remove('fa-spin');
            }
        }
    });
}

// Manual refresh function
function manualRefresh() {
    refreshOrderLists(true);
}

// Check for new orders every 10 seconds
setInterval(checkNewOrders, 10000);

// Request notification permission
if (Notification.permission === "default") {
    Notification.requestPermission();
}

// Delivery Time Slider Functionality
const deliveryTimeSlider = document.getElementById('delivery-time-slider');
const deliveryTimeDisplay = document.getElementById('delivery-time-display');
let deliveryTimeSaveTimeout;

if (deliveryTimeSlider && deliveryTimeDisplay) {
    // Update display when slider moves
    deliveryTimeSlider.addEventListener('input', function(e) {
        deliveryTimeDisplay.textContent = e.target.value;

        // Clear existing timeout
        if (deliveryTimeSaveTimeout) {
            clearTimeout(deliveryTimeSaveTimeout);
        }

        // Save after 1 second of no movement
        deliveryTimeSaveTimeout = setTimeout(() => {
            saveDeliveryTime(e.target.value);
        }, 1000);
    });
}

function saveDeliveryTime(minutes) {
    fetch('/admin/delivery-time/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            delivery_time: parseInt(minutes)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Delivery time updated:', data.delivery_time, 'minutes');

            // Show brief success indicator
            const badge = deliveryTimeDisplay.parentElement;
            badge.classList.remove('bg-primary');
            badge.classList.add('bg-success');

            setTimeout(() => {
                badge.classList.remove('bg-success');
                badge.classList.add('bg-primary');
            }, 1000);
        } else {
            console.error('Failed to update delivery time:', data);
        }
    })
    .catch(error => {
        console.error('Error updating delivery time:', error);
    });
}

// Quick check WooCommerce status (without retry)
document.addEventListener('click', function(e) {
    if (e.target.closest('.check-wc-status-btn')) {
        const btn = e.target.closest('.check-wc-status-btn');
        const orderId = btn.dataset.orderId;
        const originalHTML = btn.innerHTML;

        // Show loading spinner
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sjekker...';
        btn.disabled = true;

        fetch(`/admin/orders/${orderId}/check-wc-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let statusEmoji = data.status === 'completed' ? '✅' : '⚠️';
                let statusText = data.status === 'completed' ? 'FULLFØRT - Kjøkkenprint OK!' : 'IKKE FULLFØRT - Mangler kjøkkenprint!';
                alert(`${statusEmoji} WooCommerce-status oppdatert\n\n${statusText}\n\nStatus: ${data.status}`);
                refreshOrderLists(true);
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
    }
});

// Sync order status with WooCommerce and PCKasse
document.addEventListener('click', function(e) {
    if (e.target.closest('.sync-status-btn')) {
        const btn = e.target.closest('.sync-status-btn');
        const orderId = btn.dataset.orderId;
        const originalHTML = btn.innerHTML;

        // Show loading spinner
        btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Trigger...';
        btn.disabled = true;

        fetch(`/admin/orders/${orderId}/sync-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
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
                refreshOrderLists(true);
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
                refreshOrderLists(true);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('En feil oppstod under synkronisering');
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        });
    }
});

// Send SMS manually
document.addEventListener('click', function(e) {
    if (e.target.closest('.send-sms-btn')) {
        const btn = e.target.closest('.send-sms-btn');
        const orderId = btn.dataset.orderId;
        const originalHTML = btn.innerHTML;

        // Show loading spinner
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sender...';
        btn.disabled = true;

        fetch(`/admin/orders/${orderId}/send-sms`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ SMS sendt til kunde!');
                refreshOrderLists(true);
            } else {
                alert('❌ Feil: ' + data.message);
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('En feil oppstod ved sending av SMS');
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        });
    }
});

// Auto-polling for orders without kitchen print
const AutoStatusPoller = {
    pollingOrders: new Map(), // Map of orderId -> {attemptCount, timeoutId, createdAt}
    maxAttempts: 5,
    intervals: [10000, 30000, 60000, 120000, 240000], // 10s, 30s, 1m, 2m, 4m

    init: function() {
        console.log('[AutoPoller] Initializing auto-polling system');
        this.identifyOrdersNeedingPolling();
        this.startPolling();
    },

    identifyOrdersNeedingPolling: function() {
        // Find all order cards that are missing kitchen print
        const orderCards = document.querySelectorAll('.card');
        const now = Date.now();
        const maxAge = 15 * 60 * 1000; // 15 minutes

        orderCards.forEach(card => {
            const missingPrintIndicator = card.querySelector('.alert-danger.alert-sm');
            if (!missingPrintIndicator) return;

            const checkText = missingPrintIndicator.textContent;
            if (!checkText.includes('Mangler kjøkkenprint')) return;

            // Find order ID from buttons
            const syncBtn = card.querySelector('.sync-status-btn');
            if (!syncBtn) return;

            const orderId = syncBtn.dataset.orderId;
            if (!orderId) return;

            // Check if order is recent enough (within 15 minutes)
            // We'll extract the datetime from the card header
            const timeElement = card.querySelector('.badge.bg-dark');
            if (!timeElement) return;

            // Add to polling list if not already there
            if (!this.pollingOrders.has(orderId)) {
                this.pollingOrders.set(orderId, {
                    attemptCount: 0,
                    timeoutId: null,
                    startedAt: now
                });
                console.log(`[AutoPoller] Added order ${orderId} to polling queue`);
            }
        });

        console.log(`[AutoPoller] Found ${this.pollingOrders.size} orders needing status checks`);
    },

    startPolling: function() {
        if (this.pollingOrders.size === 0) {
            console.log('[AutoPoller] No orders need polling');
            return;
        }

        // Start polling for each order
        this.pollingOrders.forEach((data, orderId) => {
            this.scheduleCheck(orderId);
        });
    },

    scheduleCheck: function(orderId) {
        const data = this.pollingOrders.get(orderId);
        if (!data) return;

        // Check if we've exceeded max attempts
        if (data.attemptCount >= this.maxAttempts) {
            console.log(`[AutoPoller] Order ${orderId} reached max attempts (${this.maxAttempts}), stopping`);
            this.pollingOrders.delete(orderId);
            return;
        }

        // Get interval for this attempt
        const interval = this.intervals[data.attemptCount] || this.intervals[this.intervals.length - 1];

        console.log(`[AutoPoller] Scheduling check for order ${orderId} in ${interval/1000}s (attempt ${data.attemptCount + 1}/${this.maxAttempts})`);

        // Schedule the check
        data.timeoutId = setTimeout(() => {
            this.performCheck(orderId);
        }, interval);

        // Increment attempt counter
        data.attemptCount++;
        this.pollingOrders.set(orderId, data);
    },

    performCheck: function(orderId) {
        console.log(`[AutoPoller] Performing status check for order ${orderId}`);

        // Call the batch check API
        fetch('/api/v1/orders/batch-check-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({
                order_ids: [parseInt(orderId)]
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.results && data.results.length > 0) {
                const result = data.results[0];
                console.log(`[AutoPoller] Order ${orderId} status:`, result);

                if (result.pck_acknowledged) {
                    // Kitchen print received! Stop polling and refresh
                    console.log(`[AutoPoller] ✅ Order ${orderId} now has kitchen print! Stopping polling.`);
                    this.pollingOrders.delete(orderId);
                    this.showSuccessNotification(result.order_id);
                    refreshOrderLists(true);
                } else {
                    // Still not completed, schedule next check
                    console.log(`[AutoPoller] Order ${orderId} still missing print, scheduling next check...`);
                    if (result.pck_triggered) {
                        console.log(`[AutoPoller] PCKasse queue triggered for order ${orderId}`);
                    }
                    this.scheduleCheck(orderId);
                }
            } else {
                console.error('[AutoPoller] Failed to check status:', data);
                // Schedule retry anyway
                this.scheduleCheck(orderId);
            }
        })
        .catch(error => {
            console.error('[AutoPoller] Error checking status:', error);
            // Schedule retry
            this.scheduleCheck(orderId);
        });
    },

    showSuccessNotification: function(orderNumber) {
        // Show a subtle notification that the kitchen print was received
        const notification = document.createElement('div');
        notification.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3';
        notification.style.zIndex = '9999';
        notification.innerHTML = `
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>Ordre #${orderNumber}</strong> - Kjøkkenprint mottatt!
        `;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    },

    stopAll: function() {
        console.log('[AutoPoller] Stopping all polling');
        this.pollingOrders.forEach((data, orderId) => {
            if (data.timeoutId) {
                clearTimeout(data.timeoutId);
            }
        });
        this.pollingOrders.clear();
    }
};

// Initialize auto-polling when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('[AutoPoller] DOM loaded, initializing...');
    AutoStatusPoller.init();
});

// Re-initialize after manual refresh
const originalRefreshOrderLists = refreshOrderLists;
refreshOrderLists = function(force) {
    // Stop current polling
    AutoStatusPoller.stopAll();

    // Call original refresh
    originalRefreshOrderLists(force);

    // Re-initialize polling after a short delay (to let new content load)
    setTimeout(() => {
        AutoStatusPoller.init();
    }, 1000);
};
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

/* Highlight orders missing kitchen print */
.order-missing-print {
    border-width: 3px !important;
    box-shadow: 0 0 20px rgba(220, 53, 69, 0.5);
}

/* Pulsing animation for warning icon */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.pulsing-icon {
    animation: pulse 2s ease-in-out infinite;
    font-size: 1.2em;
}

/* Spinning animation for sync button */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.spin {
    animation: spin 1s linear infinite;
    display: inline-block;
}

/* Alert styling */
.alert-sm {
    font-size: 0.875rem;
}

/* Touch-friendly button sizing for tablets and mobile */
.btn {
    padding: 0.5rem 1rem;
}

/* Ensure adequate spacing between button groups */
.card-body .d-grid {
    margin-bottom: 0.75rem;
}

.card-body .d-grid:last-child {
    margin-bottom: 0;
}

/* Tablet-specific optimizations (10-inch tablets at ~768px-1024px) */
@media (min-width: 768px) and (max-width: 1024px) {
    .card-body {
        padding: 1rem;
    }

    /* Ensure buttons maintain touch-friendly sizes on tablets */
    .btn {
        min-height: 44px;
        font-size: 1rem;
    }
}

/* Mobile optimizations */
@media (max-width: 767px) {
    .card-body {
        padding: 0.75rem;
    }

    .btn {
        font-size: 0.875rem;
        min-height: 42px;
    }
}
</style>
@endpush
@endsection
