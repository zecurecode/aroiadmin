@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="bi bi-person-gear me-2"></i>Min profil
            </h2>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Profile Information Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-person-circle me-2"></i>Profilinformasjon
                    </h5>
                </div>
                <div class="card-body">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <!-- Update Password Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-shield-lock me-2"></i>Endre passord
                    </h5>
                </div>
                <div class="card-body">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>

        <!-- User Info Sidebar -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i>Brukerinformasjon
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Brukernavn</small>
                        <strong>{{ $user->username }}</strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Rolle</small>
                        <span class="badge {{ $user->isAdmin() ? 'bg-danger' : 'bg-primary' }}">
                            {{ $user->getRoleName() }}
                        </span>
                    </div>
                    @if($user->location)
                    <div class="mb-3">
                        <small class="text-muted d-block">Lokasjon</small>
                        <strong>{{ $user->location->name ?? $user->getLocationName() }}</strong>
                    </div>
                    @endif
                    <div class="mb-3">
                        <small class="text-muted d-block">Medlem siden</small>
                        <strong>{{ $user->created_at ? $user->created_at->format('d.m.Y') : 'N/A' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
