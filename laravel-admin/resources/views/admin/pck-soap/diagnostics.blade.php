@extends('layouts.admin')

@section('title', 'PCK SOAP Diagnostics')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">📊 PCK SOAP System Diagnostics</h1>
                <div>
                    <button type="button" class="btn btn-outline-primary btn-sm me-2" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                    <a href="{{ route('admin.pck-soap.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">🖥️ System Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td><strong>PHP Version:</strong></td>
                            <td><code>{{ $diagnostics['system_info']['php_version'] }}</code></td>
                        </tr>
                        <tr>
                            <td><strong>Laravel Version:</strong></td>
                            <td><code>{{ $diagnostics['system_info']['laravel_version'] }}</code></td>
                        </tr>
                        <tr>
                            <td><strong>SOAP Extension:</strong></td>
                            <td>
                                @if($diagnostics['system_info']['soap_extension'])
                                    <span class="badge bg-success">Loaded</span>
                                @else
                                    <span class="badge bg-danger">Missing</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>GD Extension:</strong></td>
                            <td>
                                @if($diagnostics['system_info']['gd_extension'])
                                    <span class="badge bg-success">Loaded</span>
                                @else
                                    <span class="badge bg-warning">Missing</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>cURL Extension:</strong></td>
                            <td>
                                @if($diagnostics['system_info']['curl_extension'])
                                    <span class="badge bg-success">Loaded</span>
                                @else
                                    <span class="badge bg-danger">Missing</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>OpenSSL Extension:</strong></td>
                            <td>
                                @if($diagnostics['system_info']['openssl_extension'])
                                    <span class="badge bg-success">Loaded</span>
                                @else
                                    <span class="badge bg-danger">Missing</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">🗄️ Database Information</h5>
                </div>
                <div class="card-body">
                    @if($diagnostics['database_info']['connection'] === 'successful')
                        <div class="alert alert-success mb-3">
                            <i class="fas fa-check-circle me-2"></i>Database connection successful
                        </div>
                        
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th colspan="2">Table Row Counts:</th>
                            </tr>
                            @foreach($diagnostics['database_info']['tables'] as $table => $count)
                                <tr>
                                    <td><code>{{ $table }}</code></td>
                                    <td><span class="badge bg-info">{{ number_format($count) }}</span></td>
                                </tr>
                            @endforeach
                        </table>
                    @else
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle me-2"></i>Database connection failed
                            <br><small>{{ $diagnostics['database_info']['error'] ?? 'Unknown error' }}</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Queue Information -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">⚙️ Queue Information</h5>
                </div>
                <div class="card-body">
                    @if(isset($diagnostics['queue_info']['error']))
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle me-2"></i>Queue system error
                            <br><small>{{ $diagnostics['queue_info']['error'] }}</small>
                        </div>
                    @else
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Queue Driver:</strong></td>
                                <td><code>{{ $diagnostics['queue_info']['driver'] }}</code></td>
                            </tr>
                            <tr>
                                <td><strong>PCK Queue Size:</strong></td>
                                <td>
                                    <span class="badge bg-{{ $diagnostics['queue_info']['pck_queue_size'] == 0 ? 'success' : 'warning' }}">
                                        {{ $diagnostics['queue_info']['pck_queue_size'] }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Failed Jobs:</strong></td>
                                <td>
                                    <span class="badge bg-{{ $diagnostics['queue_info']['failed_jobs'] == 0 ? 'success' : 'danger' }}">
                                        {{ $diagnostics['queue_info']['failed_jobs'] }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                        
                        @if($diagnostics['queue_info']['driver'] !== 'redis')
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Recommendation:</strong> Use Redis for production queue driver for better performance.
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📈 Recent Activity</h5>
                </div>
                <div class="card-body">
                    @if(isset($diagnostics['recent_activity']['error']))
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>Could not load recent activity
                            <br><small>{{ $diagnostics['recent_activity']['error'] }}</small>
                        </div>
                    @else
                        <h6>Recent Payloads:</h6>
                        @if(count($diagnostics['recent_activity']['recent_payloads']) > 0)
                            <div class="table-responsive mb-3">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Tenant</th>
                                            <th>Method</th>
                                            <th>Status</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($diagnostics['recent_activity']['recent_payloads'] as $payload)
                                            <tr>
                                                <td><small>{{ $payload['tenant'] }}</small></td>
                                                <td><span class="badge bg-secondary">{{ $payload['method'] }}</span></td>
                                                <td>
                                                    <span class="badge bg-{{ $payload['status'] === 'processed' ? 'success' : ($payload['status'] === 'failed' ? 'danger' : 'warning') }}">
                                                        {{ $payload['status'] }}
                                                    </span>
                                                </td>
                                                <td><small>{{ $payload['received_at'] }}</small></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">No recent payloads</p>
                        @endif

                        <h6>Recent Order Exports:</h6>
                        @if(count($diagnostics['recent_activity']['recent_exports']) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Exported</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($diagnostics['recent_activity']['recent_exports'] as $export)
                                            <tr>
                                                <td><code>{{ $export['order_id'] }}</code></td>
                                                <td><small>{{ $export['customer'] }}</small></td>
                                                <td><small>{{ $export['exported_at'] }}</small></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">No recent exports</p>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- System Recommendations -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">💡 System Recommendations</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Performance Optimizations:</h6>
                            <ul class="list-unstyled">
                                @if(!$diagnostics['system_info']['soap_extension'])
                                    <li class="text-danger">
                                        <i class="fas fa-times me-2"></i>Install PHP SOAP extension
                                    </li>
                                @else
                                    <li class="text-success">
                                        <i class="fas fa-check me-2"></i>SOAP extension installed
                                    </li>
                                @endif
                                
                                @if(isset($diagnostics['queue_info']['driver']) && $diagnostics['queue_info']['driver'] !== 'redis')
                                    <li class="text-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>Consider using Redis for queues
                                    </li>
                                @else
                                    <li class="text-success">
                                        <i class="fas fa-check me-2"></i>Queue driver configured
                                    </li>
                                @endif
                                
                                @if(!$diagnostics['system_info']['gd_extension'])
                                    <li class="text-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>GD extension recommended for image processing
                                    </li>
                                @else
                                    <li class="text-success">
                                        <i class="fas fa-check me-2"></i>GD extension available
                                    </li>
                                @endif
                            </ul>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Security Recommendations:</h6>
                            <ul class="list-unstyled">
                                <li class="text-info">
                                    <i class="fas fa-info-circle me-2"></i>Change default PCK passwords
                                </li>
                                <li class="text-info">
                                    <i class="fas fa-info-circle me-2"></i>Configure IP whitelisting for PCK systems
                                </li>
                                <li class="text-info">
                                    <i class="fas fa-info-circle me-2"></i>Enable HTTPS for production
                                </li>
                                <li class="text-info">
                                    <i class="fas fa-info-circle me-2"></i>Set up regular backups
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Configuration Export -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📋 Configuration Export</h5>
                </div>
                <div class="card-body">
                    <p>Export complete PCK configuration for easy setup in PCKasse systems.</p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>What's included:</h6>
                            <ul>
                                <li>WSDL and SOAP endpoint URLs</li>
                                <li>All enabled tenant credentials</li>
                                <li>License numbers and tenant mappings</li>
                                <li>Connection status and last seen information</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Usage instructions:</h6>
                            <ol>
                                <li>Click "Export Config" to download JSON file</li>
                                <li>Share relevant sections with PCKasse administrators</li>
                                <li>Update PCKasse systems with correct WSDL URL</li>
                                <li>Configure authentication credentials in PCKasse</li>
                            </ol>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <a href="{{ route('admin.pck-soap.export-config') }}" class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Export Configuration
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Auto-refresh every 60 seconds
setInterval(function() {
    location.reload();
}, 60000);
</script>
@endpush