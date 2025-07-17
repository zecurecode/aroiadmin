<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aroi Asia - Th</title>
    <meta name="description" content="Velkommen til Aroi Asia! Vi har restauranter i Namsos, Lade, Moan, Gramyra, Frosta, Hell og Steinkjer. Se våre åpningstider og bestill mat online.">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">

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
            padding: 4rem 0 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-purple-dark) 50%, #6B2C70 100%);
            opacity: 0.9;
        }

        .header .container {
            position: relative;
            z-index: 2;
        }

        .header-logo {
            max-height: 180px;
            max-width: 600px;
            height: auto;
            width: auto;
            display: block;
            margin: 0 auto 2rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 30px;
            border-radius: 25px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3), 0 4px 15px rgba(255, 255, 255, 0.1) inset;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .header-logo:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4), 0 6px 20px rgba(255, 255, 255, 0.15) inset;
        }

        .header h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .header p {
            font-size: 1.3rem;
            opacity: 0.95;
            margin-bottom: 0;
            font-weight: 300;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .main-content {
            padding: 3rem 0;
            min-height: calc(100vh - 200px);
            position: relative;
        }

        .date-info {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 3rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .date-info h2 {
            color: var(--text-dark);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-purple-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .date-info p {
            color: var(--text-light);
            font-size: 1.1rem;
            margin-bottom: 0;
            font-weight: 400;
        }

        .location-card {
            background: linear-gradient(135deg, #ffffff 0%, #fefefe 100%);
            border-radius: 25px;
            box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.1), 0 5px 15px -5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            overflow: hidden;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }

        .location-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -5px rgba(0, 0, 0, 0.15), 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            padding: 2rem;
            border-bottom: 1px solid #E5E7EB;
            background: linear-gradient(135deg, #fafafa 0%, #ffffff 100%);
        }

        .location-name {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
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

        .next-opening-time {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-light);
            margin-top: 0.5rem;
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
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0.05) 100%);
            color: white;
            padding: 3rem 0;
            text-align: center;
            margin-top: 4rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .footer p {
            font-size: 1.1rem;
            font-weight: 300;
            margin: 0;
            opacity: 0.9;
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
            .header {
                padding: 3rem 0 2.5rem;
            }

            .header-logo {
                max-height: 140px;
                max-width: 450px;
                padding: 25px;
            }

            .header h1 {
                font-size: 2.5rem;
            }

            .header p {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 2.5rem 0 2rem;
            }

            .header-logo {
                max-height: 110px;
                max-width: 350px;
                padding: 20px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .header p {
                font-size: 1.1rem;
            }

            .btn-group-custom {
                flex-direction: column;
            }

            .btn-custom {
                min-width: 100%;
            }

            .main-content {
                padding: 2rem 0;
            }

            .date-info {
                margin-bottom: 2rem;
                padding: 2rem;
            }

            .date-info h2 {
                font-size: 1.6rem;
            }

            .location-card {
                margin-bottom: 1.5rem;
            }

            .card-header {
                padding: 1.5rem;
            }

            .location-name {
                font-size: 1.5rem;
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
                                @elseif($location['past_closing_time'])
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
                                @elseif($location['past_closing_time'])
                                    <i class="bi bi-clock-fill"></i>
                                    @if($location['next_opening_time'] && $location['next_opening_day'])
                                        Åpner {{ $location['next_opening_day'] }} {{ date('H:i', strtotime($location['next_opening_time'])) }}
                                    @else
                                        Stengt
                                    @endif
                                @elseif($location['is_open'])
                                    <i class="bi bi-check-circle-fill pulse"></i>
                                    Åpen
                                @else
                                    <i class="bi bi-clock-fill"></i>
                                    @if($location['next_opening_time'] && $location['next_opening_day'])
                                        Åpner {{ $location['next_opening_day'] }} {{ date('H:i', strtotime($location['next_opening_time'])) }}
                                    @else
                                        Stengt
                                    @endif
                                @endif
                            </div>
                        </div>

                                                 <!-- Card Body -->
                        <div class="card-body">
                            <!-- Today's Opening Hours - More Prominent -->
                            <div class="todays-hours">
                                @if($location['open_time'] && $location['close_time'] && !$location['is_closed_today'] && !$location['past_closing_time'])
                                    <div class="todays-status open">
                                        <div class="status-header">
                                            <i class="bi bi-clock-fill"></i>
                                            <strong>Åpningstider i dag</strong>
                                        </div>
                                        <div class="todays-time">
                                            {{ date('H:i', strtotime($location['open_time'])) }} - {{ date('H:i', strtotime($location['close_time'])) }}
                                        </div>
                                    </div>
                                @elseif($location['past_closing_time'])
                                    <div class="todays-status closed">
                                        <div class="status-header">
                                            <i class="bi bi-x-circle-fill"></i>
                                            <strong>Stengt nå</strong>
                                        </div>
                                        @if($location['next_opening_time'] && $location['next_opening_day'])
                                            <div class="todays-time text-muted">
                                                Åpner {{ $location['next_opening_day'] }} klokken {{ date('H:i', strtotime($location['next_opening_time'])) }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="todays-status closed">
                                        <div class="status-header">
                                            <i class="bi bi-x-circle-fill"></i>
                                            <strong>Stengt</strong>
                                        </div>
                                        @if($location['next_opening_time'] && $location['next_opening_day'])
                                            <div class="todays-time text-muted">
                                                Åpner {{ $location['next_opening_day'] }} klokken {{ date('H:i', strtotime($location['next_opening_time'])) }}
                                            </div>
                                        @endif
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
