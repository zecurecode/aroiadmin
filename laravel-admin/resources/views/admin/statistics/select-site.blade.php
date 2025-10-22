@extends('layouts.admin')

@section('content')
<div class="site-selector-container">
    <div class="selector-header">
        <div class="header-icon-wrapper">
            <i class="bi bi-graph-up-arrow"></i>
        </div>
        <div class="ms-3">
            <h1 class="selector-title">Velg lokasjon for statistikk</h1>
            <p class="selector-subtitle">Velg hvilken avdeling du vil se statistikk for</p>
        </div>
    </div>

    @if($sites->count() > 0)
    <div class="sites-grid">
        @foreach($sites as $site)
        <a href="{{ route('admin.statistics.site', $site->site_id) }}" class="site-card">
            <div class="site-card-icon">
                <i class="bi bi-shop"></i>
            </div>
            <div class="site-card-content">
                <h3 class="site-name">{{ $site->name }}</h3>
                <p class="site-url">{{ $site->url }}</p>
                <div class="site-meta">
                    <span class="site-id-badge">Site ID: {{ $site->site_id }}</span>
                    @if($site->license)
                    <span class="site-license-badge">License: {{ $site->license }}</span>
                    @endif
                </div>
            </div>
            <div class="site-card-arrow">
                <i class="bi bi-arrow-right-circle"></i>
            </div>
        </a>
        @endforeach
    </div>
    @else
    <div class="empty-state">
        <div class="empty-icon">
            <i class="bi bi-inbox"></i>
        </div>
        <h3>Ingen aktive steder funnet</h3>
        <p>Det finnes ingen aktive steder i systemet. Kontakt systemadministrator.</p>
    </div>
    @endif
</div>
@endsection

@push('styles')
<style>
    .site-selector-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .selector-header {
        display: flex;
        align-items: center;
        margin-bottom: 3rem;
        padding: 2rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        color: white;
    }

    .header-icon-wrapper {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        backdrop-filter: blur(10px);
        flex-shrink: 0;
    }

    .selector-title {
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
        color: white;
    }

    .selector-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        color: rgba(255, 255, 255, 0.9);
        margin: 0.5rem 0 0 0;
    }

    .sites-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: 1.5rem;
    }

    @media (max-width: 768px) {
        .sites-grid {
            grid-template-columns: 1fr;
        }
    }

    .site-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        color: inherit;
        position: relative;
        overflow: hidden;
    }

    .site-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.3s ease;
    }

    .site-card:hover::before {
        transform: scaleX(1);
    }

    .site-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 40px rgba(102, 126, 234, 0.2);
    }

    .site-card-icon {
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: white;
        flex-shrink: 0;
        transition: transform 0.3s ease;
    }

    .site-card:hover .site-card-icon {
        transform: scale(1.1) rotate(5deg);
    }

    .site-card-content {
        flex: 1;
    }

    .site-name {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
        color: #2d3748;
    }

    .site-url {
        font-size: 0.9rem;
        color: #718096;
        margin: 0 0 1rem 0;
        word-break: break-all;
    }

    .site-meta {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .site-id-badge,
    .site-license-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .site-id-badge {
        background: #e0e7ff;
        color: #5a67d8;
    }

    .site-license-badge {
        background: #d1fae5;
        color: #065f46;
    }

    .site-card-arrow {
        font-size: 2rem;
        color: #cbd5e0;
        flex-shrink: 0;
        transition: all 0.3s ease;
    }

    .site-card:hover .site-card-arrow {
        color: #667eea;
        transform: translateX(8px);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .empty-icon {
        font-size: 5rem;
        color: #cbd5e0;
        margin-bottom: 1rem;
    }

    .empty-state h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 0.5rem;
    }

    .empty-state p {
        font-size: 1rem;
        color: #718096;
        margin: 0;
    }

    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .site-card {
        animation: fadeIn 0.5s ease-out forwards;
    }

    .site-card:nth-child(1) { animation-delay: 0.05s; }
    .site-card:nth-child(2) { animation-delay: 0.1s; }
    .site-card:nth-child(3) { animation-delay: 0.15s; }
    .site-card:nth-child(4) { animation-delay: 0.2s; }
    .site-card:nth-child(5) { animation-delay: 0.25s; }
    .site-card:nth-child(6) { animation-delay: 0.3s; }

    @media (max-width: 768px) {
        .selector-header {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }

        .selector-title {
            font-size: 1.5rem;
        }

        .site-card {
            flex-direction: column;
            text-align: center;
        }

        .site-card-arrow {
            display: none;
        }
    }
</style>
@endpush
