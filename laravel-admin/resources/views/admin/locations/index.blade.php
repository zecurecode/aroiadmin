@extends('layouts.admin')

@section('content')
{{-- Debug info --}}
@if(config('app.debug'))
<div class="alert alert-info">
    <small>Debug: Method spoofing is {{ method_field('DELETE') ? 'enabled' : 'disabled' }}</small>
</div>
@endif
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
                
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Site ID</th>
                                <th>Navn</th>
                                <th>Gruppe</th>
                                <th>Rekkefølge</th>
                                <th>Adresse</th>
                                <th>Telefon</th>
                                <th>Bestillings-URL</th>
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
                                    <td>
                                        @if($location->group_name)
                                            <span class="badge bg-info">{{ $location->group_name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $location->display_order }}</td>
                                    <td>{{ $location->address ?: '-' }}</td>
                                    <td>{{ $location->phone ?: '-' }}</td>
                                    <td>
                                        @if($location->order_url)
                                            <a href="{{ $location->order_url }}" target="_blank" class="text-truncate d-inline-block" style="max-width: 200px;">
                                                {{ $location->order_url }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $location->license }}
                                        @if($location->users()->count() > 0)
                                            <br><small class="text-muted">{{ $location->users()->count() }} bruker(e)</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($location->active)
                                            <span class="badge bg-success">Aktiv</span>
                                        @else
                                            <span class="badge bg-secondary">Inaktiv</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.locations.edit', $location->id) }}" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Rediger
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    onclick="deleteLocation({{ $location->id }}, '{{ $location->name }}')">
                                                <i class="fas fa-trash"></i> Slett
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
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

{{-- Hidden form for deletion --}}
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
function deleteLocation(id, name) {
    if (confirm('Er du sikker på at du vil slette lokasjonen ' + name + '? Dette kan ikke angres.')) {
        const form = document.getElementById('delete-form');
        form.action = '{{ route("admin.locations.index") }}/' + id;
        console.log('Submitting delete form to:', form.action);
        form.submit();
    }
}
</script>
@endpush
