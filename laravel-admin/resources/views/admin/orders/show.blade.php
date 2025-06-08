@if(request()->ajax())
    {{-- Modal content only --}}
    <div class="order-details">
        <div class="row mb-3">
            <div class="col-md-6">
                <h6>Ordre informasjon</h6>
                <table class="table table-sm">
                    <tr>
                        <th>Ordre ID:</th>
                        <td>#{{ $order->ordreid }}</td>
                    </tr>
                    <tr>
                        <th>Dato/tid:</th>
                        <td>{{ $order->datetime->format('d.m.Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Hentetid:</th>
                        <td>{{ $order->hentetid ?? 'Ikke spesifisert' }}</td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            @switch($order->ordrestatus)
                                @case(0)
                                    <span class="badge bg-warning">Ny ordre</span>
                                    @break
                                @case(1)
                                    <span class="badge bg-info">Klar for henting</span>
                                    @break
                                @case(2)
                                    <span class="badge bg-success">Hentet</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">Ukjent</span>
                            @endswitch
                        </td>
                    </tr>
                    <tr>
                        <th>Betaling:</th>
                        <td>
                            <span class="badge {{ $order->paid ? 'bg-success' : 'bg-danger' }}">
                                {{ $order->paid ? 'Betalt' : 'Ubetalt' }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Kunde informasjon</h6>
                <table class="table table-sm">
                    <tr>
                        <th>Navn:</th>
                        <td>{{ $order->fornavn }} {{ $order->etternavn }}</td>
                    </tr>
                    <tr>
                        <th>Telefon:</th>
                        <td>{{ $order->telefon }}</td>
                    </tr>
                    <tr>
                        <th>E-post:</th>
                        <td>{{ $order->epost ?? 'Ikke oppgitt' }}</td>
                    </tr>
                    <tr>
                        <th>Adresse:</th>
                        <td>{{ $order->adresse ?? 'Ikke oppgitt' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <h6>Ordre detaljer</h6>
                <div class="card">
                    <div class="card-body">
                        <pre class="mb-0">{{ $order->ekstrainfo }}</pre>
                    </div>
                </div>
            </div>
        </div>

        @if($order->kommentar)
        <div class="row mt-3">
            <div class="col-12">
                <h6>Kommentar fra kunde</h6>
                <div class="alert alert-info">
                    {{ $order->kommentar }}
                </div>
            </div>
        </div>
        @endif

        <div class="row mt-3">
            <div class="col-12">
                <div class="d-flex justify-content-end gap-2">
                    @if($order->ordrestatus == 0)
                        <button class="btn btn-success" onclick="markOrderReady({{ $order->id }})">
                            <i class="fas fa-check me-1"></i>Klar for henting
                        </button>
                    @endif
                    
                    @if($order->ordrestatus == 1 && !$order->sms)
                        <button class="btn btn-warning" onclick="sendReadySMS({{ $order->id }})">
                            <i class="fas fa-sms me-1"></i>Send SMS
                        </button>
                    @endif
                    
                    @if($order->ordrestatus == 1)
                        <button class="btn btn-primary" onclick="markOrderCompleted({{ $order->id }})">
                            <i class="fas fa-check-double me-1"></i>Marker som hentet
                        </button>
                    @endif
                    
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Lukk</button>
                </div>
            </div>
        </div>
    </div>
@else
    {{-- Full page view --}}
    @extends('layouts.admin')

    @section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Ordre #{{ $order->ordreid }}</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Tilbake
            </a>
        </div>
    </div>

    <div class="order-details">
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Ordre informasjon</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th>Ordre ID:</th>
                                <td>#{{ $order->ordreid }}</td>
                            </tr>
                            <tr>
                                <th>Dato/tid:</th>
                                <td>{{ $order->datetime->format('d.m.Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Hentetid:</th>
                                <td>{{ $order->hentetid ?? 'Ikke spesifisert' }}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    @switch($order->ordrestatus)
                                        @case(0)
                                            <span class="badge bg-warning">Ny ordre</span>
                                            @break
                                        @case(1)
                                            <span class="badge bg-info">Klar for henting</span>
                                            @break
                                        @case(2)
                                            <span class="badge bg-success">Hentet</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">Ukjent</span>
                                    @endswitch
                                </td>
                            </tr>
                            <tr>
                                <th>Betaling:</th>
                                <td>
                                    <span class="badge {{ $order->paid ? 'bg-success' : 'bg-danger' }}">
                                        {{ $order->paid ? 'Betalt' : 'Ubetalt' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>POS status:</th>
                                <td>
                                    @if($order->curl > 0)
                                        <span class="badge bg-success">Sendt ({{ $order->curl }})</span>
                                    @else
                                        <span class="badge bg-warning">Ikke sendt</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>SMS status:</th>
                                <td>
                                    <span class="badge {{ $order->sms ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $order->sms ? 'Sendt' : 'Ikke sendt' }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Kunde informasjon</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th>Navn:</th>
                                <td>{{ $order->fornavn }} {{ $order->etternavn }}</td>
                            </tr>
                            <tr>
                                <th>Telefon:</th>
                                <td>{{ $order->telefon }}</td>
                            </tr>
                            <tr>
                                <th>E-post:</th>
                                <td>{{ $order->epost ?? 'Ikke oppgitt' }}</td>
                            </tr>
                            <tr>
                                <th>Adresse:</th>
                                <td>{{ $order->adresse ?? 'Ikke oppgitt' }}</td>
                            </tr>
                            <tr>
                                <th>Postnr/Sted:</th>
                                <td>{{ $order->postnr ?? '' }} {{ $order->poststed ?? '' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Ordre detaljer</h5>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0">{{ $order->ekstrainfo }}</pre>
                    </div>
                </div>
            </div>
        </div>

        @if($order->kommentar)
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Kommentar fra kunde</h5>
                    </div>
                    <div class="card-body">
                        {{ $order->kommentar }}
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between">
                    <div>
                        @if(!$order->paid)
                            <button class="btn btn-success" onclick="markAsPaid({{ $order->id }})">
                                <i class="fas fa-check-circle me-1"></i>Marker som betalt
                            </button>
                        @endif
                        
                        @if($order->curl == 0)
                            <button class="btn btn-info ms-2" onclick="sendToPOS({{ $order->id }})">
                                <i class="fas fa-cash-register me-1"></i>Send til POS
                            </button>
                        @endif
                    </div>
                    
                    <div>
                        @if($order->ordrestatus == 0)
                            <button class="btn btn-primary" onclick="updateStatus({{ $order->id }}, 1)">
                                <i class="fas fa-check me-1"></i>Klar for henting
                            </button>
                        @endif
                        
                        @if($order->ordrestatus == 1 && !$order->sms)
                            <button class="btn btn-warning ms-2" onclick="sendSMS({{ $order->id }})">
                                <i class="fas fa-sms me-1"></i>Send SMS
                            </button>
                        @endif
                        
                        @if($order->ordrestatus == 1)
                            <button class="btn btn-success ms-2" onclick="updateStatus({{ $order->id }}, 2)">
                                <i class="fas fa-check-double me-1"></i>Marker som hentet
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function updateStatus(orderId, status) {
        fetch(`/admin/orders/${orderId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Feil: ' + data.message);
            }
        });
    }

    function markAsPaid(orderId) {
        if (confirm('Marker ordre som betalt?')) {
            fetch(`/admin/orders/${orderId}/mark-paid`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Feil: ' + data.message);
                }
            });
        }
    }

    function sendToPOS(orderId) {
        if (confirm('Send ordre til POS system?')) {
            fetch(`/admin/orders/${orderId}/send-pos`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Feil: ' + data.message);
                }
            });
        }
    }

    function sendSMS(orderId) {
        if (confirm('Send SMS til kunde?')) {
            fetch(`/admin/orders/${orderId}/send-sms`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('SMS sendt!');
                    location.reload();
                } else {
                    alert('Feil: ' + data.message);
                }
            });
        }
    }
    </script>
    @endpush
    @endsection
@endif