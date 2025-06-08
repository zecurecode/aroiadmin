@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Flash Messages -->
    @if (session('login_success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('login_success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('direct_login'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {{ session('direct_login') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                @if(auth()->user()->isAdmin())
                    <h1 class="h3 mb-0">System Dashboard - Admin View</h1>
                    <span class="badge bg-danger">Admin</span>
                @else
                    <h1 class="h3 mb-0">Dashboard - {{ $locationName ?? 'Location' }}</h1>
                @endif
            </div>
        </div>
    </div>

    @if(auth()->user()->isAdmin())
        <!-- Admin Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">{{ $adminStats['total_users'] }}</h4>
                                <p class="card-text">Total Users</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">{{ $adminStats['total_sites'] }}</h4>
                                <p class="card-text">Total Sites</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-store fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">{{ $adminStats['total_orders_today'] }}</h4>
                                <p class="card-text">Orders Today (All Sites)</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-shopping-cart fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">{{ $adminStats['total_pending_orders'] }}</h4>
                                <p class="card-text">Pending Orders (All Sites)</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Admin Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-users me-2"></i>Manage Users
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="{{ route('admin.sites.index') }}" class="btn btn-outline-success w-100">
                                    <i class="fas fa-store me-2"></i>Manage Sites
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-info w-100">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-list me-2"></i>All Orders
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Location Statistics (for all users) -->
    @if($locationName && !auth()->user()->isAdmin())
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ $locationName }} - Location Status</h5>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="locationStatus"
                                   {{ $status ? 'checked' : '' }} onchange="toggleLocationStatus()">
                            <label class="form-check-label" for="locationStatus">
                                <span class="badge {{ $isOpen ? 'bg-success' : 'bg-danger' }}">
                                    {{ $isOpen ? 'Open' : 'Closed' }}
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($openTime && $closeTime)
                            <p class="mb-0">
                                <i class="fas fa-clock me-2"></i>
                                Opening Hours: {{ $openTime }} - {{ $closeTime }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Regular Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title">{{ $todayOrders }}</h4>
                            <p class="card-text">Today's Orders</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar-day fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title">{{ $pendingOrders }}</h4>
                            <p class="card-text">Pending Orders</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-hourglass-half fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title">{{ $unpaidOrders }}</h4>
                            <p class="card-text">Unpaid Orders</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-credit-card fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Orders</h5>
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    @if($recentOrders->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentOrders as $order)
                                        <tr>
                                            <td><strong>#{{ $order->ordreid }}</strong></td>
                                            <td>{{ $order->fornavn }} {{ $order->etternavn }}</td>
                                            <td>{{ $order->telefon }}</td>
                                            <td>
                                                @switch($order->ordrestatus)
                                                    @case(0)
                                                        <span class="badge bg-warning">Pending</span>
                                                        @break
                                                    @case(1)
                                                        <span class="badge bg-info">Processing</span>
                                                        @break
                                                    @case(2)
                                                        <span class="badge bg-primary">Ready</span>
                                                        @break
                                                    @case(3)
                                                        <span class="badge bg-success">Complete</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">Unknown</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                <span class="badge {{ $order->paid ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $order->paid ? 'Paid' : 'Unpaid' }}
                                                </span>
                                            </td>
                                            <td>{{ $order->datetime->format('M d, H:i') }}</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('admin.orders.show', $order) }}"
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if(!$order->paid)
                                                        <button onclick="markAsPaid({{ $order->id }})"
                                                                class="btn btn-outline-success btn-sm">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No recent orders found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleLocationStatus() {
    const checkbox = document.getElementById('locationStatus');

    fetch('{{ route('admin.dashboard.toggle-status') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
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
            alert('Failed to update status');
            checkbox.checked = !checkbox.checked;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update status');
        checkbox.checked = !checkbox.checked;
    });
}

function markAsPaid(orderId) {
    if (confirm('Mark this order as paid?')) {
        fetch(`/admin/orders/${orderId}/mark-paid`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to mark order as paid');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to mark order as paid');
        });
    }
}
</script>
@endsection
