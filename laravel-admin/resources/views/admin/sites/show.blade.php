@extends('layouts.admin')

@section('title', 'Site Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Site Details: {{ $site->name }}</h1>
                <div>
                    <a href="{{ route('admin.sites.edit', $site) }}" class="btn btn-primary me-2">
                        <i class="fas fa-edit me-2"></i>Edit Site
                    </a>
                    <a href="{{ route('admin.sites.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Sites
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Site Information -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Site Configuration</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Site Name</label>
                                <p class="fw-bold">{{ $site->name }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">WordPress Site ID</label>
                                <p><span class="badge bg-info fs-6">{{ $site->site_id }}</span></p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Site URL</label>
                        <p>
                            <a href="{{ $site->url }}" target="_blank" class="text-decoration-none">
                                {{ $site->url }}
                                <i class="fas fa-external-link-alt ms-1"></i>
                            </a>
                        </p>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">PCKasse License</label>
                                <p>
                                    @if($site->license > 0)
                                        <span class="badge bg-success fs-6">{{ $site->license }}</span>
                                    @else
                                        <span class="badge bg-secondary">No License</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Site Status</label>
                                <p>
                                    <span class="badge {{ $site->active ? 'bg-success' : 'bg-danger' }} fs-6">
                                        {{ $site->active ? 'Active' : 'Inactive' }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- WooCommerce API Credentials -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fab fa-wordpress me-2"></i>WooCommerce API Credentials
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Consumer Key</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="consumerKey"
                                           value="{{ $site->consumer_key }}" readonly>
                                    <button class="btn btn-outline-secondary" type="button"
                                            onclick="togglePassword('consumerKey')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Consumer Secret</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="consumerSecret"
                                           value="{{ $site->consumer_secret }}" readonly>
                                    <button class="btn btn-outline-secondary" type="button"
                                            onclick="togglePassword('consumerSecret')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>API Endpoint:</strong> {{ $site->url }}/wp-json/wc/v3/
                    </div>
                </div>
            </div>

            <!-- Associated Users -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>Associated Users
                    </h5>
                    <a href="{{ route('admin.sites.users', $site) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-cog me-1"></i>Manage Users
                    </a>
                </div>
                <div class="card-body">
                    @if($site->users && $site->users->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>License</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($site->users as $user)
                                        <tr>
                                            <td><strong>{{ $user->username }}</strong></td>
                                            <td>{{ $user->name }}</td>
                                            <td>
                                                <span class="badge {{ $user->role === 'admin' ? 'bg-danger' : 'bg-primary' }}">
                                                    {{ ucfirst($user->role) }}
                                                </span>
                                            </td>
                                            <td>{{ $user->license ?: 'N/A' }}</td>
                                            <td>{{ $user->created_at->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-3">
                            <i class="fas fa-users fa-2x text-muted mb-3"></i>
                            <p class="text-muted">No users assigned to this site.</p>
                            <a href="{{ route('admin.sites.users', $site) }}" class="btn btn-outline-primary">
                                <i class="fas fa-plus me-2"></i>Assign Users
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar Information -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Site Statistics
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-primary">{{ $site->users_count ?? 0 }}</h4>
                                <small class="text-muted">Users</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success">{{ $site->orders_count ?? 0 }}</h4>
                            <small class="text-muted">Orders</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-clock me-2"></i>Site Timeline
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <p><strong>Created:</strong><br>{{ $site->created_at->format('M d, Y H:i') }}</p>
                        <p><strong>Last Updated:</strong><br>{{ $site->updated_at->format('M d, Y H:i') }}</p>
                        <p><strong>Age:</strong><br>{{ $site->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-tools me-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.sites.edit', $site) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit me-2"></i>Edit Site
                        </a>
                        <a href="{{ route('admin.sites.users', $site) }}" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>
                        <a href="{{ $site->url }}" target="_blank" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-external-link-alt me-2"></i>Visit Site
                        </a>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Integration Status
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>WooCommerce API:</span>
                            <span class="badge bg-success">Connected</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>PCKasse POS:</span>
                            @if($site->license > 0)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Disabled</span>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Site Status:</span>
                            <span class="badge {{ $site->active ? 'bg-success' : 'bg-danger' }}">
                                {{ $site->active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling.querySelector('i');

    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>
@endsection
