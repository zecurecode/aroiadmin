@extends('layouts.admin')

@section('content')
<div class="statistics-container">
    <!-- Header -->
    <div class="stats-header">
        <div class="d-flex align-items-center">
            @if(auth()->user() && auth()->user()->siteid == 0)
            <a href="{{ route('admin.statistics.index') }}" class="btn-back me-3" title="Tilbake til velger">
                <i class="bi bi-arrow-left"></i>
            </a>
            @endif
            <div class="header-icon-wrapper">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div class="ms-3">
                <h1 class="stats-title mb-1">Salgsstatistikk</h1>
                <p class="stats-subtitle mb-0">{{ $site->name }} - <span class="text-muted">Oppdatert {{ now()->format('d.m.Y H:i') }}</span></p>
            </div>
        </div>
        <button type="button" class="btn-refresh" onclick="location.reload()" title="Oppdater statistikk">
            <i class="fas fa-sync-alt"></i>
            <span class="ms-2 d-none d-md-inline">Oppdater</span>
        </button>
    </div>

    <!-- Warning for Pending Orders -->
    @if($statistics['pending']['count'] > 0)
    <div class="pending-alert animate-pulse">
        <div class="pending-alert-icon">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <div class="pending-alert-content">
            <h5 class="pending-alert-title">Advarsel: {{ $statistics['pending']['count'] }} ventende ordre funnet!</h5>
            <p class="pending-alert-text">
                Det finnes ordre med status "pending" i WooCommerce. Dette kan tyde på en feil i betalingsprosessen.
            </p>
            <div class="pending-orders-list">
                @foreach($statistics['pending']['orders'] as $pendingOrder)
                <div class="pending-order-item">
                    <span class="pending-order-badge">#{{ $pendingOrder['number'] ?? $pendingOrder['id'] }}</span>
                    <span class="pending-order-amount">{{ number_format($pendingOrder['total'], 2, ',', ' ') }} kr</span>
                    <span class="pending-order-date">{{ \Carbon\Carbon::parse($pendingOrder['date_created'])->format('d.m.Y H:i') }}</span>
                </div>
                @endforeach
            </div>
        </div>
        <button type="button" class="pending-alert-close" data-bs-dismiss="alert">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    @endif

    <!-- Main Statistics Grid -->
    <div class="stats-grid">
        <!-- Year Statistics Card -->
        <div class="stat-card stat-card-year">
            <div class="stat-card-header">
                <div class="stat-card-icon year-icon">
                    <i class="bi bi-calendar-range"></i>
                </div>
                <div>
                    <h3 class="stat-card-title">Salg i år</h3>
                    <p class="stat-card-period">1. januar - {{ now()->format('d. F') }} {{ date('Y') }}</p>
                </div>
            </div>

            <div class="stat-card-body">
                <div class="stat-main">
                    <div class="stat-main-label">Totalt salg</div>
                    <div class="stat-main-value year-value">
                        {{ number_format($statistics['year']['revenue'], 0, ',', ' ') }}
                        <span class="stat-currency">kr</span>
                    </div>
                </div>

                <div class="stat-metrics">
                    <div class="stat-metric">
                        <div class="stat-metric-icon">
                            <i class="bi bi-cart-check"></i>
                        </div>
                        <div>
                            <div class="stat-metric-value">{{ $statistics['year']['count'] }}</div>
                            <div class="stat-metric-label">Ordre</div>
                        </div>
                    </div>

                    <div class="stat-metric">
                        <div class="stat-metric-icon">
                            <i class="bi bi-calculator"></i>
                        </div>
                        <div>
                            <div class="stat-metric-value">{{ $statistics['year']['count'] > 0 ? number_format($statistics['year']['revenue'] / $statistics['year']['count'], 0, ',', ' ') : 0 }}</div>
                            <div class="stat-metric-label">Snitt per ordre</div>
                        </div>
                    </div>
                </div>

                <div class="stat-comparison">
                    <div class="comparison-label">Sammenligning med {{ date('Y') - 1 }}</div>
                    <div class="comparison-content">
                        <div class="comparison-badge {{ $statistics['year']['change_direction'] === 'up' ? 'badge-up' : 'badge-down' }}">
                            <i class="bi bi-{{ $statistics['year']['change_direction'] === 'up' ? 'arrow-up' : 'arrow-down' }}-circle-fill"></i>
                            <span>{{ number_format(abs($statistics['year']['change_percent']), 1, ',', ' ') }}%</span>
                        </div>
                        <div class="comparison-details">
                            <span class="comparison-value">{{ number_format($statistics['previous_year']['revenue'], 0, ',', ' ') }} kr</span>
                            <span class="comparison-count">{{ $statistics['previous_year']['count'] }} ordre</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Month Statistics Card -->
        <div class="stat-card stat-card-month">
            <div class="stat-card-header">
                <div class="stat-card-icon month-icon">
                    <i class="bi bi-calendar-month"></i>
                </div>
                <div>
                    <h3 class="stat-card-title">Salg denne måneden</h3>
                    <p class="stat-card-period">{{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</p>
                </div>
            </div>

            <div class="stat-card-body">
                <div class="stat-main">
                    <div class="stat-main-label">Totalt salg</div>
                    <div class="stat-main-value month-value">
                        {{ number_format($statistics['month']['revenue'], 0, ',', ' ') }}
                        <span class="stat-currency">kr</span>
                    </div>
                </div>

                <div class="stat-metrics">
                    <div class="stat-metric">
                        <div class="stat-metric-icon">
                            <i class="bi bi-cart-check"></i>
                        </div>
                        <div>
                            <div class="stat-metric-value">{{ $statistics['month']['count'] }}</div>
                            <div class="stat-metric-label">Ordre</div>
                        </div>
                    </div>

                    <div class="stat-metric">
                        <div class="stat-metric-icon">
                            <i class="bi bi-calculator"></i>
                        </div>
                        <div>
                            <div class="stat-metric-value">{{ $statistics['month']['count'] > 0 ? number_format($statistics['month']['revenue'] / $statistics['month']['count'], 0, ',', ' ') : 0 }}</div>
                            <div class="stat-metric-label">Snitt per ordre</div>
                        </div>
                    </div>
                </div>

                <div class="stat-comparison">
                    <div class="comparison-label">Sammenligning med forrige måned</div>
                    <div class="comparison-content">
                        <div class="comparison-badge {{ $statistics['month']['change_direction'] === 'up' ? 'badge-up' : 'badge-down' }}">
                            <i class="bi bi-{{ $statistics['month']['change_direction'] === 'up' ? 'arrow-up' : 'arrow-down' }}-circle-fill"></i>
                            <span>{{ number_format(abs($statistics['month']['change_percent']), 1, ',', ' ') }}%</span>
                        </div>
                        <div class="comparison-details">
                            <span class="comparison-value">{{ number_format($statistics['previous_month']['revenue'], 0, ',', ' ') }} kr</span>
                            <span class="comparison-count">{{ $statistics['previous_month']['count'] }} ordre</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Footer -->
    <div class="stats-info-card">
        <div class="info-icon">
            <i class="bi bi-info-circle-fill"></i>
        </div>
        <div class="info-content">
            <h6 class="info-title">Om statistikken</h6>
            <div class="info-items">
                <div class="info-item">
                    <i class="bi bi-check2-circle"></i>
                    <span>Data hentet direkte fra WooCommerce via Analytics API</span>
                </div>
                <div class="info-item">
                    <i class="bi bi-check2-circle"></i>
                    <span>Kun fullførte ordre (status: completed) inkluderes i statistikken</span>
                </div>
                <div class="info-item">
                    <i class="bi bi-check2-circle"></i>
                    <span>Oppdateres automatisk ved hver lasting av siden</span>
                </div>
                <div class="info-item">
                    <i class="bi bi-link-45deg"></i>
                    <span>WooCommerce: <strong>{{ $site->url }}</strong></span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .statistics-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    /* Header Styles */
    .stats-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        color: white;
    }

    .header-icon-wrapper {
        width: 64px;
        height: 64px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        backdrop-filter: blur(10px);
    }

    .stats-title {
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0;
        color: white;
    }

    .stats-subtitle {
        font-size: 0.95rem;
        opacity: 0.9;
        color: rgba(255, 255, 255, 0.9);
    }

    .btn-refresh {
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        cursor: pointer;
    }

    .btn-refresh:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .btn-refresh i {
        transition: transform 0.5s ease;
    }

    .btn-refresh:hover i {
        transform: rotate(180deg);
    }

    .btn-back {
        width: 48px;
        height: 48px;
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.3);
        color: white;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        text-decoration: none;
        flex-shrink: 0;
    }

    .btn-back:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateX(-4px);
        color: white;
    }

    /* Pending Alert */
    .pending-alert {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        display: flex;
        gap: 1.5rem;
        box-shadow: 0 10px 30px rgba(255, 107, 107, 0.3);
        color: white;
        position: relative;
        overflow: hidden;
    }

    .pending-alert::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: pulse-bg 3s ease-in-out infinite;
    }

    @keyframes pulse-bg {
        0%, 100% { transform: scale(1) rotate(0deg); }
        50% { transform: scale(1.1) rotate(180deg); }
    }

    .pending-alert-icon {
        font-size: 3rem;
        flex-shrink: 0;
        animation: shake 0.5s ease-in-out infinite;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }

    .pending-alert-content {
        flex: 1;
        position: relative;
        z-index: 1;
    }

    .pending-alert-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .pending-alert-text {
        margin-bottom: 1rem;
        opacity: 0.95;
    }

    .pending-orders-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .pending-order-item {
        background: rgba(255, 255, 255, 0.2);
        padding: 0.75rem 1rem;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 1rem;
        backdrop-filter: blur(10px);
    }

    .pending-order-badge {
        background: rgba(255, 255, 255, 0.3);
        padding: 0.25rem 0.75rem;
        border-radius: 6px;
        font-weight: 700;
        font-size: 0.9rem;
    }

    .pending-order-amount {
        font-weight: 700;
        font-size: 1.1rem;
    }

    .pending-order-date {
        margin-left: auto;
        opacity: 0.9;
        font-size: 0.9rem;
    }

    .pending-alert-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }

    .pending-alert-close:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }

    /* Statistics Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 1100px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Stat Cards */
    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
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

    .stat-card:hover::before {
        transform: scaleX(1);
    }

    .stat-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
    }

    .stat-card-year::before {
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    }

    .stat-card-month::before {
        background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);
    }

    /* Card Header */
    .stat-card-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .stat-card-icon {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        color: white;
        flex-shrink: 0;
    }

    .year-icon {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .month-icon {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }

    .stat-card-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin: 0;
        color: #2d3748;
    }

    .stat-card-period {
        font-size: 0.9rem;
        color: #718096;
        margin: 0;
    }

    /* Main Stat Value */
    .stat-main {
        margin-bottom: 2rem;
    }

    .stat-main-label {
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #718096;
        margin-bottom: 0.5rem;
    }

    .stat-main-value {
        font-size: 3rem;
        font-weight: 800;
        line-height: 1;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .stat-card-month .stat-main-value {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .stat-currency {
        font-size: 1.5rem;
        margin-left: 0.25rem;
        opacity: 0.7;
    }

    /* Metrics */
    .stat-metrics {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .stat-metric {
        background: #f7fafc;
        border-radius: 12px;
        padding: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: all 0.3s ease;
    }

    .stat-metric:hover {
        background: #edf2f7;
        transform: scale(1.02);
    }

    .stat-metric-icon {
        width: 40px;
        height: 40px;
        background: white;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: #667eea;
        flex-shrink: 0;
    }

    .stat-metric-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2d3748;
        line-height: 1;
    }

    .stat-metric-label {
        font-size: 0.75rem;
        color: #718096;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Comparison */
    .stat-comparison {
        background: #f7fafc;
        border-radius: 12px;
        padding: 1.25rem;
    }

    .comparison-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #718096;
        margin-bottom: 1rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .comparison-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .comparison-badge {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 10px;
        font-weight: 700;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .badge-up {
        background: linear-gradient(135deg, #38ef7d 0%, #11998e 100%);
        color: white;
    }

    .badge-down {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
        color: white;
    }

    .comparison-details {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.25rem;
    }

    .comparison-value {
        font-weight: 700;
        font-size: 1.1rem;
        color: #2d3748;
    }

    .comparison-count {
        font-size: 0.85rem;
        color: #718096;
    }

    /* Info Card */
    .stats-info-card {
        background: linear-gradient(135deg, #e0e7ff 0%, #cfd9ff 100%);
        border-radius: 16px;
        padding: 1.5rem;
        display: flex;
        gap: 1.5rem;
    }

    .info-icon {
        font-size: 2rem;
        color: #667eea;
        flex-shrink: 0;
    }

    .info-content {
        flex: 1;
    }

    .info-title {
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 1rem;
    }

    .info-items {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 0.75rem;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #4a5568;
        font-size: 0.9rem;
    }

    .info-item i {
        color: #667eea;
        font-size: 1.1rem;
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

    .stat-card {
        animation: fadeIn 0.6s ease-out forwards;
    }

    .stat-card:nth-child(1) {
        animation-delay: 0.1s;
    }

    .stat-card:nth-child(2) {
        animation-delay: 0.2s;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .stats-header {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }

        .stats-title {
            font-size: 1.5rem;
        }

        .stat-main-value {
            font-size: 2.5rem;
        }

        .stat-metrics {
            grid-template-columns: 1fr;
        }

        .pending-alert {
            flex-direction: column;
        }

        .pending-order-item {
            flex-wrap: wrap;
        }

        .pending-order-date {
            margin-left: 0;
            width: 100%;
        }
    }
</style>
@endpush
