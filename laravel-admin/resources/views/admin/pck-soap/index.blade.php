@extends('layouts.admin')

@section('title', 'PCK SOAP Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">🔧 PCK SOAP Management</h1>
                <div>
                    <a href="{{ route('admin.pck-soap.diagnostics') }}" class="btn btn-info btn-sm me-2">
                        📊 Diagnostics
                    </a>
                    <a href="{{ route('admin.pck-soap.export-config') }}" class="btn btn-secondary btn-sm">
                        📥 Export Config
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Overview -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2">
                        @if($stats['soap_extension'])
                            <i class="fas fa-check-circle text-success fa-2x"></i>
                        @else
                            <i class="fas fa-times-circle text-danger fa-2x"></i>
                        @endif
                    </div>
                    <h6 class="card-title">SOAP Extension</h6>
                    <p class="card-text text-muted small">
                        {{ $stats['soap_extension'] ? 'Loaded' : 'Missing' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2">
                        @if($stats['wsdl_exists'])
                            <i class="fas fa-check-circle text-success fa-2x"></i>
                        @else
                            <i class="fas fa-times-circle text-danger fa-2x"></i>
                        @endif
                    </div>
                    <h6 class="card-title">WSDL File</h6>
                    <p class="card-text text-muted small">
                        <a href="/wsdl/pck.wsdl" target="_blank">View WSDL</a>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2">
                        <i class="fas fa-users text-primary fa-2x"></i>
                    </div>
                    <h6 class="card-title">Credentials</h6>
                    <p class="card-text">
                        <span class="h5 text-success">{{ $stats['enabled_credentials'] }}</span> / 
                        <span class="text-muted">{{ $stats['total_credentials'] }}</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2">
                        @if($stats['pending_payloads'] == 0)
                            <i class="fas fa-check-circle text-success fa-2x"></i>
                        @elseif($stats['pending_payloads'] < 10)
                            <i class="fas fa-clock text-warning fa-2x"></i>
                        @else
                            <i class="fas fa-exclamation-triangle text-danger fa-2x"></i>
                        @endif
                    </div>
                    <h6 class="card-title">Queue Status</h6>
                    <p class="card-text">
                        <span class="h5 {{ $stats['pending_payloads'] == 0 ? 'text-success' : 'text-warning' }}">
                            {{ $stats['pending_payloads'] }}
                        </span> pending
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Health Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">🏥 System Health</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshHealth()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                <div class="card-body">
                    <div id="health-status">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin"></i> Loading health status...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">⚡ Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-4">
                            <form action="{{ route('admin.pck-soap.start-queue') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-play me-2"></i>Start Queue Worker
                                </button>
                            </form>
                        </div>
                        
                        <div class="col-md-6 col-lg-4">
                            <form action="{{ route('admin.pck-soap.retry-failed') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-warning w-100" 
                                        {{ $stats['failed_payloads'] == 0 ? 'disabled' : '' }}>
                                    <i class="fas fa-redo me-2"></i>Retry Failed ({{ $stats['failed_payloads'] }})
                                </button>
                            </form>
                        </div>
                        
                        <div class="col-md-6 col-lg-4">
                            <form action="{{ route('admin.pck-soap.cleanup') }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Are you sure you want to cleanup old data?')">
                                @csrf
                                <button type="submit" class="btn btn-info w-100">
                                    <i class="fas fa-broom me-2"></i>Cleanup Old Data
                                </button>
                            </form>
                        </div>
                        
                        <div class="col-md-6 col-lg-4">
                            <form action="{{ route('admin.pck-soap.generate-passwords') }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('This will generate new passwords for ALL credentials. Continue?')">
                                @csrf
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="fas fa-key me-2"></i>Generate New Passwords
                                </button>
                            </form>
                        </div>
                        
                        <div class="col-md-6 col-lg-4">
                            <form action="{{ route('admin.pck-soap.generate-missing') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i>Generate Missing Credentials
                                </button>
                            </form>
                        </div>
                        
                        <div class="col-md-6 col-lg-4">
                            <form action="{{ route('admin.pck-soap.clear-failed-jobs') }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Clear all failed jobs?')">
                                @csrf
                                <button type="submit" class="btn btn-secondary w-100">
                                    <i class="fas fa-trash me-2"></i>Clear Failed Jobs
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Dashboard -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">📊 Processing Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border-end">
                                <div class="h4 text-primary">{{ $stats['processed_today'] }}</div>
                                <div class="small text-muted">Processed Today</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-end">
                                <div class="h4 text-success">{{ $stats['total_mappings'] }}</div>
                                <div class="small text-muted">Product Mappings</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="h4 text-info">{{ $stats['orders_exported_today'] }}</div>
                            <div class="small text-muted">Orders Exported Today</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">🚨 Issues & Monitoring</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border-end">
                                <div class="h4 {{ $stats['pending_payloads'] == 0 ? 'text-success' : 'text-warning' }}">
                                    {{ $stats['pending_payloads'] }}
                                </div>
                                <div class="small text-muted">Pending Queue</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-end">
                                <div class="h4 {{ $stats['failed_payloads'] == 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $stats['failed_payloads'] }}
                                </div>
                                <div class="small text-muted">Failed Payloads</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="h4 {{ $stats['failed_jobs'] == 0 ? 'text-success' : 'text-danger' }}">
                                {{ $stats['failed_jobs'] }}
                            </div>
                            <div class="small text-muted">Failed Jobs</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Credentials Overview -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">🔑 PCK Credentials</h5>
                    <a href="{{ route('admin.pck-soap.credentials') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-cog me-2"></i>Manage Credentials
                    </a>
                </div>
                <div class="card-body">
                    @if($credentials->count() == 0)
                        <div class="text-center py-4">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <h5>No PCK Credentials Found</h5>
                            <p class="text-muted">Click "Generate Missing Credentials" to create them automatically.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Location</th>
                                        <th>Username</th>
                                        <th>License</th>
                                        <th>Status</th>
                                        <th>Last Seen</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($credentials as $credential)
                                        <tr>
                                            <td>
                                                <strong>{{ $credential->avdeling->navn ?? $credential->avdelingAlternative->Navn ?? "Tenant {$credential->tenant_id}" }}</strong>
                                                <br><small class="text-muted">ID: {{ $credential->tenant_id }}</small>
                                            </td>
                                            <td><code>{{ $credential->pck_username }}</code></td>
                                            <td><code>{{ $credential->pck_license }}</code></td>
                                            <td>
                                                @if($credential->is_enabled)
                                                    <span class="badge bg-success">Enabled</span>
                                                @else
                                                    <span class="badge bg-secondary">Disabled</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($credential->last_seen_at)
                                                    {{ $credential->last_seen_at->diffForHumans() }}
                                                @else
                                                    <span class="text-muted">Never</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                                            onclick="testTenant({{ $credential->tenant_id }})">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <form action="{{ route('admin.pck-soap.toggle-credential', $credential) }}" 
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-{{ $credential->is_enabled ? 'warning' : 'success' }} btn-sm">
                                                            <i class="fas fa-{{ $credential->is_enabled ? 'pause' : 'play' }}"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Queue Management -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">⚙️ Queue Management</h5>
                    <a href="{{ route('admin.pck-soap.queue') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-list me-2"></i>View Queue Details
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="h4">{{ $stats['queue_size'] }}</div>
                            <div class="text-muted">Jobs in Queue</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="h4 text-{{ $stats['failed_jobs'] == 0 ? 'success' : 'danger' }}">
                                {{ $stats['failed_jobs'] }}
                            </div>
                            <div class="text-muted">Failed Jobs</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="h4 text-primary">{{ $stats['processed_today'] }}</div>
                            <div class="text-muted">Processed Today</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="h4 text-info">{{ $stats['orders_ready_export'] }}</div>
                            <div class="text-muted">Orders Ready</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Result Modal -->
<div class="modal fade" id="testResultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tenant Test Result</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="testResultBody">
                <!-- Test results will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Auto-refresh health status every 30 seconds
let healthRefreshInterval;

document.addEventListener('DOMContentLoaded', function() {
    refreshHealth();
    healthRefreshInterval = setInterval(refreshHealth, 30000);
});

function refreshHealth() {
    fetch('/admin/pck-soap/health')
        .then(response => response.json())
        .then(data => {
            updateHealthDisplay(data);
        })
        .catch(error => {
            console.error('Health check failed:', error);
            document.getElementById('health-status').innerHTML = 
                '<div class="alert alert-danger">Health check failed: ' + error.message + '</div>';
        });
}

function updateHealthDisplay(health) {
    let html = '<div class="row">';
    
    // Overall status
    const statusClass = health.overall_status === 'healthy' ? 'success' : 
                       health.overall_status === 'warning' ? 'warning' : 'danger';
    
    html += `<div class="col-12 mb-3">
        <div class="alert alert-${statusClass}">
            <i class="fas fa-${health.overall_status === 'healthy' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            System Status: <strong>${health.overall_status.toUpperCase()}</strong>
            <small class="float-end">Last checked: ${new Date(health.timestamp).toLocaleTimeString()}</small>
        </div>
    </div>`;
    
    // Individual checks
    Object.entries(health.checks).forEach(([check, result]) => {
        const iconClass = result.status === 'pass' ? 'fa-check-circle text-success' : 
                         result.status === 'warn' ? 'fa-exclamation-triangle text-warning' : 
                         'fa-times-circle text-danger';
        
        html += `<div class="col-md-6 col-lg-4 mb-2">
            <div class="d-flex align-items-center">
                <i class="fas ${iconClass} me-2"></i>
                <div>
                    <div class="fw-bold">${check.replace('_', ' ')}</div>
                    <small class="text-muted">${result.message}</small>
                </div>
            </div>
        </div>`;
    });
    
    html += '</div>';
    document.getElementById('health-status').innerHTML = html;
}

function testTenant(tenantId) {
    // Show loading in modal
    const modal = new bootstrap.Modal(document.getElementById('testResultModal'));
    document.getElementById('testResultBody').innerHTML = 
        '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Testing tenant connection...</div>';
    modal.show();
    
    fetch('/admin/pck-soap/test-tenant', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({tenant_id: tenantId})
    })
    .then(response => response.json())
    .then(data => {
        let html = '';
        
        if (data.status === 'success') {
            html = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>Tenant test successful
                </div>
                <h6>Credential Status:</h6>
                <p class="text-success">✅ Active and accessible</p>
                
                <h6>WooCommerce Test:</h6>
                <p class="${data.woo_test.status === 'success' ? 'text-success' : 'text-danger'}">
                    ${data.woo_test.status === 'success' ? '✅' : '❌'} ${data.woo_test.message}
                </p>
                
                <h6>Tenant Info:</h6>
                <ul class="list-unstyled">
                    <li><strong>Name:</strong> ${data.tenant_info.tenant_name}</li>
                    <li><strong>Username:</strong> ${data.tenant_info.pck_username}</li>
                    <li><strong>License:</strong> ${data.tenant_info.pck_license}</li>
                </ul>
            `;
        } else {
            html = `
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle me-2"></i>Tenant test failed
                </div>
                <p><strong>Error:</strong> ${data.message}</p>
            `;
        }
        
        document.getElementById('testResultBody').innerHTML = html;
    })
    .catch(error => {
        document.getElementById('testResultBody').innerHTML = 
            '<div class="alert alert-danger">Test failed: ' + error.message + '</div>';
    });
}

// Clean up interval when page unloads
window.addEventListener('beforeunload', function() {
    if (healthRefreshInterval) {
        clearInterval(healthRefreshInterval);
    }
});
</script>
@endpush