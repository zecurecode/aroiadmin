@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-map-marker-alt me-2"></i>Rediger lokasjon: {{ $location->name }}</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="{{ route('admin.locations.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Tilbake
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Rediger lokasjonsinformasjon</h5>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.locations.update', $location->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Navn <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $location->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="site_id" class="form-label">Site ID</label>
                            <input type="text" class="form-control" id="site_id" 
                                   value="{{ $location->site_id }}" readonly disabled>
                            <small class="text-muted">Site ID kan ikke endres</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Adresse</label>
                        <input type="text" class="form-control @error('address') is-invalid @enderror" 
                               id="address" name="address" value="{{ old('address', $location->address) }}"
                               placeholder="F.eks. Storgata 1, 1234 Oslo">
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Telefon</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" name="phone" value="{{ old('phone', $location->phone) }}"
                                   placeholder="+47 12 34 56 78">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">E-post</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email', $location->email) }}"
                                   placeholder="lokasjon@example.com">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="license" class="form-label">PCKasse Lisens <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('license') is-invalid @enderror" 
                                   id="license" name="license" value="{{ old('license', $location->license) }}" required>
                            @error('license')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label d-block">Status</label>
                            <div class="form-check form-switch">
                                <input type="hidden" name="active" value="0">
                                <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                                       {{ old('active', $location->active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="active">
                                    Lokasjon er aktiv
                                </label>
                            </div>
                            <small class="text-muted">Inaktive lokasjoner vises ikke på forsiden</small>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.locations.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Avbryt
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Lagre endringer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Informasjon</h5>
            </div>
            <div class="card-body">
                <p><strong>Site ID:</strong> {{ $location->site_id }}</p>
                <p><strong>Opprettet:</strong> {{ $location->created_at ? $location->created_at->format('d.m.Y H:i') : 'Ukjent' }}</p>
                <p><strong>Sist oppdatert:</strong> {{ $location->updated_at ? $location->updated_at->format('d.m.Y H:i') : 'Ukjent' }}</p>
                
                <hr>
                
                <h6>Tilknyttede brukere</h6>
                <p class="text-muted">{{ $location->users->count() }} bruker(e) tilknyttet denne lokasjonen</p>
                
                @if($location->users->count() > 0)
                    <ul class="list-unstyled mb-0">
                        @foreach($location->users->take(5) as $user)
                            <li><i class="fas fa-user me-1"></i>{{ $user->username }}</li>
                        @endforeach
                        @if($location->users->count() > 5)
                            <li class="text-muted">... og {{ $location->users->count() - 5 }} til</li>
                        @endif
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection