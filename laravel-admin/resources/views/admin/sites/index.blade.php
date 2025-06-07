@extends('layouts.admin')

@section('title', 'Manage Sites')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Manage Sites</h1>
                <a href="{{ route('admin.sites.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add New Site
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            @if($sites->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Site Name</th>
                                <th>Site ID</th>
                                <th>URL</th>
                                <th>License</th>
                                <th>Users</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sites as $site)
                                <tr>
                                    <td><strong>{{ $site->name }}</strong></td>
                                    <td>
                                        <span class="badge bg-info">{{ $site->site_id }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ $site->url }}" target="_blank" class="text-decoration-none">
                                            {{ $site->url }}
                                            <i class="fas fa-external-link-alt ms-1"></i>
                                        </a>
                                    </td>
                                    <td>
                                        @if($site->license > 0)
                                            <span class="badge bg-success">{{ $site->license }}</span>
                                        @else
                                            <span class="badge bg-secondary">No License</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            {{ $site->users_count ?? 0 }} users
                                        </span>
                                        <a href="{{ route('admin.sites.users', $site) }}" class="btn btn-sm btn-outline-info ms-1">
                                            <i class="fas fa-users"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge {{ $site->active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $site->active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.sites.show', $site) }}"
                                               class="btn btn-outline-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.sites.edit', $site) }}"
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="deleteSite({{ $site->id }})"
                                                    class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $sites->links() }}
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-store fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No sites found.</p>
                    <a href="{{ route('admin.sites.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add First Site
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- WooCommerce Credentials Info -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>WooCommerce Integration Info
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Consumer Keys & Secrets</h6>
                            <p class="small text-muted">
                                These are the WooCommerce API credentials used to connect to each site.
                                Consumer keys and secrets are obtained from WooCommerce → Settings → Advanced → REST API.
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>PCKasse License Numbers</h6>
                            <p class="small text-muted">
                                License numbers are used for POS system integration with PCKasse.
                                These numbers are assigned to each location for order processing.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Site Modal -->
<div class="modal fade" id="deleteSiteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Site</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this site? This action cannot be undone.</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> Deleting a site will affect all users assigned to it.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteSiteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Site</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteSite(siteId) {
    const form = document.getElementById('deleteSiteForm');
    form.action = `/admin/sites/${siteId}`;

    const modal = new bootstrap.Modal(document.getElementById('deleteSiteModal'));
    modal.show();
}
</script>
@endsection
