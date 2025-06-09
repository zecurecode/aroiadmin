@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-map-marker-alt me-2"></i>Ny lokasjon</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="{{ route('admin.locations.index') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Tilbake
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Opprett ny lokasjon</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Lokasjonsfunksjonaliteten er under utvikling. Denne siden vil snart inneholde skjema for å opprette nye lokasjoner.
                </div>

                <form method="POST" action="{{ route('admin.locations.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Navn</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Navn på lokasjon" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Adresse</label>
                        <input type="text" class="form-control" id="address" name="address" placeholder="Adresse" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" disabled>
                            <option value="active">Aktiv</option>
                            <option value="inactive">Inaktiv</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" disabled>
                            <i class="fas fa-save me-1"></i>Lagre lokasjon
                        </button>
                        <a href="{{ route('admin.locations.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Avbryt
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
