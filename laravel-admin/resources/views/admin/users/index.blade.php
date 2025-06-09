@extends('layouts.admin')

@section('title', 'Administrer Brukere')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-users me-2"></i>Administrer Brukere</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i>Ny Bruker
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
        <h5 class="card-title mb-0">Alle brukere</h5>
    </div>
    <div class="card-body">
        @if($users->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Brukernavn</th>
                            <th>Navn</th>
                            <th>E-post</th>
                            <th>Sted</th>
                            <th>Lisens</th>
                            <th>Rolle</th>
                            <th>Opprettet</th>
                            <th>Handlinger</th>
                        </tr>
                    </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td><strong>{{ $user->username }}</strong></td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email ?? 'N/A' }}</td>
                                    <td>
                                        @if($user->site)
                                            <span class="badge bg-info">{{ $user->site->name }}</span>
                                        @else
                                            <span class="badge bg-secondary">Intet Sted</span>
                                        @endif
                                    </td>
                                    <td>{{ $user->license ?: 'N/A' }}</td>
                                    <td>
                                        <span class="badge {{ $user->role === 'admin' ? 'bg-danger' : 'bg-primary' }}">
                                            {{ $user->role === 'admin' ? 'Admin' : 'Bruker' }}
                                        </span>
                                    </td>
                                    <td>{{ $user->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.users.show', $user) }}"
                                               class="btn btn-outline-info btn-sm"
                                               title="Vis bruker">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.users.edit', $user) }}"
                                               class="btn btn-outline-primary btn-sm"
                                               title="Rediger bruker">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($user->id !== auth()->id())
                                                <form method="POST" action="{{ route('admin.users.impersonate', $user) }}" style="display: inline;">
                                                    @csrf
                                                    <button type="submit"
                                                            class="btn btn-outline-warning btn-sm"
                                                            title="Impersoner bruker">
                                                        <i class="fas fa-user-secret"></i>
                                                    </button>
                                                </form>
                                                <button onclick="deleteUser({{ $user->id }})"
                                                        class="btn btn-outline-danger btn-sm"
                                                        title="Slett bruker">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $users->links() }}
            </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <p class="text-muted">Ingen brukere funnet.</p>
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Opprett Første Bruker
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Slett Bruker Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Slett Bruker</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Er du sikker på at du vil slette denne brukeren? Denne handlingen kan ikke angres.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <form id="deleteUserForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Slett Bruker</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteUser(userId) {
    const form = document.getElementById('deleteUserForm');
    form.action = `/admin/users/${userId}`;

    const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    modal.show();
}
</script>
@endsection
