@extends('layouts.admin')

@section('title', 'Administrer Steder')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-store me-2"></i>Administrer Steder</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="{{ route('admin.sites.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i>Nytt Sted
            </a>
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

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Alle steder</h5>
    </div>
    <div class="card-body">
        @if($sites->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Stedsnavn</th>
                            <th>Sted ID</th>
                            <th>URL</th>
                            <th>Lisens</th>
                            <th>Brukere</th>
                            <th>Status</th>
                            <th>Handlinger</th>
                        </tr>
                    </thead>
                        <tbody>
                            @foreach($sites as $site)
                                <tr>
                                    <td><strong>{{ $site->name }}</strong></td>
                                    <td>
                                        <span class="badge bg-info">{{ $site->site_id }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ $site->url }}" target="_blank" class="text-decoration-none">
                                            {{ $site->url }}
                                            <i class="fas fa-external-link-alt ms-1"></i>
                                        </a>
                                    </td>
                                    <td>
                                        @if($site->license > 0)
                                            <span class="badge bg-success">{{ $site->license }}</span>
                                        @else
                                            <span class="badge bg-secondary">Ingen Lisens</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            {{ $site->users_count ?? 0 }} brukere
                                        </span>
                                        <a href="{{ route('admin.sites.users', $site) }}" class="btn btn-sm btn-outline-info ms-1">
                                            <i class="fas fa-users"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge {{ $site->active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $site->active ? 'Aktiv' : 'Inaktiv' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.sites.show', $site) }}"
                                               class="btn btn-outline-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.sites.edit', $site) }}"
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="deleteSite({{ $site->id }})"
                                                    class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $sites->links() }}
                </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-store fa-3x text-muted mb-3"></i>
                <p class="text-muted">Ingen steder funnet.</p>
                <a href="{{ route('admin.sites.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Opprett Første Sted
                </a>
            </div>
        @endif
    </div>
</div>

<!-- WooCommerce Credentials Info -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-info-circle me-2"></i>WooCommerce Integrasjon Info
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Consumer Keys & Secrets</h6>
                <p class="small text-muted">
                    Dette er WooCommerce API-legitimasjonen som brukes til å koble til hvert sted.
                    Consumer keys og secrets fås fra WooCommerce → Innstillinger → Avansert → REST API.
                </p>
            </div>
            <div class="col-md-6">
                <h6>PCKasse Lisens Numre</h6>
                <p class="small text-muted">
                    Lisens numre brukes til POS-system integrasjon med PCKasse.
                    Disse numrene tildeles til hver lokasjon for ordrebehandling.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slett Sted Modal -->
<div class="modal fade" id="deleteSiteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Slett Sted</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Er du sikker på at du vil slette dette stedet? Denne handlingen kan ikke angres.</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Advarsel:</strong> Sletting av et sted vil påvirke alle brukere som er tildelt det.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <form id="deleteSiteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Slett Sted</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteSite(siteId) {
    const form = document.getElementById('deleteSiteForm');
    form.action = `/admin/sites/${siteId}`;

    const modal = new bootstrap.Modal(document.getElementById('deleteSiteModal'));
    modal.show();
}
</script>
@endsection
