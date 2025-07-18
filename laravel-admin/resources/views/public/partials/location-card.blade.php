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
                        Åpner {{ $location['next_opening_day'] }} kl. {{ date('H:i', strtotime($location['next_opening_time'])) }}
                    @else
                        Stengt
                    @endif
                @endif
            </div>
        </div>

        <!-- Card Body -->
        <div class="card-body">
            <!-- Today's Hours -->
            <div class="today-hours">
                <strong>I dag:</strong>
                @if($location['special_notes'])
                    <span class="special-note">{{ $location['special_notes'] }}</span>
                @elseif($location['is_closed_today'])
                    <span class="closed-text">Stengt</span>
                @elseif($location['open_time'] && $location['close_time'])
                    <span>{{ date('H:i', strtotime($location['open_time'])) }} - {{ date('H:i', strtotime($location['close_time'])) }}</span>
                @else
                    <span class="closed-text">Stengt</span>
                @endif
            </div>

            <!-- Expandable Weekly Schedule -->
            <div class="weekly-schedule">
                <button class="toggle-schedule" data-location-id="{{ $location['id'] }}">
                    <span>Se hele uken</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="schedule-content" id="schedule-{{ $location['id'] }}" style="display: none;">
                    @foreach($location['weekly_hours'] as $day)
                    <div class="schedule-row {{ $day['is_today'] ? 'today' : '' }}">
                        <span class="day-name">{{ $day['day'] }}:</span>
                        <span class="day-hours">
                            @if($day['open_time'] && $day['close_time'])
                                {{ date('H:i', strtotime($day['open_time'])) }} - {{ date('H:i', strtotime($day['close_time'])) }}
                            @else
                                Stengt
                            @endif
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Contact Info -->
            <div class="contact-info">
                @if($location['phone'])
                <div class="info-item">
                    <i class="bi bi-telephone"></i>
                    <a href="tel:{{ $location['phone'] }}">{{ $location['phone'] }}</a>
                </div>
                @endif
                @if($location['address'])
                <div class="info-item">
                    <i class="bi bi-geo-alt"></i>
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