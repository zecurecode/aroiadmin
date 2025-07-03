<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aroi Asia - Th</title>
    <meta name="description" content="Velkommen til Aroi Asia! Vi har restauranter i Namsos, Lade, Moan, Gramyra, Frosta, Hell og Steinkjer. Se våre åpningstider og bestill mat online.">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-purple: #8a3794;
            --primary-purple-dark: #7a2d84;
            --success-green: #10B981;
            --warning-orange: #F59E0B;
            --danger-red: #EF4444;
            --text-dark: #1F2937;
            --text-light: #6B7280;
            --bg-light: #F9FAFB;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-purple-dark) 100%);
            min-height: 100vh;
            margin: 0;
        }

        .header {
            background: var(--primary-purple);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }

        .header-logo {
            max-height: 80px;
            max-width: 300px;
            height: auto;
            width: auto;
            display: block;
            margin: 0 auto;
            /* Removed white filter - use original logo colors */
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 10px;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .main-content {
            padding: 2rem 0;
            min-height: calc(100vh - 200px);
        }

        .date-info {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .date-info h2 {
            color: var(--text-dark);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .date-info p {
            color: var(--text-light);
            margin-bottom: 0;
        }

        .location-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .location-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 35px -5px rgba(0, 0, 0, 0.15), 0 10px 15px -5px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #E5E7EB;
        }

        .location-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.875rem;
            font-weight: 500;
            gap: 0.5rem;
        }

        .status-open {
            background: #DCFCE7;
            color: #166534;
        }

        .status-closed {
            background: #FEE2E2;
            color: #991B1B;
        }

        .status-warning {
            background: #FEF3C7;
            color: #92400E;
        }

        .card-body {
            padding: 1.5rem;
        }

                .todays-hours {
            margin-bottom: 1.5rem;
        }

        .todays-status {
            background: var(--bg-light);
            border-radius: 12px;
            padding: 1.25rem;
            border-left: 4px solid var(--primary-purple);
        }

        .todays-status.open {
            background: linear-gradient(135deg, #DCFCE7 0%, #F0FDF4 100%);
            border-left-color: var(--success-green);
        }

        .todays-status.closed {
            background: linear-gradient(135deg, #FEE2E2 0%, #FEF2F2 100%);
            border-left-color: var(--danger-red);
        }

        .status-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-dark);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .todays-time {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .contact-info {
            margin-bottom: 1.5rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            color: var(--text-light);
        }

        .contact-item:last-child {
            margin-bottom: 0;
        }

        .contact-item i {
            width: 20px;
            text-align: center;
            color: var(--primary-purple);
        }

        .btn-group-custom {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn-custom {
            flex: 1;
            min-width: 120px;
            padding: 0.875rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .btn-primary-custom {
            background: var(--primary-purple);
            color: white;
            border: 2px solid var(--primary-purple);
        }

        .btn-primary-custom:hover {
            background: var(--primary-purple-dark);
            border-color: var(--primary-purple-dark);
            color: white;
            transform: translateY(-1px);
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--primary-purple);
            border: 2px solid var(--primary-purple);
        }

        .btn-outline-custom:hover {
            background: var(--primary-purple);
            color: white;
            transform: translateY(-1px);
        }

        .footer {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 2rem 0;
            text-align: center;
            margin-top: 3rem;
        }

                .weekly-hours {
            background: white;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .day-hours {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 0.75rem !important;
            border-bottom: 1px solid #F3F4F6 !important;
            border-radius: 6px !important;
            margin-bottom: 0.25rem !important;
            background: #FAFAFA !important;
        }

        .day-hours:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .day-hours.today {
            background: linear-gradient(135deg, rgba(138, 55, 148, 0.15) 0%, rgba(138, 55, 148, 0.05) 100%);
            border: 2px solid var(--primary-purple);
            padding: 0.75rem;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(138, 55, 148, 0.1);
        }

                .day-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            color: #1F2937 !important;
            font-size: 1rem;
            min-width: 120px;
            flex-shrink: 0;
        }

        .day-hours.today .day-name {
            color: var(--primary-purple) !important;
        }

        .day-time {
            color: #374151 !important;
            font-weight: 600;
            font-size: 0.95rem;
            text-align: right;
        }

        .day-hours.today .day-time {
            color: var(--primary-purple) !important;
        }

        .special-notes {
            background: linear-gradient(135deg, #FEF3C7 0%, #FFFBEB 100%);
            border: 1px solid #F59E0B;
            border-radius: 8px;
            padding: 0.75rem;
            color: #92400E;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .weekly-hours-toggle {
            text-align: center;
        }

        .weekly-header {
            border-bottom: 2px solid var(--primary-purple);
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
        }

        .weekly-header h6 {
            color: var(--primary-purple);
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .header-logo {
                max-height: 60px;
                max-width: 250px;
            }

            .btn-group-custom {
                flex-direction: column;
            }

            .btn-custom {
                min-width: 100%;
            }

            .main-content {
                padding: 1rem 0;
            }

            .location-card {
                margin-bottom: 1rem;
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <img src="{{ asset('images/logo.png') }}" alt="Aroi Asia Thai Kitchen" class="header-logo" width="500" height="500">
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Date Info -->
            <div class="date-info">
                <h2>Velkommen til Aroi Asia!</h2>
                <p>Klikk på avdelingen for å se meny og bestille mat</p>
            </div>

            <!-- Locations Grid -->
            <div class="row">
                @foreach($locations as $location)
                <div class="col-lg-6 col-xl-4">
                    <div class="location-card">
                        <!-- Card Header -->
                        <div class="card-header">
                            <h3 class="location-name">{{ $location['name'] }}</h3>
                            <div class="status-badge
                                @if($location['is_closed_today'])
                                    status-closed
                                @elseif($location['is_open'])
                                    status-open
                                @else
                                    status-closed
                                @endif
                            ">
                                @if($location['is_closed_today'])
                                    <i class="bi bi-x-circle-fill"></i>
                                    Stengt
                                @elseif($location['is_open'])
                                    <i class="bi bi-check-circle-fill pulse"></i>
                                    Åpen
                                @else
                                    <i class="bi bi-clock-fill"></i>
                                    Stengt
                                @endif
                            </div>
                        </div>

                                                 <!-- Card Body -->
                        <div class="card-body">
                            <!-- Today's Opening Hours - More Prominent -->
                            <div class="todays-hours">
                                @if($location['open_time'] && $location['close_time'] && !$location['is_closed_today'])
                                    <div class="todays-status open">
                                        <div class="status-header">
                                            <i class="bi bi-clock-fill"></i>
                                            <strong>Åpningstider i dag</strong>
                                        </div>
                                        <div class="todays-time">
                                            {{ date('H:i', strtotime($location['open_time'])) }} - {{ date('H:i', strtotime($location['close_time'])) }}
                                        </div>
                                    </div>
                                @else
                                    <div class="todays-status closed">
                                        <div class="status-header">
                                            <i class="bi bi-x-circle-fill"></i>
                                            <strong>Stengt i dag</strong>
                                        </div>
                                    </div>
                                @endif

                                @if($location['special_notes'])
                                <div class="special-notes mt-2">
                                    <i class="bi bi-info-circle-fill"></i>
                                    {{ $location['special_notes'] }}
                                </div>
                                @endif
                            </div>

                            <!-- Weekly Hours Toggle -->
                            <div class="weekly-hours-toggle mb-3">
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#weeklyHours{{ $location['id'] }}" aria-expanded="false">
                                    <i class="bi bi-calendar-week"></i>
                                    Se hele ukens åpningstider
                                </button>
                            </div>

                            <!-- Weekly Hours Collapsible -->
                            <div class="collapse" id="weeklyHours{{ $location['id'] }}">
                                <div class="weekly-hours">
                                    <div class="weekly-header">
                                        <h6 class="mb-3">
                                            <i class="bi bi-calendar-week"></i>
                                            Ukens åpningstider
                                        </h6>
                                    </div>
                                    @foreach($location['weekly_hours'] as $dayHours)
                                    <div class="day-hours {{ $dayHours['is_today'] ? 'today' : '' }}">
                                        <div class="day-name">
                                            <strong>
                                                @switch($dayHours['day'])
                                                    @case('Monday') Mandag @break
                                                    @case('Tuesday') Tirsdag @break
                                                    @case('Wednesday') Onsdag @break
                                                    @case('Thursday') Torsdag @break
                                                    @case('Friday') Fredag @break
                                                    @case('Saturday') Lørdag @break
                                                    @case('Sunday') Søndag @break
                                                    @default {{ $dayHours['day'] }} @break
                                                @endswitch
                                            </strong>
                                            @if($dayHours['is_today'])
                                                <span class="badge bg-primary ms-1">I dag</span>
                                            @endif
                                        </div>
                                        <div class="day-time">
                                            @if($dayHours['is_open'])
                                                <strong>{{ date('H:i', strtotime($dayHours['open_time'])) }} - {{ date('H:i', strtotime($dayHours['close_time'])) }}</strong>
                                            @else
                                                <span class="text-muted"><strong>Stengt</strong></span>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Contact Info -->
                            <div class="contact-info">
                                @if($location['phone'])
                                <div class="contact-item">
                                    <i class="bi bi-telephone-fill"></i>
                                    <span>{{ $location['phone'] }}</span>
                                </div>
                                @endif

                                @if($location['address'])
                                <div class="contact-item">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span>{{ $location['address'] }}</span>
                                </div>
                                @endif
                            </div>

                            <!-- Action Buttons -->
                            <div class="btn-group-custom">
                                <a href="{{ $location['url'] }}" class="btn-custom btn-primary-custom" target="_blank">
                                    <i class="bi bi-menu-button-wide"></i>
                                    Se meny og bestill
                                </a>

                                <a href="{{ $location['maps_url'] }}" class="btn-custom btn-outline-custom" target="_blank">
                                    <i class="bi bi-geo-alt"></i>
                                    Veibeskrivelse
                                </a>

                                @if($location['phone'])
                                <a href="tel:{{ $location['phone'] }}" class="btn-custom btn-outline-custom">
                                    <i class="bi bi-telephone"></i>
                                    Ring
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="container">
            <p>&copy; {{ date('Y') }} Aroi Asia AS</p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
