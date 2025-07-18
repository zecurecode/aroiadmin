@extends('layouts.catering')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-center">
                <div class="mb-6">
                    <div class="mx-auto flex items-center justify-center w-16 h-16 bg-green-100 rounded-full">
                        <i class="fas fa-check text-green-600 text-3xl"></i>
                    </div>
                </div>
                
                <h1 class="text-3xl font-bold mb-2">Bestilling mottatt!</h1>
                <p class="text-gray-600 mb-8">Vi har mottatt din catering-bestilling og sender deg en bekreftelse på e-post.</p>

                <div class="bg-gray-50 rounded-lg p-6 mb-8 text-left max-w-2xl mx-auto">
                    <h2 class="font-semibold text-lg mb-4">Bestillingsdetaljer</h2>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Ordrenummer:</span>
                            <span class="font-medium">{{ $order->order_number }}</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Lokasjon:</span>
                            <span class="font-medium">{{ $order->location->name }}</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Leveringsdato:</span>
                            <span class="font-medium">{{ $order->delivery_date->format('d.m.Y') }}</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Leveringstid:</span>
                            <span class="font-medium">{{ $order->delivery_time }}</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Antall gjester:</span>
                            <span class="font-medium">{{ $order->number_of_guests }}</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total beløp:</span>
                            <span class="font-bold text-purple-600 text-lg">{{ number_format($order->total_amount, 0, ',', ' ') }} kr</span>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t">
                        <h3 class="font-medium mb-2">Leveringsadresse:</h3>
                        <p class="text-gray-600">{{ $order->delivery_address }}</p>
                    </div>
                    
                    <div class="mt-4">
                        <h3 class="font-medium mb-2">Bestilte produkter:</h3>
                        <div class="space-y-1">
                            @foreach($order->formatted_products as $product)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">{{ $product->quantity }}x {{ $product->name }}</span>
                                    <span class="text-gray-600">{{ number_format($product->total, 0, ',', ' ') }} kr</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 rounded-lg p-6 mb-8 text-left max-w-2xl mx-auto">
                    <h3 class="font-semibold mb-2">Hva skjer nå?</h3>
                    <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700">
                        <li>Du vil motta en bekreftelse på e-post til <strong>{{ $order->contact_email }}</strong></li>
                        <li>Vi forbereder din bestilling og leverer på avtalt tid</li>
                        <li>Faktura sendes til <strong>{{ $order->invoice_email }}</strong> etter levering</li>
                        <li>Betalingsfrist er 14 dager fra fakturadato</li>
                    </ol>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('catering.index') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-lg text-base font-medium text-white bg-purple-600 hover:bg-purple-700">
                        Ny catering-bestilling
                    </a>
                    
                    <button onclick="window.print()" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-lg text-base font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-print mr-2"></i> Skriv ut bekreftelse
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection