@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Rediger bruker: {{ $user->username }}</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Tilbake
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="username" class="form-label">Brukernavn</label>
                        <input type="text" class="form-control @error('username') is-invalid @enderror" 
                               id="username" name="username" value="{{ old('username', $user->username) }}" required>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Nytt passord</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="password" name="password">
                        <div class="form-text">La stå tom for å beholde eksisterende passord</div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Bekreft nytt passord</label>
                        <input type="password" class="form-control" 
                               id="password_confirmation" name="password_confirmation">
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Rolle</label>
                        <select class="form-select @error('role') is-invalid @enderror" 
                                id="role" name="role" required>
                            <option value="user" {{ old('role', $user->role ?? ($user->isAdmin() ? 'admin' : 'user')) == 'user' ? 'selected' : '' }}>Bruker</option>
                            <option value="admin" {{ old('role', $user->role ?? ($user->isAdmin() ? 'admin' : 'user')) == 'admin' ? 'selected' : '' }}>Administrator</option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="site-selection" style="{{ old('role', $user->role ?? ($user->isAdmin() ? 'admin' : 'user')) == 'admin' ? 'display:none;' : '' }}">
                        <label for="siteid" class="form-label">Lokasjon</label>
                        <select class="form-select @error('siteid') is-invalid @enderror" 
                                id="siteid" name="siteid">
                            <option value="">Velg lokasjon</option>
                            @if(isset($sites))
                                @foreach($sites as $site)
                                    <option value="{{ $site->site_id }}" 
                                            {{ old('siteid', $user->siteid) == $site->site_id ? 'selected' : '' }}>
                                        {{ $site->name }}
                                    </option>
                                @endforeach
                            @else
                                <!-- Fallback hardcoded options if $sites is not available -->
                                <option value="7" {{ old('siteid', $user->siteid) == '7' ? 'selected' : '' }}>Namsos</option>
                                <option value="4" {{ old('siteid', $user->siteid) == '4' ? 'selected' : '' }}>Lade</option>
                                <option value="6" {{ old('siteid', $user->siteid) == '6' ? 'selected' : '' }}>Moan</option>
                                <option value="5" {{ old('siteid', $user->siteid) == '5' ? 'selected' : '' }}>Gramyra</option>
                                <option value="10" {{ old('siteid', $user->siteid) == '10' ? 'selected' : '' }}>Frosta</option>
                                <option value="11" {{ old('siteid', $user->siteid) == '11' ? 'selected' : '' }}>Hell</option>
                                <option value="13" {{ old('siteid', $user->siteid) == '13' ? 'selected' : '' }}>Steinkjer</option>
                            @endif
                        </select>
                        @error('siteid')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="license-input" style="{{ old('role', $user->role ?? ($user->isAdmin() ? 'admin' : 'user')) == 'admin' ? 'display:none;' : '' }}">
                        <label for="license" class="form-label">PCKasse Lisens</label>
                        <input type="number" class="form-control @error('license') is-invalid @enderror" 
                               id="license" name="license" value="{{ old('license', $user->license) }}">
                        @error('license')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Standard lisenser: Namsos: 6714, Lade: 12381, Moan: 5203, Gramyra: 6715, Frosta: 14780, Steinkjer: 30221
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <div>
                            @if(auth()->id() !== $user->id)
                                <button type="button" class="btn btn-info" onclick="impersonateUser({{ $user->id }})">
                                    <i class="fas fa-user-secret me-1"></i>Logg inn som bruker
                                </button>
                            @endif
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Lagre endringer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if(auth()->id() !== $user->id)
        <div class="card mt-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Farlig sone</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" 
                      onsubmit="return confirm('Er du sikker på at du vil slette denne brukeren? Dette kan ikke angres.')">
                    @csrf
                    @method('DELETE')
                    <p>Når du sletter en bruker, vil alle deres data bli permanent fjernet.</p>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i>Slett bruker
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.getElementById('role').addEventListener('change', function() {
    const siteSelection = document.getElementById('site-selection');
    const licenseInput = document.getElementById('license-input');
    
    if (this.value === 'admin') {
        siteSelection.style.display = 'none';
        licenseInput.style.display = 'none';
        document.getElementById('siteid').value = '0';
        document.getElementById('license').value = '9999';
    } else {
        siteSelection.style.display = 'block';
        licenseInput.style.display = 'block';
    }
});

function impersonateUser(userId) {
    if (confirm('Er du sikker på at du vil logge inn som denne brukeren?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/admin/users/' + userId + '/impersonate';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
@endsection