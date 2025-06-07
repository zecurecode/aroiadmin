@extends('layouts.admin')

@section('title', 'System Settings')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">System Settings</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSettingModal">
                    <i class="fas fa-plus me-2"></i>Add New Setting
                </button>
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

    <form action="{{ route('admin.settings.update') }}" method="POST" id="settingsForm">
        @csrf

        @foreach($settings as $category => $categorySettings)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        @switch($category)
                            @case('SMS Settings')
                                <i class="fas fa-sms me-2"></i>{{ $category }}
                                @break
                            @case('POS Settings')
                                <i class="fas fa-cash-register me-2"></i>{{ $category }}
                                @break
                            @case('Database Settings')
                                <i class="fas fa-database me-2"></i>{{ $category }}
                                @break
                            @case('Admin Settings')
                                <i class="fas fa-user-shield me-2"></i>{{ $category }}
                                @break
                            @default
                                <i class="fas fa-cog me-2"></i>{{ $category }}
                        @endswitch
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($categorySettings as $setting)
                            <div class="col-md-6 mb-3">
                                <label for="setting_{{ $setting->key }}" class="form-label">
                                    {{ $setting->description ?: ucfirst(str_replace('_', ' ', $setting->key)) }}
                                    @if($setting->key === 'teletopia_username' || $setting->key === 'teletopia_password')
                                        <button type="button" class="btn btn-sm btn-outline-info ms-2" onclick="testSMS()">
                                            <i class="fas fa-paper-plane"></i> Test SMS
                                        </button>
                                    @endif
                                </label>

                                @switch($setting->type)
                                    @case('password')
                                        <div class="input-group">
                                            <input type="password"
                                                   class="form-control @error('settings.'.$setting->key) is-invalid @enderror"
                                                   id="setting_{{ $setting->key }}"
                                                   name="settings[{{ $setting->key }}]"
                                                   value="{{ old('settings.'.$setting->key, $setting->value) }}">
                                            <button class="btn btn-outline-secondary" type="button"
                                                    onclick="togglePassword('setting_{{ $setting->key }}')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        @break

                                    @case('textarea')
                                        <textarea class="form-control @error('settings.'.$setting->key) is-invalid @enderror"
                                                  id="setting_{{ $setting->key }}"
                                                  name="settings[{{ $setting->key }}]"
                                                  rows="3">{{ old('settings.'.$setting->key, $setting->value) }}</textarea>
                                        @break

                                    @case('number')
                                        <input type="number"
                                               class="form-control @error('settings.'.$setting->key) is-invalid @enderror"
                                               id="setting_{{ $setting->key }}"
                                               name="settings[{{ $setting->key }}]"
                                               value="{{ old('settings.'.$setting->key, $setting->value) }}">
                                        @break

                                    @case('email')
                                        <input type="email"
                                               class="form-control @error('settings.'.$setting->key) is-invalid @enderror"
                                               id="setting_{{ $setting->key }}"
                                               name="settings[{{ $setting->key }}]"
                                               value="{{ old('settings.'.$setting->key, $setting->value) }}">
                                        @break

                                    @case('url')
                                        <input type="url"
                                               class="form-control @error('settings.'.$setting->key) is-invalid @enderror"
                                               id="setting_{{ $setting->key }}"
                                               name="settings[{{ $setting->key }}]"
                                               value="{{ old('settings.'.$setting->key, $setting->value) }}">
                                        @break

                                    @default
                                        <input type="text"
                                               class="form-control @error('settings.'.$setting->key) is-invalid @enderror"
                                               id="setting_{{ $setting->key }}"
                                               name="settings[{{ $setting->key }}]"
                                               value="{{ old('settings.'.$setting->key, $setting->value) }}">
                                @endswitch

                                @error('settings.'.$setting->key)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-muted">Key: {{ $setting->key }}</small>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="deleteSetting('{{ $setting->id }}', '{{ $setting->key }}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        <div class="d-flex justify-content-end mb-4">
            <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-save me-2"></i>Save All Settings
            </button>
        </div>
    </form>

    <!-- System Information -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>System Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <p><strong>Laravel Version:</strong> {{ app()->version() }}</p>
                        <p><strong>PHP Version:</strong> {{ PHP_VERSION }}</p>
                        <p><strong>Environment:</strong> {{ app()->environment() }}</p>
                        <p><strong>Debug Mode:</strong> {{ config('app.debug') ? 'Enabled' : 'Disabled' }}</p>
                        <p><strong>Timezone:</strong> {{ config('app.timezone') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-tools me-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="testSMS()">
                            <i class="fas fa-paper-plane me-2"></i>Test SMS Configuration
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="clearCache()">
                            <i class="fas fa-broom me-2"></i>Clear Application Cache
                        </button>
                        <a href="{{ route('admin.sites.index') }}" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-store me-2"></i>Manage Sites
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add New Setting Modal -->
<div class="modal fade" id="addSettingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Setting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.settings.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="key" class="form-label">Setting Key *</label>
                        <input type="text" class="form-control" id="key" name="key" required
                               placeholder="e.g., api_timeout">
                        <div class="form-text">Use lowercase with underscores</div>
                    </div>
                    <div class="mb-3">
                        <label for="value" class="form-label">Value</label>
                        <input type="text" class="form-control" id="value" name="value">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="description" name="description"
                               placeholder="Human readable description">
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Input Type *</label>
                        <select class="form-control" id="type" name="type" required>
                            <option value="text">Text</option>
                            <option value="password">Password</option>
                            <option value="email">Email</option>
                            <option value="url">URL</option>
                            <option value="number">Number</option>
                            <option value="textarea">Textarea</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Setting</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SMS Test Modal -->
<div class="modal fade" id="smsTestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test SMS Configuration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="testPhone" class="form-label">Phone Number *</label>
                    <input type="tel" class="form-control" id="testPhone"
                           placeholder="+4790123456" required>
                    <div class="form-text">Include country code (e.g., +47 for Norway)</div>
                </div>
                <div id="smsTestResult" class="alert" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendTestSMS()">
                    <i class="fas fa-paper-plane me-2"></i>Send Test SMS
                </button>
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

function testSMS() {
    const modal = new bootstrap.Modal(document.getElementById('smsTestModal'));
    modal.show();
}

function sendTestSMS() {
    const phone = document.getElementById('testPhone').value;
    const resultDiv = document.getElementById('smsTestResult');

    if (!phone) {
        showSMSResult('Please enter a phone number.', 'danger');
        return;
    }

    // Show loading
    showSMSResult('Sending test SMS...', 'info');

    fetch('{{ route("admin.settings.test-sms") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ phone: phone })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSMSResult(data.message, 'success');
        } else {
            showSMSResult(`Error: ${data.message}`, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showSMSResult('Failed to send test SMS.', 'danger');
    });
}

function showSMSResult(message, type) {
    const resultDiv = document.getElementById('smsTestResult');
    resultDiv.className = `alert alert-${type}`;
    resultDiv.textContent = message;
    resultDiv.style.display = 'block';
}

function deleteSetting(settingId, settingKey) {
    if (confirm(`Are you sure you want to delete the setting "${settingKey}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/settings/${settingId}`;

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';

        form.appendChild(csrfToken);
        form.appendChild(methodField);
        document.body.appendChild(form);
        form.submit();
    }
}

function clearCache() {
    if (confirm('Are you sure you want to clear the application cache?')) {
        // This would need a route to handle cache clearing
        alert('Cache clearing feature needs to be implemented.');
    }
}
</script>
@endsection
