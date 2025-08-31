@extends('layouts.admin')

@section('title', 'PCK Queue Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">⚙️ PCK Queue Management</h1>
                <a href="{{ route('admin.pck-soap.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Queue Statistics -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2">
                        <i class="fas fa-clock text-warning fa-2x"></i>
                    </div>
                    <h6 class="card-title">Queue Size</h6>
                    <div class="h4 text-warning">{{ $stats['queue_size'] }}</div>
                    <p class="card-text text-muted small">Jobs waiting to be processed</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2">
                        <i class="fas fa-times-circle text-danger fa-2x"></i>
                    </div>
                    <h6 class="card-title">Failed Jobs</h6>
                    <div class="h4 text-danger">{{ $stats['failed_jobs'] }}</div>
                    <p class="card-text text-muted small">System-level failed jobs</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2">
                        <i class="fas fa-inbox text-info fa-2x"></i>
                    </div>
                    <h6 class="card-title">Pending Payloads</h6>
                    <div class="h4 text-info">{{ $stats['pending_payloads'] }}</div>
                    <p class="card-text text-muted small">Received but not processed</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2">
                        <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                    </div>
                    <h6 class="card-title">Failed Payloads</h6>
                    <div class="h4 text-warning">{{ $stats['failed_payloads']->count() }}</div>
                    <p class="card-text text-muted small">Processing failures</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Queue Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Queue Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-3">
                            <form action="{{ route('admin.pck-soap.start-queue') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-play me-2"></i>Start Queue Worker
                                </button>
                            </form>
                        </div>
                        
                        <div class="col-md-6 col-lg-3">
                            <form action="{{ route('admin.pck-soap.retry-failed') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-warning w-100" 
                                        {{ $stats['failed_payloads']->count() == 0 ? 'disabled' : '' }}>
                                    <i class="fas fa-redo me-2"></i>Retry Failed Payloads
                                </button>
                            </form>
                        </div>
                        
                        <div class="col-md-6 col-lg-3">
                            <form action="{{ route('admin.pck-soap.clear-failed-jobs') }}" method="POST"
                                  onsubmit="return confirm('Clear all failed jobs?')">
                                @csrf
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="fas fa-trash me-2"></i>Clear Failed Jobs
                                </button>
                            </form>
                        </div>
                        
                        <div class="col-md-6 col-lg-3">
                            <button type="button" class="btn btn-info w-100" onclick="refreshQueueStatus()">
                                <i class="fas fa-sync-alt me-2"></i>Refresh Status
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Failed Payloads -->
    @if($stats['failed_payloads']->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-danger">🚨 Failed Payloads (Requires Attention)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Location</th>
                                    <th>Method</th>
                                    <th>Received</th>
                                    <th>Failed</th>
                                    <th>Error</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stats['failed_payloads'] as $payload)
                                    <tr>
                                        <td><code>{{ $payload->id }}</code></td>
                                        <td>{{ $payload->avdeling->navn ?? "Tenant {$payload->tenant_id}" }}</td>
                                        <td><span class="badge bg-secondary">{{ $payload->method }}</span></td>
                                        <td>{{ $payload->received_at->format('Y-m-d H:i') }}</td>
                                        <td>{{ $payload->processed_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            @if($payload->error)
                                                <small class="text-danger">
                                                    {{ Str::limit($payload->error['message'] ?? 'Unknown error', 50) }}
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                    onclick="viewPayloadDetails({{ $payload->id }})">
                                                <i class="fas fa-eye"></i>
                                            </button>
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

    <!-- Recent Processed -->
    @if($stats['recent_processed']->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-success">✅ Recently Processed</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Location</th>
                                    <th>Method</th>
                                    <th>Received</th>
                                    <th>Processed</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stats['recent_processed'] as $payload)
                                    <tr>
                                        <td><code>{{ $payload->id }}</code></td>
                                        <td>{{ $payload->avdeling->navn ?? "Tenant {$payload->tenant_id}" }}</td>
                                        <td><span class="badge bg-success">{{ $payload->method }}</span></td>
                                        <td>{{ $payload->received_at->format('H:i:s') }}</td>
                                        <td>{{ $payload->processed_at->format('H:i:s') }}</td>
                                        <td>
                                            {{ $payload->received_at->diffInSeconds($payload->processed_at) }}s
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

@endsection

@push('scripts')
<script>
function refreshQueueStatus() {
    location.reload();
}

function viewPayloadDetails(payloadId) {
    // For now, just show basic info
    // Could be expanded to show full payload details
    alert('Payload ID: ' + payloadId + '\nCheck logs for full details.');
}

// Auto-refresh every 30 seconds
setInterval(function() {
    // Only refresh if there are pending/failed items
    const pending = {{ $stats['pending_payloads'] }};
    const failed = {{ $stats['failed_payloads']->count() }};
    
    if (pending > 0 || failed > 0) {
        location.reload();
    }
}, 30000);
</script>
@endpush