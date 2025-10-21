@if(request()->ajax())
    {{-- Modal content only --}}
    <div class="order-details">
        {{-- Order Header --}}
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Ordre #{{ $order->ordreid }}</h5>
                            <small>Opprettet: {{ $order->datetime->format('d.m.Y H:i:s') }}</small>
                        </div>
                        <div class="text-end">
                            @switch($order->ordrestatus)
                                @case(0)
                                    <span class="badge bg-warning fs-6">NY ORDRE</span>
                                    @break
                                @case(1)
                                    <span class="badge bg-info fs-6">KLAR FOR HENTING</span>
                                    @break
                                @case(2)
                                    <span class="badge bg-success fs-6">HENTET</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary fs-6">UKJENT STATUS</span>
                            @endswitch
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Customer Information --}}
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-user me-1"></i>Kundeinformasjon</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <th width="40%">Navn:</th>
                                <td><strong>{{ $order->fornavn }} {{ $order->etternavn }}</strong></td>
                            </tr>
                            <tr>
                                <th>Telefon:</th>
                                <td>
                                    <a href="tel:{{ $order->telefon }}" class="text-decoration-none">
                                        <i class="fas fa-phone me-1"></i>{{ $order->telefon }}
                                    </a>
                                </td>
                            </tr>
                            @if($order->epost)
                            <tr>
                                <th>E-post:</th>
                                <td>
                                    <a href="mailto:{{ $order->epost }}" class="text-decoration-none">
                                        <i class="fas fa-envelope me-1"></i>{{ $order->epost }}
                                    </a>
                                </td>
                            </tr>
                            @endif
                            @if($wooOrder && isset($wooOrder['billing']))
                            <tr>
                                <th>Adresse:</th>
                                <td>
                                    @if($wooOrder['billing']['address_1'])
                                        {{ $wooOrder['billing']['address_1'] }}<br>
                                    @endif
                                    @if($wooOrder['billing']['postcode'] || $wooOrder['billing']['city'])
                                        {{ $wooOrder['billing']['postcode'] }} {{ $wooOrder['billing']['city'] }}
                                    @endif
                                </td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            {{-- Order Status & Payment --}}
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-1"></i>Ordrestatus</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <th width="40%">Status:</th>
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
                                    @if($order->paid)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Betalt
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times me-1"></i>Ikke betalt
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @if($wooOrder)
                            <tr>
                                <th>Betalingsmåte:</th>
                                <td>{{ $wooOrder['payment_method_title'] ?? 'Ikke spesifisert' }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th>SMS status:</th>
                                <td>
                                    @if($order->sms)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Sendt
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-clock me-1"></i>Ikke sendt
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>POS system:</th>
                                <td>
                                    @if($order->curl && $order->curl != 0)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Sendt ({{ $order->curl }})
                                        </span>
                                        @if($order->curltime)
                                            <br><small class="text-muted">{{ $order->curltime->format('d.m.Y H:i') }}</small>
                                        @endif
                                    @else
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock me-1"></i>Ikke sendt
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- WooCommerce Order Details --}}
        @if($wooOrder)
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-shopping-cart me-1"></i>Ordredetaljer fra WooCommerce</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">Total:</th>
                                        <td><strong>{{ $wooOrder['total'] ?? '0' }} {{ $wooOrder['currency'] ?? 'NOK' }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>WooCommerce Status:</th>
                                        <td>
                                            <span class="badge bg-secondary">{{ $wooOrder['status'] ?? 'Ukjent' }}</span>
                                        </td>
                                    </tr>
                                    @if(isset($wooOrder['date_created']))
                                    <tr>
                                        <th>Opprettet i WC:</th>
                                        <td>{{ \Carbon\Carbon::parse($wooOrder['date_created'])->format('d.m.Y H:i') }}</td>
                                    </tr>
                                    @endif
                                    @if(isset($wooOrder['date_completed']))
                                    <tr>
                                        <th>Fullført:</th>
                                        <td>{{ \Carbon\Carbon::parse($wooOrder['date_completed'])->format('d.m.Y H:i') }}</td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                            <div class="col-md-6">
                                @if(isset($wooOrder['meta_data']))
                                <table class="table table-sm">
                                    @foreach($wooOrder['meta_data'] as $meta)
                                        @if($meta['key'] == 'hentetidspunkt' || $meta['key'] == '_hentetidspunkt')
                                        <tr>
                                            <th width="40%">Hentetidspunkt:</th>
                                            <td><strong>{{ $meta['value'] }}</strong></td>
                                        </tr>
                                        @endif
                                        @if($meta['key'] == 'hentes' || $meta['key'] == '_hentes')
                                        <tr>
                                            <th>Hentes:</th>
                                            <td>{{ $meta['value'] }}</td>
                                        </tr>
                                        @endif
                                    @endforeach
                                </table>
                                @endif
                            </div>
                        </div>

                        {{-- Order Items --}}
                        @if(isset($wooOrder['line_items']) && count($wooOrder['line_items']) > 0)
                        <h6 class="border-bottom pb-2 mb-3">Bestilte produkter</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Produkt</th>
                                        <th class="text-center">Antall</th>
                                        <th class="text-end">Pris</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($wooOrder['line_items'] as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item['name'] }}</strong>
                                            @if(isset($item['meta_data']) && count($item['meta_data']) > 0)
                                                <br>
                                                @foreach($item['meta_data'] as $meta)
                                                    @if($meta['key'] !== '_' && !str_starts_with($meta['key'], '_'))
                                                        <small class="text-muted">{{ $meta['display_key'] ?? $meta['key'] }}: {{ $meta['display_value'] ?? $meta['value'] }}</small><br>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $item['quantity'] }}</td>
                                        <td class="text-end">{{ number_format($item['price'], 2) }} {{ $wooOrder['currency'] }}</td>
                                        <td class="text-end"><strong>{{ number_format($item['total'], 2) }} {{ $wooOrder['currency'] }}</strong></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-dark">
                                    <tr>
                                        <th colspan="3" class="text-end">Total:</th>
                                        <th class="text-end">{{ number_format($wooOrder['total'], 2) }} {{ $wooOrder['currency'] }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Customer Notes & Comments --}}
        @php
            $customerNote = null;
            $orderComments = [];

            // Check for customer note in main WooCommerce data
            if ($wooOrder && !empty($wooOrder['customer_note'])) {
                $customerNote = $wooOrder['customer_note'];
            }

            // Check for customer note in meta data
            if ($wooOrder && isset($wooOrder['meta_data']) && !$customerNote) {
                foreach ($wooOrder['meta_data'] as $meta) {
                    if (in_array($meta['key'], ['customer_note', '_customer_note', 'order_comments'])) {
                        $customerNote = $meta['value'];
                        break;
                    }
                }
            }

            // Check for order comments/notes in meta data
            if ($wooOrder && isset($wooOrder['meta_data'])) {
                foreach ($wooOrder['meta_data'] as $meta) {
                    if (strpos($meta['key'], 'note') !== false || strpos($meta['key'], 'comment') !== false) {
                        if (!empty($meta['value']) && $meta['value'] !== $customerNote) {
                            $orderComments[] = [
                                'key' => $meta['display_key'] ?? $meta['key'],
                                'value' => $meta['display_value'] ?? $meta['value']
                            ];
                        }
                    }
                }
            }

            $hasAnyNotes = $customerNote || !empty($order->kommentar) || !empty($order->ekstrainfo) || !empty($orderComments);
        @endphp

        @if($hasAnyNotes)
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-comment me-1"></i>Notater og kommentarer</h6>
                    </div>
                    <div class="card-body">
                        @if($customerNote)
                        <div class="alert alert-info">
                            <strong><i class="fas fa-user me-1"></i>Kundens notat fra WooCommerce:</strong><br>
                            <span class="fs-6">{{ $customerNote }}</span>
                        </div>
                        @endif

                        @foreach($orderComments as $comment)
                        <div class="alert alert-info">
                            <strong><i class="fas fa-info-circle me-1"></i>{{ $comment['key'] }}:</strong><br>
                            <span class="fs-6">{{ $comment['value'] }}</span>
                        </div>
                        @endforeach

                        @if(!empty($order->kommentar))
                        <div class="alert alert-secondary">
                            <strong><i class="fas fa-sticky-note me-1"></i>Intern kommentar:</strong><br>
                            <span class="fs-6">{{ $order->kommentar }}</span>
                        </div>
                        @endif

                        @if(!empty($order->ekstrainfo))
                        <div class="alert alert-light">
                            <strong><i class="fas fa-info me-1"></i>Ekstra informasjon:</strong><br>
                            <pre class="mb-0 fs-6">{{ $order->ekstrainfo }}</pre>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Debug Information (only shown in development and not in AJAX modals) --}}
        @if(config('app.debug') && $wooOrder && !request()->ajax())
        <div class="row mb-3">
            <div class="col-12">
                <div class="card border-secondary">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="fas fa-bug me-1"></i>WooCommerce Debug Data</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Tilgjengelige felter:</strong></p>
                        <small class="text-muted">{{ implode(', ', array_keys($wooOrder)) }}</small>

                        @if(isset($wooOrder['meta_data']))
                        <p class="mt-3"><strong>Meta data felter:</strong></p>
                        <div class="small">
                            @foreach($wooOrder['meta_data'] as $meta)
                                <div class="mb-1">
                                    <code>{{ $meta['key'] }}</code>: {{ Str::limit($meta['value'], 50) }}
                                </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Action Buttons --}}
        <div class="row mt-3">
            <div class="col-12">
                <div class="d-flex justify-content-end gap-2 flex-wrap">
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

                    @if(!$order->paid)
                        <button class="btn btn-success" onclick="markAsPaid({{ $order->id }})">
                            <i class="fas fa-credit-card me-1"></i>Marker som betalt
                        </button>
                    @endif

                    @if($order->curl == 0)
                        <button class="btn btn-info" onclick="sendToPOS({{ $order->id }})">
                            <i class="fas fa-cash-register me-1"></i>Send til POS
                        </button>
                    @endif

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Lukk
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function markOrderReady(orderId) {
        if (confirm('Marker ordre som klar for henting?')) {
            fetch(`/admin/orders/${orderId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status: 1 })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show message about SMS status
                    if (data.message) {
                        alert(data.message);
                    }
                    location.reload();
                } else {
                    alert('Feil: ' + data.message);
                }
            })
            .catch(error => {
                alert('Feil ved oppdatering av ordre: ' + error);
            });
        }
    }

    function sendReadySMS(orderId) {
        if (confirm('Send SMS til kunden om at ordren er klar?')) {
            fetch(`/admin/orders/${orderId}/send-sms`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
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

    function markOrderCompleted(orderId) {
        if (confirm('Marker ordre som hentet?')) {
            fetch(`/admin/orders/${orderId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status: 2 })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show message about SMS status
                    if (data.message) {
                        alert(data.message);
                    }
                    location.reload();
                } else {
                    alert('Feil: ' + data.message);
                }
            })
            .catch(error => {
                alert('Feil ved oppdatering av ordre: ' + error);
            });
        }
    }

    function markAsPaid(orderId) {
        if (confirm('Marker ordre som betalt?')) {
            fetch(`/admin/orders/${orderId}/mark-paid`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
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
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
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
    </script>

@else
    {{-- Full page view - redirect to orders list since we only want modal view --}}
    @extends('layouts.admin')

    @section('content')
    <div class="container-fluid">
        <div class="alert alert-info">
            <h4>Ordre detaljer</h4>
            <p>Ordredetaljer vises nå kun i modal-format. Du omdirigeres tilbake til ordrelisten.</p>
        </div>
        <script>
            setTimeout(function() {
                window.location.href = '{{ route('admin.orders.index') }}';
            }, 2000);
        </script>
    </div>
    @endsection
@endif
