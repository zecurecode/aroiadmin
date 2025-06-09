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
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Lokasjonsfunksjonaliteten er under utvikling. Denne siden vil snart inneholde oversikt over alle lokasjoner.
                </div>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Navn</th>
                                <th>Adresse</th>
                                <th>Status</th>
                                <th>Opprettet</th>
                                <th>Handlinger</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-map-marker-alt fa-2x mb-2"></i><br>
                                    Ingen lokasjoner funnet
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
