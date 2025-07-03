@extends('layouts.admin')

@section('title', 'Administrer Åpningstider')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-calendar-alt me-2"></i>
                Administrer Åpningstider
            </h1>
            <p class="text-muted mb-0">Håndter vanlige åpningstider og spesielle datoer</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSpecialHoursModal">
            <i class="fas fa-plus me-2"></i>
            Legg til spesielle åpningstider
        </button>
    </div>

    <!-- Location and Month Selection -->
    <div class="row mb-4">
        @if(auth()->user()->is_admin && $locations->count() > 1)
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Velg avdeling
                    </h5>
                    <select id="locationSelect" class="form-select">
                        @foreach($locations as $location)
                        <option value="{{ $location->Id }}" {{ $location->Id == $selectedLocationId ? 'selected' : '' }}>
                            {{ $location->Navn }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        @else
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Avdeling
                    </h5>
                    <div class="form-control-plaintext fw-bold">
                        {{ $locations->first()?->Navn ?? 'Ukjent avdeling' }}
                    </div>
                    <input type="hidden" id="locationSelect" value="{{ $locations->first()?->Id }}">
                </div>
            </div>
        </div>
        @endif
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-calendar me-2"></i>
                        Måned og år
                    </h5>
                    <div class="d-flex align-items-center">
                        <button id="prevMonth" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <input type="month" id="monthSelect" class="form-control" value="{{ $currentDate }}">
                        <button id="nextMonth" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Calendar View -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-days me-2"></i>
                        Kalender oversikt
                    </h5>
                    <span id="currentLocationName" class="badge bg-primary fs-6"></span>
                </div>
                <div class="card-body">
                    <div id="calendarLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Laster...</span>
                        </div>
                        <p class="mt-2">Laster kalender...</p>
                    </div>
                    <div id="calendarContainer" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-bordered calendar-table">
                                <thead class="table-light">
                                    <tr>
                                        <th width="14.28%">Mandag</th>
                                        <th width="14.28%">Tirsdag</th>
                                        <th width="14.28%">Onsdag</th>
                                        <th width="14.28%">Torsdag</th>
                                        <th width="14.28%">Fredag</th>
                                        <th width="14.28%">Lørdag</th>
                                        <th width="14.28%">Søndag</th>
                                    </tr>
                                </thead>
                                <tbody id="calendarBody">
                                    <!-- Calendar days will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Regular Hours and Info Panel -->
        <div class="col-lg-4">
            <!-- Regular Hours Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Vanlige åpningstider
                    </h5>
                    <button id="editRegularHours" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i>
                        Rediger
                    </button>
                </div>
                <div class="card-body">
                    <div id="regularHoursDisplay">
                        <!-- Regular hours will be populated by JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Legend Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Forklaring
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="calendar-day-sample regular-day me-2"></div>
                        <small>Vanlige åpningstider</small>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <div class="calendar-day-sample special-day me-2"></div>
                        <small>Spesielle åpningstider</small>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <div class="calendar-day-sample closed-day me-2"></div>
                        <small>Stengt</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="calendar-day-sample holiday-day me-2"></div>
                        <small>Helligdag</small>
                    </div>
                </div>
            </div>

            <!-- Upcoming Special Hours -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-star me-2"></i>
                        Kommende spesielle datoer
                    </h5>
                </div>
                <div class="card-body">
                    <div id="upcomingSpecialHours">
                        <!-- Upcoming special hours will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Special Hours Modal -->
<div class="modal fade" id="addSpecialHoursModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>
                    Legg til spesielle åpningstider
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addSpecialHoursForm">
                <div class="modal-body">
                    <div class="row">
                        @if(auth()->user()->is_admin && $locations->count() > 1)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="specialLocationId" class="form-label">Avdeling</label>
                                <select id="specialLocationId" name="location_id" class="form-select" required>
                                    @foreach($locations as $location)
                                    <option value="{{ $location->Id }}">{{ $location->Navn }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @else
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Avdeling</label>
                                <div class="form-control-plaintext">{{ $locations->first()?->Navn ?? 'Ukjent avdeling' }}</div>
                                <input type="hidden" id="specialLocationId" name="location_id" value="{{ $locations->first()?->Id }}" required>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="specialType" class="form-label">Type</label>
                                <select id="specialType" name="type" class="form-select" required>
                                    <option value="special">Spesielle åpningstider</option>
                                    <option value="holiday">Helligdag</option>
                                    <option value="maintenance">Vedlikehold</option>
                                    <option value="event">Arrangement</option>
                                    <option value="closure">Stengt</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="specialDate" class="form-label">Fra dato</label>
                                <input type="date" id="specialDate" name="date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="specialEndDate" class="form-label">Til dato (valgfritt)</label>
                                <input type="date" id="specialEndDate" name="end_date" class="form-control">
                                <div class="form-text">Lar tom for enkeltdag, fyll ut for periode</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="hidden" name="is_closed" value="0">
                            <input class="form-check-input" type="checkbox" id="specialIsClosed" name="is_closed" value="1">
                            <label class="form-check-label" for="specialIsClosed">
                                Stengt hele dagen
                            </label>
                        </div>
                    </div>

                    <div id="specialTimeFields" class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Åpningstid</label>
                                <div class="d-flex gap-2">
                                    <select class="form-select" id="specialOpenHour" style="width: auto;">
                                        <option value="">Timer</option>
                                    </select>
                                    <span class="align-self-center">:</span>
                                    <select class="form-select" id="specialOpenMin" style="width: auto;">
                                        <option value="00">00</option>
                                        <option value="15">15</option>
                                        <option value="30">30</option>
                                        <option value="45">45</option>
                                    </select>
                                    <input type="hidden" id="specialOpenTime" name="open_time">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Stengetid</label>
                                <div class="d-flex gap-2">
                                    <select class="form-select" id="specialCloseHour" style="width: auto;">
                                        <option value="">Timer</option>
                                    </select>
                                    <span class="align-self-center">:</span>
                                    <select class="form-select" id="specialCloseMin" style="width: auto;">
                                        <option value="00">00</option>
                                        <option value="15">15</option>
                                        <option value="30">30</option>
                                        <option value="45">45</option>
                                    </select>
                                    <input type="hidden" id="specialCloseTime" name="close_time">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="specialReason" class="form-label">Grunn/beskrivelse</label>
                        <input type="text" id="specialReason" name="reason" class="form-control"
                               placeholder="F.eks. 'Julaften', 'Renovering', etc.">
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="specialRecurring" name="recurring_yearly">
                            <label class="form-check-label" for="specialRecurring">
                                Gjenta hvert år (for helligdager)
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="specialNotes" class="form-label">Notater (valgfritt)</label>
                        <textarea id="specialNotes" name="notes" class="form-control" rows="3"
                                  placeholder="Eventuelle tilleggsnotater..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Lagre
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Special Hours Modal -->
<div class="modal fade" id="editSpecialHoursModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Rediger spesielle åpningstider
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editSpecialHoursForm">
                <input type="hidden" id="editSpecialId" name="id">
                <div class="modal-body">
                    <!-- Same fields as add modal, will be populated by JavaScript -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editSpecialType" class="form-label">Type</label>
                                <select id="editSpecialType" name="type" class="form-select" required>
                                    <option value="special">Spesielle åpningstider</option>
                                    <option value="holiday">Helligdag</option>
                                    <option value="maintenance">Vedlikehold</option>
                                    <option value="event">Arrangement</option>
                                    <option value="closure">Stengt</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editSpecialDate" class="form-label">Fra dato</label>
                                <input type="date" id="editSpecialDate" name="date" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editSpecialEndDate" class="form-label">Til dato (valgfritt)</label>
                                <input type="date" id="editSpecialEndDate" name="end_date" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check mt-4">
                                    <input type="hidden" name="is_closed" value="0">
                                    <input class="form-check-input" type="checkbox" id="editSpecialIsClosed" name="is_closed" value="1">
                                    <label class="form-check-label" for="editSpecialIsClosed">
                                        Stengt hele dagen
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="editSpecialTimeFields" class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Åpningstid</label>
                                <div class="d-flex gap-2">
                                    <select class="form-select" id="editSpecialOpenHour" style="width: auto;">
                                        <option value="">Timer</option>
                                    </select>
                                    <span class="align-self-center">:</span>
                                    <select class="form-select" id="editSpecialOpenMin" style="width: auto;">
                                        <option value="00">00</option>
                                        <option value="15">15</option>
                                        <option value="30">30</option>
                                        <option value="45">45</option>
                                    </select>
                                    <input type="hidden" id="editSpecialOpenTime" name="open_time">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Stengetid</label>
                                <div class="d-flex gap-2">
                                    <select class="form-select" id="editSpecialCloseHour" style="width: auto;">
                                        <option value="">Timer</option>
                                    </select>
                                    <span class="align-self-center">:</span>
                                    <select class="form-select" id="editSpecialCloseMin" style="width: auto;">
                                        <option value="00">00</option>
                                        <option value="15">15</option>
                                        <option value="30">30</option>
                                        <option value="45">45</option>
                                    </select>
                                    <input type="hidden" id="editSpecialCloseTime" name="close_time">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="editSpecialReason" class="form-label">Grunn/beskrivelse</label>
                        <input type="text" id="editSpecialReason" name="reason" class="form-control">
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="editSpecialRecurring" name="recurring_yearly">
                            <label class="form-check-label" for="editSpecialRecurring">
                                Gjenta hvert år (for helligdager)
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="editSpecialNotes" class="form-label">Notater (valgfritt)</label>
                        <textarea id="editSpecialNotes" name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger me-auto" id="deleteSpecialHours">
                        <i class="fas fa-trash me-2"></i>
                        Slett
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Oppdater
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Regular Hours Edit Modal -->
<div class="modal fade" id="editRegularHoursModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clock me-2"></i>
                    Rediger vanlige åpningstider
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editRegularHoursForm">
                <div class="modal-body">
                    <div id="regularHoursFields">
                        <!-- Regular hours fields will be populated by JavaScript -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Lagre endringer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.calendar-table {
    table-layout: fixed;
}

.calendar-day {
    height: 120px;
    vertical-align: top;
    border: 1px solid #dee2e6;
    position: relative;
    cursor: pointer;
    transition: all 0.2s ease;
}

.calendar-day:hover {
    background-color: #f8f9fa;
}

.calendar-day.regular-day {
    background-color: #ffffff;
}

.calendar-day.special-day {
    background-color: #e3f2fd;
    border-color: #2196f3;
}

.calendar-day.closed-day {
    background-color: #ffebee;
    border-color: #f44336;
}

.calendar-day.holiday-day {
    background-color: #fff3e0;
    border-color: #ff9800;
}

.calendar-day-number {
    font-weight: bold;
    font-size: 1.1em;
    margin-bottom: 5px;
}

.calendar-day-hours {
    font-size: 0.85em;
    color: #495057;
}

.calendar-day-reason {
    font-size: 0.75em;
    color: #6c757d;
    font-style: italic;
    margin-top: 5px;
}

.calendar-day-sample {
    width: 20px;
    height: 20px;
    border-radius: 3px;
    display: inline-block;
}

.calendar-day-sample.regular-day {
    background-color: #ffffff;
    border: 1px solid #dee2e6;
}

.calendar-day-sample.special-day {
    background-color: #e3f2fd;
    border: 1px solid #2196f3;
}

.calendar-day-sample.closed-day {
    background-color: #ffebee;
    border: 1px solid #f44336;
}

.calendar-day-sample.holiday-day {
    background-color: #fff3e0;
    border: 1px solid #ff9800;
}

.day-hours-row {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    padding: 8px;
    border-radius: 5px;
    background-color: #f8f9fa;
}

.day-hours-row:last-child {
    margin-bottom: 0;
}

.day-label {
    width: 80px;
    font-weight: 500;
}

.hours-display {
    flex: 1;
    text-align: center;
    color: #495057;
}

.hours-display.closed {
    color: #dc3545;
    font-style: italic;
}

/* Force 24-hour time format */
.time-input-24h::-webkit-datetime-edit-hour-field:not([data-placeholder]) {
    color: inherit;
}

.time-input-24h::-webkit-datetime-edit-minute-field:not([data-placeholder]) {
    color: inherit;
}

.time-input-24h::-webkit-datetime-edit-ampm-field {
    display: none !important;
}

/* Additional rules to force 24-hour format */
input[type="time"]::-webkit-datetime-edit-ampm-field {
    display: none !important;
}

/* Firefox */
input[type="time"] {
    -moz-appearance: textfield;
}

/* Force 24-hour format for all browsers */
input[type="time"].time-input-24h {
    -webkit-appearance: none;
    -moz-appearance: textfield;
}

/* Style for text-based time inputs */
.time-input-24h {
    font-family: monospace;
}

.time-input-24h.is-invalid {
    border-color: #dc3545;
}

.time-input-24h::placeholder {
    color: #6c757d;
    opacity: 0.7;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    let currentLocationId = $('#locationSelect').val() || {{ $selectedLocationId ?? 'null' }};
    let currentMonth = $('#monthSelect').val();
    let calendarData = {};
    let locationData = {};

    // Ensure we have a valid location ID
    if (!currentLocationId) {
        console.error('No location ID available');
        showAlert('Ingen lokasjon tilgjengelig', 'danger');
        return;
    }

    // Setup CSRF token for AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // No longer needed - using select boxes instead

    // Initialize hour options for special hours
    function initializeSpecialHoursSelects() {
        // Add hours
        $('#specialOpenHour, #specialCloseHour, #editSpecialOpenHour, #editSpecialCloseHour').each(function() {
            $(this).html('<option value="">Timer</option>' + generateHourOptions(''));
        });
        
        // Add change handlers
        $('#specialOpenHour, #specialOpenMin').on('change', function() {
            updateTimeInput('specialOpenTime', $('#specialOpenHour').val(), $('#specialOpenMin').val());
        });
        
        $('#specialCloseHour, #specialCloseMin').on('change', function() {
            updateTimeInput('specialCloseTime', $('#specialCloseHour').val(), $('#specialCloseMin').val());
        });
        
        $('#editSpecialOpenHour, #editSpecialOpenMin').on('change', function() {
            updateTimeInput('editSpecialOpenTime', $('#editSpecialOpenHour').val(), $('#editSpecialOpenMin').val());
        });
        
        $('#editSpecialCloseHour, #editSpecialCloseMin').on('change', function() {
            updateTimeInput('editSpecialCloseTime', $('#editSpecialCloseHour').val(), $('#editSpecialCloseMin').val());
        });
    }
    
    function updateTimeInput(inputId, hour, min) {
        const input = $('#' + inputId);
        if (hour && min) {
            input.val(`${hour}:${min}`);
        } else {
            input.val('');
        }
    }

    // Initialize
    initializeSpecialHoursSelects();
    loadCalendarData();

    // Event handlers
    $('#locationSelect').change(function() {
        currentLocationId = $(this).val();
        loadCalendarData();
    });

    $('#monthSelect').change(function() {
        currentMonth = $(this).val();
        loadCalendarData();
    });

    $('#prevMonth, #nextMonth').click(function() {
        const isNext = $(this).attr('id') === 'nextMonth';
        const currentDate = new Date(currentMonth + '-01');
        currentDate.setMonth(currentDate.getMonth() + (isNext ? 1 : -1));

        const year = currentDate.getFullYear();
        const month = String(currentDate.getMonth() + 1).padStart(2, '0');
        currentMonth = `${year}-${month}`;

        $('#monthSelect').val(currentMonth);
        loadCalendarData();
    });

    // Load calendar data
    function loadCalendarData() {
        $('#calendarLoading').show();
        $('#calendarContainer').hide();

        $.get('/admin/opening-hours/calendar-data', {
            location_id: currentLocationId,
            month: currentMonth
        })
        .done(function(response) {
            calendarData = response.calendarData;
            locationData = response.location;

            renderCalendar();
            renderRegularHours();
            renderUpcomingSpecialHours();

            $('#currentLocationName').text(locationData.name);
            $('#calendarLoading').hide();
            $('#calendarContainer').show();
        })
        .fail(function(xhr) {
            console.error('Failed to load calendar data:', xhr);
            const errorMsg = xhr.responseJSON?.error || xhr.responseJSON?.message || 'Feil ved lasting av kalenderdata';
            showAlert(`Feil: ${errorMsg}`, 'danger');
            $('#calendarLoading').hide();
            
            // Show more detailed error info in console
            if (xhr.responseJSON) {
                console.error('Error details:', xhr.responseJSON);
            }
        });
    }

    // Render calendar
    function renderCalendar() {
        const tbody = $('#calendarBody');
        tbody.empty();

        // Group days by weeks
        const weeks = [];
        let currentWeek = [];

        // Start from Monday (weekday 1)
        const firstDay = new Date(currentMonth + '-01');
        const startOfMonth = new Date(firstDay);
        startOfMonth.setDate(1);

        // Find the Monday of the week containing the first day
        const startDate = new Date(startOfMonth);
        const dayOfWeek = startDate.getDay();
        const mondayOffset = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
        startDate.setDate(startDate.getDate() + mondayOffset);

        // Generate 6 weeks of data
        for (let week = 0; week < 6; week++) {
            const weekData = [];
            for (let day = 0; day < 7; day++) {
                const currentDate = new Date(startDate);
                currentDate.setDate(startDate.getDate() + (week * 7) + day);

                const dateStr = currentDate.toISOString().split('T')[0];
                const dayData = calendarData.find(d => d.date === dateStr);

                if (dayData) {
                    weekData.push(dayData);
                } else {
                    weekData.push({
                        date: dateStr,
                        day: currentDate.getDate(),
                        dayOfWeek: currentDate.toLocaleDateString('en', {weekday: 'long'}).toLowerCase(),
                        isSpecial: false,
                        isClosed: true,
                        hours: 'Stengt',
                        type: 'regular'
                    });
                }
            }
            weeks.push(weekData);
        }

        // Render weeks
        weeks.forEach(week => {
            const row = $('<tr></tr>');

            week.forEach(day => {
                const cell = $('<td></td>')
                    .addClass('calendar-day')
                    .attr('data-date', day.date)
                    .attr('data-special-id', day.specialId || '');

                // Add appropriate classes
                if (day.isClosed) {
                    cell.addClass(day.type === 'holiday' ? 'holiday-day' : 'closed-day');
                } else if (day.isSpecial) {
                    cell.addClass('special-day');
                } else {
                    cell.addClass('regular-day');
                }

                // Check if date is in current month
                const cellDate = new Date(day.date);
                const currentMonthDate = new Date(currentMonth + '-01');
                const isCurrentMonth = cellDate.getMonth() === currentMonthDate.getMonth();

                if (!isCurrentMonth) {
                    cell.css('opacity', '0.3');
                }

                // Cell content
                let content = `<div class="calendar-day-number">${day.day}</div>`;
                content += `<div class="calendar-day-hours">${day.hours}</div>`;
                if (day.reason) {
                    content += `<div class="calendar-day-reason">${day.reason}</div>`;
                }

                cell.html(content);
                row.append(cell);
            });

            tbody.append(row);
        });

        // Add click handlers for calendar days
        $('.calendar-day').click(function() {
            const date = $(this).data('date');
            const specialId = $(this).data('special-id');

            if (specialId) {
                // Edit existing special hours
                editSpecialHours(specialId);
            } else {
                // Add new special hours for this date
                addSpecialHoursForDate(date);
            }
        });
    }

    // Render regular hours
    function renderRegularHours() {
        const container = $('#regularHoursDisplay');
        container.empty();

        const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        const dayNames = ['Mandag', 'Tirsdag', 'Onsdag', 'Torsdag', 'Fredag', 'Lørdag', 'Søndag'];

        days.forEach((day, index) => {
            const hours = locationData.regularHours[day];
            const row = $('<div class="day-hours-row"></div>');

            row.append(`<div class="day-label">${dayNames[index]}:</div>`);

            const hoursDisplay = $('<div class="hours-display"></div>');
            if (hours.closed == 1) {
                hoursDisplay.addClass('closed').text('Stengt');
            } else if (hours.start && hours.stop) {
                hoursDisplay.text(`${hours.start} - ${hours.stop}`);
            } else {
                hoursDisplay.addClass('closed').text('Stengt');
            }

            row.append(hoursDisplay);
            container.append(row);
        });
    }

    // Render upcoming special hours
    function renderUpcomingSpecialHours() {
        const container = $('#upcomingSpecialHours');
        container.empty();

        const upcoming = calendarData.filter(day => {
            return day.isSpecial && new Date(day.date) >= new Date();
        }).slice(0, 5);

        if (upcoming.length === 0) {
            container.html('<p class="text-muted mb-0">Ingen kommende spesielle datoer</p>');
            return;
        }

        upcoming.forEach(day => {
            const item = $(`
                <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded border">
                    <div>
                        <strong>${new Date(day.date).toLocaleDateString('no-NO')}</strong><br>
                        <small class="text-muted">${day.hours}</small>
                        ${day.reason ? `<br><small class="text-info">${day.reason}</small>` : ''}
                    </div>
                    <button class="btn btn-sm btn-outline-primary edit-special-btn" data-special-id="${day.specialId}">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            `);
            container.append(item);
        });

        // Add click handlers for edit buttons
        $('.edit-special-btn').click(function() {
            const specialId = $(this).data('special-id');
            editSpecialHours(specialId);
        });
    }

    // Modal handlers
    $('#editRegularHours').click(function() {
        renderRegularHoursForm();
        $('#editRegularHoursModal').modal('show');
    });

    // Handle closed checkbox for add modal
    $('#specialIsClosed').change(function() {
        if ($(this).is(':checked')) {
            $('#specialTimeFields').hide();
            $('#specialOpenTime, #specialCloseTime').prop('required', false);
        } else {
            $('#specialTimeFields').show();
            $('#specialOpenTime, #specialCloseTime').prop('required', true);
        }
    });

    // Handle closed checkbox for edit modal
    $('#editSpecialIsClosed').change(function() {
        if ($(this).is(':checked')) {
            $('#editSpecialTimeFields').hide();
            $('#editSpecialOpenTime, #editSpecialCloseTime').prop('required', false);
        } else {
            $('#editSpecialTimeFields').show();
            $('#editSpecialOpenTime, #editSpecialCloseTime').prop('required', true);
        }
    });

    // Form submissions
    $('#addSpecialHoursForm').submit(function(e) {
        e.preventDefault();
        saveSpecialHours();
    });

    $('#editSpecialHoursForm').submit(function(e) {
        e.preventDefault();
        updateSpecialHours();
    });

    $('#editRegularHoursForm').submit(function(e) {
        e.preventDefault();
        saveRegularHours();
    });

    $('#deleteSpecialHours').click(function() {
        if (confirm('Er du sikker på at du vil slette denne spesielle åpningstiden?')) {
            deleteSpecialHours();
        }
    });

    // Helper functions
    function addSpecialHoursForDate(date) {
        $('#specialLocationId').val(currentLocationId);
        $('#specialDate').val(date);
        $('#addSpecialHoursModal').modal('show');
    }

    function editSpecialHours(specialId) {
        // Find the special hours data
        const specialData = calendarData.find(day => day.specialId == specialId);
        if (!specialData) return;

        // We need to fetch the full data from server since calendar data is limited
        $.get(`/admin/opening-hours/special/${specialId}`)
        .done(function(data) {
            populateEditForm(data);
            $('#editSpecialHoursModal').modal('show');
        })
        .fail(function() {
            // Fallback: use available data
            populateEditFormFromCalendar(specialData, specialId);
            $('#editSpecialHoursModal').modal('show');
        });
    }

    function populateEditForm(data) {
        $('#editSpecialId').val(data.id);
        $('#editSpecialType').val(data.type);
        $('#editSpecialDate').val(data.date);
        $('#editSpecialEndDate').val(data.end_date || '');
        $('#editSpecialIsClosed').prop('checked', data.is_closed);
        
        // Set time selects
        if (data.open_time) {
            const openTime = data.open_time.substring(0, 5);
            const [openHour, openMin] = openTime.split(':');
            $('#editSpecialOpenHour').val(openHour);
            $('#editSpecialOpenMin').val(openMin);
            $('#editSpecialOpenTime').val(openTime);
        } else {
            $('#editSpecialOpenHour').val('');
            $('#editSpecialOpenMin').val('00');
            $('#editSpecialOpenTime').val('');
        }
        
        if (data.close_time) {
            const closeTime = data.close_time.substring(0, 5);
            const [closeHour, closeMin] = closeTime.split(':');
            $('#editSpecialCloseHour').val(closeHour);
            $('#editSpecialCloseMin').val(closeMin);
            $('#editSpecialCloseTime').val(closeTime);
        } else {
            $('#editSpecialCloseHour').val('');
            $('#editSpecialCloseMin').val('00');
            $('#editSpecialCloseTime').val('');
        }
        
        $('#editSpecialReason').val(data.reason || '');
        $('#editSpecialRecurring').prop('checked', data.recurring_yearly);
        $('#editSpecialNotes').val(data.notes || '');

        // Toggle time fields
        $('#editSpecialIsClosed').trigger('change');
    }

    function populateEditFormFromCalendar(data, specialId) {
        $('#editSpecialId').val(specialId);
        $('#editSpecialType').val(data.type);
        $('#editSpecialDate').val(data.date);
        $('#editSpecialIsClosed').prop('checked', data.isClosed);
        $('#editSpecialReason').val(data.reason || '');

        // Parse hours if available
        if (data.hours && data.hours !== 'Stengt' && data.hours.includes(' - ')) {
            const [open, close] = data.hours.split(' - ');
            $('#editSpecialOpenTime').val(formatTimeTo24Hour(open));
            $('#editSpecialCloseTime').val(formatTimeTo24Hour(close));
        }

        // Toggle time fields
        $('#editSpecialIsClosed').trigger('change');
    }

    // Generate hour options for select
    function generateHourOptions(selectedHour) {
        let options = '';
        for (let i = 0; i < 24; i++) {
            const hour = i.toString().padStart(2, '0');
            const selected = hour === selectedHour ? 'selected' : '';
            options += `<option value="${hour}" ${selected}>${hour}</option>`;
        }
        return options;
    }
    
    // Generate minute options for select
    function generateMinuteOptions(selectedMin) {
        let options = '';
        const minutes = ['00', '15', '30', '45'];
        minutes.forEach(min => {
            const selected = min === selectedMin ? 'selected' : '';
            options += `<option value="${min}" ${selected}>${min}</option>`;
        });
        return options;
    }

    // Helper function to convert time to 24-hour format
    function formatTimeTo24Hour(timeStr) {
        if (!timeStr) return '';
        
        // Remove extra spaces and convert to lowercase
        timeStr = timeStr.trim().toLowerCase();
        
        // If already in HH:MM format (24-hour), return as is
        if (/^\d{2}:\d{2}$/.test(timeStr)) {
            return timeStr;
        }
        
        // If in H:MM format, add leading zero
        if (/^\d{1}:\d{2}$/.test(timeStr)) {
            return '0' + timeStr;
        }
        
        // Handle H:MM am/pm or HH:MM am/pm format
        const amPmMatch = timeStr.match(/^(\d{1,2}):(\d{2})\s*(am|pm)$/i);
        if (amPmMatch) {
            let hours = parseInt(amPmMatch[1], 10);
            const minutes = amPmMatch[2];
            const isPM = amPmMatch[3].toLowerCase() === 'pm';
            
            if (isPM && hours !== 12) {
                hours = hours + 12;
            } else if (!isPM && hours === 12) {
                hours = 0;
            }
            
            return `${hours.toString().padStart(2, '0')}:${minutes}`;
        }
        
        // Handle AM/PM format with spaces
        if (timeStr.includes('am') || timeStr.includes('pm')) {
            const isPM = timeStr.includes('pm');
            const timeOnly = timeStr.replace(/\s*(am|pm)\s*/i, '');
            const timeParts = timeOnly.split(':');
            
            if (timeParts.length === 2) {
                const hours = parseInt(timeParts[0], 10);
                const minutes = parseInt(timeParts[1], 10);
                
                let hour24 = hours;
                
                if (isPM && hours !== 12) {
                    hour24 = hours + 12;
                } else if (!isPM && hours === 12) {
                    hour24 = 0;
                }
                
                return `${hour24.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
            }
        }
        
        return timeStr;
    }

    function renderRegularHoursForm() {
        const container = $('#regularHoursFields');
        container.empty();

        const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        const dayNames = ['Mandag', 'Tirsdag', 'Onsdag', 'Torsdag', 'Fredag', 'Lørdag', 'Søndag'];

        days.forEach((day, index) => {
            const hours = locationData.regularHours[day];
            const isClosed = hours.closed == 1;
            
            // Parse times
            let startHour = '', startMin = '', endHour = '', endMin = '';
            if (hours.start) {
                const startParts = hours.start.split(':');
                startHour = startParts[0] || '';
                startMin = startParts[1] || '00';
            }
            if (hours.stop) {
                const endParts = hours.stop.split(':');
                endHour = endParts[0] || '';
                endMin = endParts[1] || '00';
            }

            // Set proper time values
            const startTimeValue = startHour && startMin ? `${startHour}:${startMin}` : '';
            const endTimeValue = endHour && endMin ? `${endHour}:${endMin}` : '';

            const fieldset = $(`
                <div class="mb-3 p-3 border rounded">
                    <h6>${dayNames[index]}</h6>
                    <div class="form-check mb-2">
                        <input class="form-check-input day-closed-check" type="checkbox"
                               id="closed_${day}" data-day="${day}" ${isClosed ? 'checked' : ''}>
                        <label class="form-check-label" for="closed_${day}">Stengt</label>
                    </div>
                    <div class="row time-fields" ${isClosed ? 'style="display: none;"' : ''}>
                        <div class="col-md-6">
                            <label class="form-label">Åpner</label>
                            <div class="d-flex gap-2">
                                <select class="form-select" id="start_hour_${day}" style="width: auto;" ${isClosed ? '' : 'required'}>
                                    <option value="">Timer</option>
                                    ${generateHourOptions(startHour)}
                                </select>
                                <span class="align-self-center">:</span>
                                <select class="form-select" id="start_min_${day}" style="width: auto;" ${isClosed ? '' : 'required'}>
                                    ${generateMinuteOptions(startMin)}
                                </select>
                                <input type="hidden" id="start_${day}" name="hours[${day}][start]" value="${startTimeValue}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stenger</label>
                            <div class="d-flex gap-2">
                                <select class="form-select" id="end_hour_${day}" style="width: auto;" ${isClosed ? '' : 'required'}>
                                    <option value="">Timer</option>
                                    ${generateHourOptions(endHour)}
                                </select>
                                <span class="align-self-center">:</span>
                                <select class="form-select" id="end_min_${day}" style="width: auto;" ${isClosed ? '' : 'required'}>
                                    ${generateMinuteOptions(endMin)}
                                </select>
                                <input type="hidden" id="end_${day}" name="hours[${day}][end]" value="${endTimeValue}">
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="hours[${day}][closed]" class="closed-hidden" value="${isClosed ? '1' : '0'}">
                </div>
            `);

            container.append(fieldset);
        });

        // Add change handlers for select boxes
        container.find('select').on('change', function() {
            const id = $(this).attr('id');
            const day = id.split('_').pop();
            const isStart = id.includes('start');
            
            const hourSelect = $(`#${isStart ? 'start' : 'end'}_hour_${day}`);
            const minSelect = $(`#${isStart ? 'start' : 'end'}_min_${day}`);
            const hiddenInput = $(`#${isStart ? 'start' : 'end'}_${day}`);
            
            const hour = hourSelect.val();
            const min = minSelect.val();
            
            if (hour && min) {
                hiddenInput.val(`${hour}:${min}`);
            } else {
                hiddenInput.val('');
            }
        });

        // Add event handlers for closed checkboxes
        $('.day-closed-check').change(function() {
            const day = $(this).data('day');
            const isClosed = $(this).is(':checked');
            const timeFields = $(this).closest('.mb-3').find('.time-fields');
            const hiddenField = $(this).closest('.mb-3').find('.closed-hidden');
            const selects = timeFields.find('select');

            if (isClosed) {
                timeFields.hide();
                selects.prop('required', false);
                hiddenField.val('1');
                // Clear hidden time inputs
                $(`#start_${day}`).val('');
                $(`#end_${day}`).val('');
            } else {
                timeFields.show();
                selects.prop('required', true);
                hiddenField.val('0');
            }
        });
    }

    function saveSpecialHours() {
        const formData = new FormData($('#addSpecialHoursForm')[0]);

        $.ajax({
            url: '/admin/opening-hours/special',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false
        })
        .done(function(response) {
            showAlert(response.message, 'success');
            $('#addSpecialHoursModal').modal('hide');
            $('#addSpecialHoursForm')[0].reset();
            loadCalendarData();
        })
        .fail(function(xhr) {
            const response = xhr.responseJSON;
            showAlert(response?.message || 'Feil ved lagring', 'danger');
        });
    }

    function updateSpecialHours() {
        const specialId = $('#editSpecialId').val();
        const formData = new FormData($('#editSpecialHoursForm')[0]);

        $.ajax({
            url: `/admin/opening-hours/special/${specialId}`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-HTTP-Method-Override': 'PUT'
            }
        })
        .done(function(response) {
            showAlert(response.message, 'success');
            $('#editSpecialHoursModal').modal('hide');
            loadCalendarData();
        })
        .fail(function(xhr) {
            const response = xhr.responseJSON;
            showAlert(response?.message || 'Feil ved oppdatering', 'danger');
        });
    }

    function deleteSpecialHours() {
        const specialId = $('#editSpecialId').val();

        $.ajax({
            url: `/admin/opening-hours/special/${specialId}`,
            method: 'POST',
            headers: {
                'X-HTTP-Method-Override': 'DELETE'
            }
        })
        .done(function(response) {
            showAlert(response.message, 'success');
            $('#editSpecialHoursModal').modal('hide');
            loadCalendarData();
        })
        .fail(function(xhr) {
            const response = xhr.responseJSON;
            showAlert(response?.message || 'Feil ved sletting', 'danger');
        });
    }

    function saveRegularHours() {
        const formData = new FormData($('#editRegularHoursForm')[0]);
        
        // Convert FormData to JSON object
        const hours = {};
        const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        days.forEach(day => {
            const startTime = formData.get(`hours[${day}][start]`) || null;
            const endTime = formData.get(`hours[${day}][end]`) || null;
            const isClosed = formData.get(`hours[${day}][closed]`) === '1';
            
            hours[day] = {
                start: isClosed ? null : startTime,
                end: isClosed ? null : endTime,
                closed: isClosed
            };
        });

        // Debug logging
        console.log('Sending hours data:', hours);
        console.log('Sample time values:', {
            mondayStart: hours.monday.start,
            mondayEnd: hours.monday.end
        });

        $.ajax({
            url: `/admin/opening-hours/regular/${currentLocationId}`,
            method: 'POST',
            data: JSON.stringify({ hours: hours }),
            contentType: 'application/json',
            headers: {
                'X-HTTP-Method-Override': 'PUT',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .done(function(response) {
            showAlert(response.message, 'success');
            $('#editRegularHoursModal').modal('hide');
            loadCalendarData();
        })
        .fail(function(xhr) {
            const response = xhr.responseJSON;
            console.error('Save regular hours error:', response);
            
            let errorMessage = 'Feil ved lagring';
            
            if (response) {
                if (response.errors) {
                    // Show validation errors
                    const errors = Object.values(response.errors).flat();
                    errorMessage = errors.join('<br>');
                } else if (response.message) {
                    errorMessage = response.message;
                }
            }
            
            showAlert(errorMessage, 'danger');
        });
    }

    function showAlert(message, type) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Remove existing alerts
        $('.alert').remove();

        // Add new alert at top of container
        $('.container-fluid').prepend(alertHtml);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            $('.alert').fadeOut();
        }, 5000);
    }
});
</script>
@endpush
