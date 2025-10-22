@extends('layouts.admin')

@section('content')
<div class="admin-dashboard-container">
    <!-- Header -->
    <div class="admin-header">
        <div class="d-flex align-items-center">
            <div class="header-icon">
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="ms-3">
                <h1 class="admin-title">Administrator Dashboard</h1>
                <p class="admin-subtitle">Systemadministrasjon og oversikt</p>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-row">
        <div class="stat-box stat-primary">
            <div class="stat-icon">
                <i class="bi bi-shop"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ $sites->count() }}</div>
                <div class="stat-label">Aktive lokasjoner</div>
            </div>
        </div>

        <div class="stat-box stat-success">
            <div class="stat-icon">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ $activeUsers }}</div>
                <div class="stat-label">Aktive brukere</div>
                <div class="stat-meta">{{ $totalUsers }} totalt</div>
            </div>
        </div>

        <div class="stat-box stat-info">
            <div class="stat-icon">
                <i class="bi bi-cart-check"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ $paidOrders }}</div>
                <div class="stat-label">Betalte ordre</div>
                <div class="stat-meta">{{ $totalOrders }} totalt</div>
            </div>
        </div>

        <div class="stat-box stat-warning">
            <div class="stat-icon">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ $unpaidOrders }}</div>
                <div class="stat-label">Ubetalte ordre</div>
            </div>
        </div>
    </div>

    <!-- Sites and Management -->
    <div class="content-grid">
        <!-- Sites List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-shop me-2"></i>
                    Lokasjoner
                </h3>
                <a href="{{ route('admin.sites.index') }}" class="btn-manage">
                    <i class="bi bi-gear me-1"></i>Administrer
                </a>
            </div>
            <div class="card-body">
                <div class="sites-list">
                    @foreach($sites as $site)
                    <div class="site-item">
                        <div class="site-info">
                            <div class="site-name">{{ $site->name }}</div>
                            <div class="site-url">{{ $site->url }}</div>
                        </div>
                        <div class="site-actions">
                            <span class="badge badge-site">ID: {{ $site->site_id }}</span>
                            <a href="{{ route('admin.statistics.site', $site->site_id) }}" class="btn-stats" title="Se statistikk">
                                <i class="bi bi-graph-up"></i>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-lightning me-2"></i>
                    Hurtigvalg
                </h3>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <a href="{{ route('admin.users.index') }}" class="action-item">
                        <div class="action-icon action-primary">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <div class="action-content">
                            <div class="action-title">Brukere</div>
                            <div class="action-desc">Administrer brukere</div>
                        </div>
                    </a>

                    <a href="{{ route('admin.sites.index') }}" class="action-item">
                        <div class="action-icon action-success">
                            <i class="bi bi-shop"></i>
                        </div>
                        <div class="action-content">
                            <div class="action-title">Lokasjoner</div>
                            <div class="action-desc">WooCommerce keys og innstillinger</div>
                        </div>
                    </a>

                    <a href="{{ route('admin.settings.index') }}" class="action-item">
                        <div class="action-icon action-info">
                            <i class="bi bi-gear"></i>
                        </div>
                        <div class="action-content">
                            <div class="action-title">Innstillinger</div>
                            <div class="action-desc">SMS, API og systeminnstillinger</div>
                        </div>
                    </a>

                    <a href="{{ route('admin.pck-soap.index') }}" class="action-item">
                        <div class="action-icon action-warning">
                            <i class="bi bi-hdd-network"></i>
                        </div>
                        <div class="action-content">
                            <div class="action-title">PCK SOAP</div>
                            <div class="action-desc">POS integrasjon og SOAP</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .admin-dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    /* Header */
    .admin-header {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(240, 147, 251, 0.3);
        color: white;
    }

    .header-icon {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        backdrop-filter: blur(10px);
    }

    .admin-title {
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
    }

    .admin-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        margin: 0.5rem 0 0 0;
    }

    /* Stats Row */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-box {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    .stat-box:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }

    .stat-icon {
        width: 64px;
        height: 64px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: white;
        flex-shrink: 0;
    }

    .stat-primary .stat-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .stat-success .stat-icon { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .stat-info .stat-icon { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    .stat-warning .stat-icon { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }

    .stat-content {
        flex: 1;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        color: #2d3748;
        line-height: 1;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 0.9rem;
        color: #718096;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-meta {
        font-size: 0.85rem;
        color: #a0aec0;
        margin-top: 0.25rem;
    }

    /* Content Grid */
    .content-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 2rem;
    }

    @media (max-width: 1100px) {
        .content-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Card */
    .card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .card-header {
        padding: 1.5rem;
        border-bottom: 2px solid #f7fafc;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #2d3748;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .btn-manage {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
    }

    .btn-manage:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }

    .card-body {
        padding: 1.5rem;
    }

    /* Sites List */
    .sites-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .site-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: #f7fafc;
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .site-item:hover {
        background: #edf2f7;
        transform: translateX(4px);
    }

    .site-info {
        flex: 1;
    }

    .site-name {
        font-weight: 700;
        color: #2d3748;
        font-size: 1.1rem;
        margin-bottom: 0.25rem;
    }

    .site-url {
        font-size: 0.85rem;
        color: #718096;
    }

    .site-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .badge-site {
        background: #e0e7ff;
        color: #5a67d8;
        padding: 0.25rem 0.75rem;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .btn-stats {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 1.1rem;
    }

    .btn-stats:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }

    /* Quick Actions */
    .quick-actions {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .action-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: #f7fafc;
        border-radius: 12px;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .action-item:hover {
        background: #edf2f7;
        transform: translateX(4px);
    }

    .action-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        flex-shrink: 0;
    }

    .action-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .action-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .action-info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    .action-warning { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }

    .action-content {
        flex: 1;
    }

    .action-title {
        font-weight: 700;
        color: #2d3748;
        font-size: 1.1rem;
        margin-bottom: 0.25rem;
    }

    .action-desc {
        font-size: 0.85rem;
        color: #718096;
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

    .stat-box, .card {
        animation: fadeIn 0.5s ease-out forwards;
    }

    .stat-box:nth-child(1) { animation-delay: 0.05s; }
    .stat-box:nth-child(2) { animation-delay: 0.1s; }
    .stat-box:nth-child(3) { animation-delay: 0.15s; }
    .stat-box:nth-child(4) { animation-delay: 0.2s; }

    /* Responsive */
    @media (max-width: 768px) {
        .stats-row {
            grid-template-columns: 1fr;
        }

        .admin-header {
            text-align: center;
        }

        .admin-title {
            font-size: 1.5rem;
        }
    }
</style>
@endpush
