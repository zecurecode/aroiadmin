@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-map-marker-alt me-2"></i>Lokasjoner</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="{{ route('admin.locations.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i>Ny lokasjon
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Alle lokasjoner</h5>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Site ID</th>
                                <th>Navn</th>
                                <th>Adresse</th>
                                <th>Telefon</th>
                                <th>E-post</th>
                                <th>Lisens</th>
                                <th>Status</th>
                                <th>Handlinger</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($locations as $location)
                                <tr>
                                    <td>{{ $location->site_id }}</td>
                                    <td><strong>{{ $location->name }}</strong></td>
                                    <td>{{ $location->address ?: '-' }}</td>
                                    <td>{{ $location->phone ?: '-' }}</td>
                                    <td>{{ $location->email ?: '-' }}</td>
                                    <td>{{ $location->license }}</td>
                                    <td>
                                        @if($location->active)
                                            <span class="badge bg-success">Aktiv</span>
                                        @else
                                            <span class="badge bg-secondary">Inaktiv</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.locations.edit', $location->id) }}" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Rediger
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-map-marker-alt fa-2x mb-2"></i><br>
                                        Ingen lokasjoner funnet
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
