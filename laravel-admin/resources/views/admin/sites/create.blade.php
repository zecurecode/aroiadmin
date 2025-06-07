@extends('layouts.admin')

@section('title', 'Add New Site')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Add New Site</h1>
                <a href="{{ route('admin.sites.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Sites
                </a>
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
                    <form action="{{ route('admin.sites.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Site Name *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="site_id" class="form-label">WordPress Site ID *</label>
                                    <input type="number" class="form-control @error('site_id') is-invalid @enderror"
                                           id="site_id" name="site_id" value="{{ old('site_id') }}" required>
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
                                   id="url" name="url" value="{{ old('url') }}" required
                                   placeholder="https://example.aroiasia.no">
                            @error('url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="consumer_key" class="form-label">WooCommerce Consumer Key *</label>
                                    <input type="text" class="form-control @error('consumer_key') is-invalid @enderror"
                                           id="consumer_key" name="consumer_key" value="{{ old('consumer_key') }}" required>
                                    @error('consumer_key')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="consumer_secret" class="form-label">WooCommerce Consumer Secret *</label>
                                    <input type="text" class="form-control @error('consumer_secret') is-invalid @enderror"
                                           id="consumer_secret" name="consumer_secret" value="{{ old('consumer_secret') }}" required>
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
                                           id="license" name="license" value="{{ old('license', 0) }}" min="0">
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
                                               {{ old('active', true) ? 'checked' : '' }}>
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
                                <i class="fas fa-save me-2"></i>Create Site
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
                        <i class="fas fa-info-circle me-2"></i>How to Get WooCommerce Credentials
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <ol>
                            <li class="mb-2">Login to your WooCommerce site admin</li>
                            <li class="mb-2">Go to <strong>WooCommerce → Settings</strong></li>
                            <li class="mb-2">Click the <strong>Advanced</strong> tab</li>
                            <li class="mb-2">Click <strong>REST API</strong></li>
                            <li class="mb-2">Click <strong>Add Key</strong></li>
                            <li class="mb-2">Set permissions to <strong>Read/Write</strong></li>
                            <li class="mb-2">Copy the generated Consumer Key and Secret</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-lightbulb me-2"></i>Site ID Reference
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <strong>Existing Site IDs:</strong>
                        <ul class="mt-2">
                            <li>Lade: 4</li>
                            <li>Gramyra: 5</li>
                            <li>Moan: 6</li>
                            <li>Namsos: 7</li>
                            <li>Frosta: 10</li>
                            <li>Hell: 11</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
