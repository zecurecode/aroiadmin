<form method="post" action="{{ route('password.update') }}">
    @csrf
    @method('put')

    <div class="mb-3">
        <label for="current_password" class="form-label">Nåværende passord</label>
        <input
            type="password"
            class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
            id="current_password"
            name="current_password"
            autocomplete="current-password"
        >
        @error('current_password', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Nytt passord</label>
        <input
            type="password"
            class="form-control @error('password', 'updatePassword') is-invalid @enderror"
            id="password"
            name="password"
            autocomplete="new-password"
        >
        @error('password', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Bruk et sterkt passord med minst 8 tegn.</div>
    </div>

    <div class="mb-3">
        <label for="password_confirmation" class="form-label">Bekreft passord</label>
        <input
            type="password"
            class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror"
            id="password_confirmation"
            name="password_confirmation"
            autocomplete="new-password"
        >
        @error('password_confirmation', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex align-items-center gap-3">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-shield-check me-2"></i>Oppdater passord
        </button>

        @if (session('status') === 'password-updated')
            <div class="alert alert-success mb-0 py-2 px-3" role="alert">
                <i class="bi bi-check-circle me-2"></i>Passord oppdatert!
            </div>
        @endif
    </div>
</form>
