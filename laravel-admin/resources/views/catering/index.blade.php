@extends('layouts.catering')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h1 class="text-3xl font-bold mb-2">Bestill catering</h1>
                <p class="text-gray-600 mb-8">Velg lokasjonen du ønsker å bestille catering fra</p>

                @if(session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                @if($locations->isEmpty())
                    <div class="text-center py-12">
                        <p class="text-gray-500">Ingen lokasjoner tilgjengelig for catering akkurat nå.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($locations as $location)
                            <a href="{{ route('catering.products', $location->id) }}" 
                               class="block bg-white border-2 border-gray-200 rounded-lg p-6 hover:border-purple-600 hover:shadow-lg transition-all duration-200">
                                <div class="flex items-start justify-between mb-4">
                                    <h3 class="text-xl font-semibold text-gray-900">{{ $location->name }}</h3>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Åpen for catering
                                    </span>
                                </div>
                                
                                @if($location->address)
                                    <div class="flex items-start mb-3">
                                        <i class="fas fa-map-marker-alt text-gray-400 mt-0.5 mr-2"></i>
                                        <p class="text-sm text-gray-600">{{ $location->address }}</p>
                                    </div>
                                @endif
                                
                                @if($location->phone)
                                    <div class="flex items-center mb-3">
                                        <i class="fas fa-phone text-gray-400 mr-2"></i>
                                        <p class="text-sm text-gray-600">{{ $location->phone }}</p>
                                    </div>
                                @endif
                                
                                @if($location->cateringSettings)
                                    <div class="border-t pt-3 mt-3">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-500">Min. gjester:</span>
                                            <span class="font-medium">{{ $location->cateringSettings->min_guests }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-sm mt-1">
                                            <span class="text-gray-500">Min. beløp:</span>
                                            <span class="font-medium">{{ number_format($location->cateringSettings->min_order_amount, 0, ',', ' ') }} kr</span>
                                        </div>
                                        <div class="flex items-center justify-between text-sm mt-1">
                                            <span class="text-gray-500">Bestill senest:</span>
                                            <span class="font-medium">{{ $location->cateringSettings->advance_notice_days }} dager før</span>
                                        </div>
                                    </div>
                                @endif
                                
                                <div class="mt-4 flex items-center text-purple-600 font-medium">
                                    <span>Velg denne lokasjonen</span>
                                    <i class="fas fa-arrow-right ml-2"></i>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
                
                <div class="mt-12 bg-gray-50 rounded-lg p-6">
                    <h2 class="text-lg font-semibold mb-3">Om vår catering-tjeneste</h2>
                    <div class="space-y-2 text-sm text-gray-600">
                        <p>• Perfekt for bedriftsarrangementer, feiringer og større sammenkomster</p>
                        <p>• Fersk mat laget med kjærlighet fra våre food trucks</p>
                        <p>• Fleksible menyer tilpasset dine behov</p>
                        <p>• Levering direkte til din adresse</p>
                        <p>• Faktura med betalingsfrist etter levering</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection