@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Opprett ny bruker</h1>
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
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="username" class="form-label">Brukernavn</label>
                        <input type="text" class="form-control @error('username') is-invalid @enderror" 
                               id="username" name="username" value="{{ old('username') }}" required autofocus>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Passord</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="password" name="password" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Bekreft passord</label>
                        <input type="password" class="form-control" 
                               id="password_confirmation" name="password_confirmation" required>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Rolle</label>
                        <select class="form-select @error('role') is-invalid @enderror" 
                                id="role" name="role" required>
                            <option value="">Velg rolle</option>
                            <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>Bruker</option>
                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrator</option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="site-selection" style="{{ old('role', 'user') == 'admin' ? 'display:none;' : '' }}">
                        <label for="siteid" class="form-label">Lokasjon</label>
                        <select class="form-select @error('siteid') is-invalid @enderror" 
                                id="siteid" name="siteid">
                            <option value="">Velg lokasjon</option>
                            @if(isset($sites))
                                @foreach($sites as $site)
                                    <option value="{{ $site->site_id }}" 
                                            {{ old('siteid') == $site->site_id ? 'selected' : '' }}>
                                        {{ $site->name }}
                                    </option>
                                @endforeach
                            @else
                                <!-- Fallback hardcoded options if $sites is not available -->
                                <option value="7" {{ old('siteid') == '7' ? 'selected' : '' }}>Namsos</option>
                                <option value="4" {{ old('siteid') == '4' ? 'selected' : '' }}>Lade</option>
                                <option value="6" {{ old('siteid') == '6' ? 'selected' : '' }}>Moan</option>
                                <option value="5" {{ old('siteid') == '5' ? 'selected' : '' }}>Gramyra</option>
                                <option value="10" {{ old('siteid') == '10' ? 'selected' : '' }}>Frosta</option>
                                <option value="11" {{ old('siteid') == '11' ? 'selected' : '' }}>Hell</option>
                                <option value="13" {{ old('siteid') == '13' ? 'selected' : '' }}>Steinkjer</option>
                            @endif
                        </select>
                        @error('siteid')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="license-input" style="{{ old('role', 'user') == 'admin' ? 'display:none;' : '' }}">
                        <label for="license" class="form-label">PCKasse Lisens</label>
                        <input type="number" class="form-control @error('license') is-invalid @enderror" 
                               id="license" name="license" value="{{ old('license') }}">
                        @error('license')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Standard lisenser: Namsos: 6714, Lade: 12381, Moan: 5203, Gramyra: 6715, Frosta: 14780, Steinkjer: 30221
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Opprett bruker
                        </button>
                    </div>
                </form>
            </div>
        </div>
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

// Auto-fill license based on site selection
document.getElementById('siteid').addEventListener('change', function() {
    const licenseField = document.getElementById('license');
    
    @if(isset($sites))
        // Use data from database
        const siteLicenses = {
            @foreach($sites as $site)
                '{{ $site->site_id }}': '{{ $site->license }}',
            @endforeach
        };
        
        if (siteLicenses[this.value]) {
            licenseField.value = siteLicenses[this.value];
        }
    @else
        // Fallback to hardcoded licenses
        const licenses = {
            '7': '6714',   // Namsos
            '4': '12381',  // Lade
            '6': '5203',   // Moan
            '5': '6715',   // Gramyra
            '10': '14780', // Frosta
            '13': '30221'  // Steinkjer
        };
        
        if (licenses[this.value]) {
            licenseField.value = licenses[this.value];
        }
    @endif
});
</script>
@endpush
@endsection