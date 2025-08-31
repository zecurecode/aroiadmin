@extends('layouts.admin')

@section('title', 'PCK Credentials Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">🔑 PCK Credentials Management</h1>
                <a href="{{ route('admin.pck-soap.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Add/Edit Credential Form -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add/Update PCK Credential</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.pck-soap.store-credential') }}" method="POST" onsubmit="return debugFormSubmit(this)">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tenant_id" class="form-label">Location/Tenant ID *</label>
                                    <select class="form-select @error('tenant_id') is-invalid @enderror" 
                                            id="tenant_id" name="tenant_id" required>
                                        <option value="">Select Location</option>
                                        <option value="4" {{ old('tenant_id') == '4' ? 'selected' : '' }}>Lade (4)</option>
                                        <option value="5" {{ old('tenant_id') == '5' ? 'selected' : '' }}>Gramyra (5)</option>
                                        <option value="6" {{ old('tenant_id') == '6' ? 'selected' : '' }}>Moan (6)</option>
                                        <option value="7" {{ old('tenant_id') == '7' ? 'selected' : '' }}>Namsos (7)</option>
                                        <option value="10" {{ old('tenant_id') == '10' ? 'selected' : '' }}>Frosta (10)</option>
                                        <option value="11" {{ old('tenant_id') == '11' ? 'selected' : '' }}>Hell (11)</option>
                                        <option value="12" {{ old('tenant_id') == '12' ? 'selected' : '' }}>Steinkjer (12)</option>
                                        <option value="16" {{ old('tenant_id') == '16' ? 'selected' : '' }}>Malvik (16)</option>
                                    </select>
                                    @error('tenant_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="pck_username" class="form-label">PCK Username *</label>
                                    <input type="text" class="form-control @error('pck_username') is-invalid @enderror"
                                           id="pck_username" name="pck_username" value="{{ old('pck_username') }}" required>
                                    @error('pck_username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="pck_password" class="form-label">PCK Password *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control @error('pck_password') is-invalid @enderror"
                                               id="pck_password" name="pck_password" required>
                                        <button type="button" class="btn btn-outline-secondary" onclick="generateRandomPassword()">
                                            <i class="fas fa-random"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility()">
                                            <i class="fas fa-eye" id="passwordToggleIcon"></i>
                                        </button>
                                    </div>
                                    @error('pck_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="pck_license" class="form-label">PCK License Number *</label>
                                    <input type="text" class="form-control @error('pck_license') is-invalid @enderror"
                                           id="pck_license" name="pck_license" value="{{ old('pck_license') }}" required>
                                    @error('pck_license')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        <small>Known licenses: Steinkjer: 30221, Namsos: 6714, Lade: 12381, Moan: 5203, Gramyra: 6715, Frosta: 14780</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="ip_whitelist" class="form-label">IP Whitelist (optional)</label>
                            <input type="text" class="form-control @error('ip_whitelist') is-invalid @enderror"
                                   id="ip_whitelist" name="ip_whitelist" value="{{ old('ip_whitelist') }}"
                                   placeholder="192.168.1.100, 10.0.0.50 (comma-separated)">
                            @error('ip_whitelist')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Leave empty to allow all IP addresses</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled"
                                       {{ old('is_enabled', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_enabled">
                                    Enable this credential
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Credential
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">📋 Quick Setup Guide</h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <ol>
                            <li class="mb-2">Choose the location from dropdown</li>
                            <li class="mb-2">Enter PCK username (e.g., "steinkjer_pck")</li>
                            <li class="mb-2">Generate or enter secure password</li>
                            <li class="mb-2">Enter the correct license number</li>
                            <li class="mb-2">Optionally set IP restrictions</li>
                            <li class="mb-2">Enable the credential</li>
                        </ol>
                        
                        <div class="alert alert-warning mt-3">
                            <strong>Note:</strong> These credentials will be used by PCKasse to authenticate SOAP requests.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">🔗 PCKasse Configuration</h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <p><strong>WSDL URL:</strong></p>
                        <code class="d-block mb-2">{{ request()->getSchemeAndHttpHost() }}/wsdl/pck.wsdl</code>
                        
                        <p><strong>SOAP Endpoint:</strong></p>
                        <code class="d-block">{{ request()->getSchemeAndHttpHost() }}/soap/pck/{tenant_id}</code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Existing Credentials Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Existing PCK Credentials</h5>
                </div>
                <div class="card-body">
                    @if($credentials->count() == 0)
                        <div class="text-center py-4">
                            <i class="fas fa-key fa-3x text-muted mb-3"></i>
                            <h5>No credentials configured</h5>
                            <p class="text-muted">Use the form above to create your first PCK credential.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Location</th>
                                        <th>Username</th>
                                        <th>License</th>
                                        <th>IP Whitelist</th>
                                        <th>Status</th>
                                        <th>Last Seen</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($credentials as $credential)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <strong>{{ $credential->avdeling->navn ?? $credential->avdelingAlternative->Navn ?? "Unknown" }}</strong>
                                                        <br><small class="text-muted">Tenant ID: {{ $credential->tenant_id }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><code>{{ $credential->pck_username }}</code></td>
                                            <td><code>{{ $credential->pck_license }}</code></td>
                                            <td>
                                                @if($credential->ip_whitelist)
                                                    <small class="badge bg-info">{{ count($credential->ip_whitelist) }} IPs</small>
                                                    <br><code class="small">{{ implode(', ', array_slice($credential->ip_whitelist, 0, 2)) }}</code>
                                                    @if(count($credential->ip_whitelist) > 2)
                                                        <br><small class="text-muted">+{{ count($credential->ip_whitelist) - 2 }} more</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">All IPs allowed</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($credential->is_enabled)
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Enabled
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-pause me-1"></i>Disabled
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($credential->last_seen_at)
                                                    <span class="text-success" title="{{ $credential->last_seen_at->format('Y-m-d H:i:s') }}">
                                                        {{ $credential->last_seen_at->diffForHumans() }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">Never</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            onclick="testTenant({{ $credential->tenant_id }})"
                                                            title="Test Connection">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    
                                                    <button type="button" class="btn btn-outline-info"
                                                            onclick="editCredential({{ $credential->toJson() }})"
                                                            title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    
                                                    <form action="{{ route('admin.pck-soap.toggle-credential', $credential) }}" 
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" 
                                                                class="btn btn-outline-{{ $credential->is_enabled ? 'warning' : 'success' }}"
                                                                title="{{ $credential->is_enabled ? 'Disable' : 'Enable' }}">
                                                            <i class="fas fa-{{ $credential->is_enabled ? 'pause' : 'play' }}"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <form action="{{ route('admin.pck-soap.delete-credential', $credential) }}" 
                                                          method="POST" class="d-inline"
                                                          onsubmit="return confirm('Delete this credential?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
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
</div>

<!-- Test Result Modal -->
<div class="modal fade" id="testResultModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tenant Connection Test</h5>
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
function debugFormSubmit(form) {
    console.log('Form submission detected');
    console.log('Form action:', form.action);
    console.log('Form method:', form.method);
    
    // Log all form data
    const formData = new FormData(form);
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = key === 'pck_password' ? '***masked***' : value;
    }
    console.log('Form data:', data);
    
    // Check if all required fields are filled
    const requiredFields = ['tenant_id', 'pck_username', 'pck_password', 'pck_license'];
    const missing = [];
    
    requiredFields.forEach(field => {
        if (!formData.get(field)) {
            missing.push(field);
        }
    });
    
    if (missing.length > 0) {
        alert('Missing required fields: ' + missing.join(', '));
        return false;
    }
    
    console.log('Form validation passed, submitting...');
    return true; // Allow form submission
}

function generateRandomPassword() {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    let password = '';
    
    for (let i = 0; i < 16; i++) {
        password += chars[Math.floor(Math.random() * chars.length)];
    }
    
    document.getElementById('pck_password').value = password;
}

function togglePasswordVisibility() {
    const passwordField = document.getElementById('pck_password');
    const icon = document.getElementById('passwordToggleIcon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        passwordField.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

function editCredential(credential) {
    // Populate form with existing credential data
    document.getElementById('tenant_id').value = credential.tenant_id;
    document.getElementById('pck_username').value = credential.pck_username;
    document.getElementById('pck_license').value = credential.pck_license;
    document.getElementById('is_enabled').checked = credential.is_enabled;
    
    if (credential.ip_whitelist && credential.ip_whitelist.length > 0) {
        document.getElementById('ip_whitelist').value = credential.ip_whitelist.join(', ');
    }
    
    // Scroll to form
    document.querySelector('.card').scrollIntoView({ behavior: 'smooth' });
}

function testTenant(tenantId) {
    // Show loading in modal
    const modal = new bootstrap.Modal(document.getElementById('testResultModal'));
    document.getElementById('testResultBody').innerHTML = 
        '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Testing tenant connection...</div>';
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
                    <i class="fas fa-check-circle me-2"></i>Connection test successful!
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>Credential Status:</h6>
                        <div class="badge bg-success mb-3">Active and Accessible</div>
                        
                        <h6>Tenant Information:</h6>
                        <ul class="list-unstyled">
                            <li><strong>Name:</strong> ${data.tenant_info.tenant_name}</li>
                            <li><strong>Username:</strong> ${data.tenant_info.pck_username}</li>
                            <li><strong>License:</strong> ${data.tenant_info.pck_license}</li>
                            <li><strong>Enabled:</strong> ${data.tenant_info.is_enabled ? 'Yes' : 'No'}</li>
                        </ul>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>WooCommerce Test:</h6>
                        <div class="alert alert-${data.woo_test.status === 'success' ? 'success' : 'danger'}">
                            <i class="fas fa-${data.woo_test.status === 'success' ? 'check' : 'times'} me-2"></i>
                            ${data.woo_test.message}
                        </div>
                        
                        ${data.woo_test.product_count !== undefined ? 
                            `<p><strong>Products found:</strong> ${data.woo_test.product_count}</p>` : ''}
                    </div>
                </div>
            `;
        } else {
            html = `
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle me-2"></i>Connection test failed
                </div>
                <div class="alert alert-light">
                    <strong>Error Details:</strong><br>
                    ${data.message}
                </div>
                <div class="alert alert-info">
                    <strong>Troubleshooting:</strong>
                    <ul class="mb-0">
                        <li>Verify the tenant ID exists in the system</li>
                        <li>Check that the credential is enabled</li>
                        <li>Ensure WooCommerce API keys are correct</li>
                        <li>Verify network connectivity to WordPress site</li>
                    </ul>
                </div>
            `;
        }
        
        document.getElementById('testResultBody').innerHTML = html;
    })
    .catch(error => {
        document.getElementById('testResultBody').innerHTML = 
            `<div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>Test request failed: ${error.message}
            </div>`;
    });
}

// Auto-populate username based on tenant selection
document.addEventListener('DOMContentLoaded', function() {
    const tenantSelect = document.getElementById('tenant_id');
    const usernameField = document.getElementById('pck_username');
    const licenseField = document.getElementById('pck_license');
    
    tenantSelect.addEventListener('change', function() {
        const tenantId = this.value;
        console.log('Tenant selected:', tenantId);
        
        const locationMapping = {
            '4': {username: 'lade_pck', license: '12381'},
            '5': {username: 'gramyra_pck', license: '6715'},
            '6': {username: 'moan_pck', license: '5203'},
            '7': {username: 'namsos_pck', license: '6714'},
            '10': {username: 'frosta_pck', license: '14780'},
            '11': {username: 'hell_pck', license: '0000'},
            '12': {username: 'steinkjer_pck', license: '30221'},
            '16': {username: 'malvik_pck', license: '14946'},
        };
        
        if (locationMapping[tenantId]) {
            console.log('Setting username to:', locationMapping[tenantId].username);
            console.log('Setting license to:', locationMapping[tenantId].license);
            
            // Only auto-fill if fields are empty (don't overwrite user input)
            if (!usernameField.value.trim()) {
                usernameField.value = locationMapping[tenantId].username;
            }
            
            if (!licenseField.value.trim()) {
                licenseField.value = locationMapping[tenantId].license;
            }
            
            // Don't touch password field - let user manage it
        } else {
            console.log('No mapping found for tenant ID:', tenantId);
            // Only clear if fields are empty
            if (!usernameField.value.trim()) {
                usernameField.value = '';
            }
            if (!licenseField.value.trim()) {
                licenseField.value = '';
            }
        }
    });
});
</script>
@endpush