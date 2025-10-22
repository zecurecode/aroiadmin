<form method="post" action="{{ route('profile.update') }}">
    @csrf
    @method('patch')

    <div class="mb-3">
        <label for="username" class="form-label">Brukernavn</label>
        <input
            type="text"
            class="form-control @error('username') is-invalid @enderror"
            id="username"
            name="username"
            value="{{ old('username', $user->username) }}"
            required
            autofocus
        >
        @error('username')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Dette brukernavnet vil brukes for innlogging.</div>
    </div>

    <div class="mb-3">
        <label for="siteid" class="form-label">Sted ID</label>
        <input
            type="text"
            class="form-control bg-light"
            id="siteid"
            value="{{ $user->siteid }}"
            disabled
            readonly
        >
        <div class="form-text">Dette feltet kan ikke endres.</div>
    </div>

    <div class="d-flex align-items-center gap-3">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-2"></i>Lagre endringer
        </button>

        @if (session('status') === 'profile-updated')
            <div class="alert alert-success mb-0 py-2 px-3" role="alert">
                <i class="bi bi-check-circle me-2"></i>Lagret!
            </div>
        @endif
    </div>
</form>
