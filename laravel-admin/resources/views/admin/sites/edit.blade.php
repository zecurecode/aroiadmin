@extends('layouts.admin')

@section('title', 'Edit Site')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Edit Site: {{ $site->name }}</h1>
                <div>
                    <a href="{{ route('admin.sites.show', $site) }}" class="btn btn-outline-info me-2">
                        <i class="fas fa-eye me-2"></i>View Site
                    </a>
                    <a href="{{ route('admin.sites.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Sites
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Site Details</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.sites.update', $site) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Site Name *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name', $site->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="site_id" class="form-label">WordPress Site ID *</label>
                                    <input type="number" class="form-control @error('site_id') is-invalid @enderror"
                                           id="site_id" name="site_id" value="{{ old('site_id', $site->site_id) }}" required>
                                    @error('site_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Unique WordPress multisite ID</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="url" class="form-label">Site URL *</label>
                            <input type="url" class="form-control @error('url') is-invalid @enderror"
                                   id="url" name="url" value="{{ old('url', $site->url) }}" required>
                            @error('url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="consumer_key" class="form-label">WooCommerce Consumer Key *</label>
                                    <input type="text" class="form-control @error('consumer_key') is-invalid @enderror"
                                           id="consumer_key" name="consumer_key" value="{{ old('consumer_key', $site->consumer_key) }}" required>
                                    @error('consumer_key')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="consumer_secret" class="form-label">WooCommerce Consumer Secret *</label>
                                    <input type="text" class="form-control @error('consumer_secret') is-invalid @enderror"
                                           id="consumer_secret" name="consumer_secret" value="{{ old('consumer_secret', $site->consumer_secret) }}" required>
                                    @error('consumer_secret')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="license" class="form-label">PCKasse License Number</label>
                                    <input type="number" class="form-control @error('license') is-invalid @enderror"
                                           id="license" name="license" value="{{ old('license', $site->license) }}" min="0">
                                    @error('license')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Leave 0 if no POS integration needed</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="active" name="active"
                                               {{ old('active', $site->active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="active">
                                            Site is Active
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('admin.sites.index') }}" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Site
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Site Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <p><strong>Created:</strong> {{ $site->created_at->format('M d, Y H:i') }}</p>
                        <p><strong>Last Updated:</strong> {{ $site->updated_at->format('M d, Y H:i') }}</p>
                        <p><strong>Status:</strong>
                            <span class="badge {{ $site->active ? 'bg-success' : 'bg-danger' }}">
                                {{ $site->active ? 'Active' : 'Inactive' }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>Associated Users
                    </h6>
                </div>
                <div class="card-body">
                    @if($site->users && $site->users->count() > 0)
                        <div class="small">
                            @foreach($site->users as $user)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>{{ $user->username }}</span>
                                    <span class="badge {{ $user->role === 'admin' ? 'bg-danger' : 'bg-primary' }}">
                                        {{ ucfirst($user->role) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-2">
                            <a href="{{ route('admin.sites.users', $site) }}" class="btn btn-sm btn-outline-info">
                                Manage Users
                            </a>
                        </div>
                    @else
                        <p class="text-muted small">No users assigned to this site.</p>
                        <a href="{{ route('admin.sites.users', $site) }}" class="btn btn-sm btn-outline-primary">
                            Assign Users
                        </a>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>Important Notes
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small text-muted">
                        <p>• Changing the license number will update all users assigned to this site</p>
                        <p>• Consumer key and secret are used for WooCommerce API integration</p>
                        <p>• Deactivating a site will prevent API access</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
