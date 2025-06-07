@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Ordrer - {{ $locationName }}</h1>
</div>

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
                                    <th>Status</th>
                                    <th>Handlinger</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $order)
                                <tr class="order-card">
                                    <td><strong>#{{ $order->ordreid }}</strong></td>
                                    <td>{{ $order->full_name }}</td>
                                    <td>{{ $order->telefon }}</td>
                                    <td>{{ $order->epost }}</td>
                                    <td>{{ $order->datetime->format('d.m.Y H:i') }}</td>
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

                                        <!-- POS Status -->
                                        @if($order->curl == 0)
                                            <span class="badge bg-warning status-badge">Ikke sendt POS</span>
                                        @endif

                                        <!-- SMS Status -->
                                        @if($order->sms)
                                            <span class="badge bg-info status-badge">SMS sendt</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.orders.show', $order) }}"
                                               class="btn btn-sm btn-outline-primary" title="Se detaljer">
                                                <i class="bi bi-eye"></i>
                                            </a>

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

@push('scripts')
<script>
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
</script>
@endpush
