@extends('layouts.catering', ['step' => 3])

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h1 class="text-3xl font-bold mb-2">Fullfør catering-bestilling</h1>
                <p class="text-gray-600 mb-8">Fyll ut informasjonen nedenfor for å fullføre bestillingen</p>

                @if($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('catering.store') }}" method="POST" id="catering-form">
                    @csrf
                    <input type="hidden" name="location_id" value="{{ $location->id }}">
                    
                    <!-- Products (hidden) -->
                    @foreach($selectedProducts as $product)
                        <input type="hidden" name="products[{{ $loop->index }}][id]" value="{{ $product['id'] }}">
                        <input type="hidden" name="products[{{ $loop->index }}][name]" value="{{ $product['name'] }}">
                        <input type="hidden" name="products[{{ $loop->index }}][price]" value="{{ $product['price'] }}">
                        <input type="hidden" name="products[{{ $loop->index }}][quantity]" value="{{ $product['quantity'] }}">
                    @endforeach

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2 space-y-8">
                            <!-- Delivery Information -->
                            <div class="border rounded-lg p-6">
                                <h2 class="text-xl font-semibold mb-4">Leveringsinformasjon</h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Leveringsdato <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" 
                                               name="delivery_date" 
                                               min="{{ $minDate }}"
                                               value="{{ old('delivery_date') }}"
                                               required
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Leveringstid <span class="text-red-500">*</span>
                                        </label>
                                        <select name="delivery_time" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                            <option value="">Velg tid</option>
                                            @foreach($cateringSettings->delivery_times as $time)
                                                <option value="{{ $time }}" {{ old('delivery_time') == $time ? 'selected' : '' }}>
                                                    {{ $time }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Leveringsadresse <span class="text-red-500">*</span>
                                        </label>
                                        <textarea name="delivery_address" 
                                                  rows="3" 
                                                  required
                                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">{{ old('delivery_address') }}</textarea>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Antall gjester <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" 
                                               name="number_of_guests" 
                                               min="{{ $cateringSettings->min_guests }}"
                                               value="{{ old('number_of_guests', $cateringSettings->min_guests) }}"
                                               required
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="border rounded-lg p-6">
                                <h2 class="text-xl font-semibold mb-4">Kontaktinformasjon</h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Kontaktperson <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" 
                                               name="contact_name" 
                                               value="{{ old('contact_name') }}"
                                               required
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Telefonnummer <span class="text-red-500">*</span>
                                        </label>
                                        <input type="tel" 
                                               name="contact_phone" 
                                               value="{{ old('contact_phone') }}"
                                               required
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            E-post <span class="text-red-500">*</span>
                                        </label>
                                        <input type="email" 
                                               name="contact_email" 
                                               value="{{ old('contact_email') }}"
                                               required
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                    </div>
                                </div>
                            </div>

                            <!-- Invoice Information -->
                            <div class="border rounded-lg p-6">
                                <h2 class="text-xl font-semibold mb-4">Fakturainformasjon</h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Firmanavn <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" 
                                               name="company_name" 
                                               value="{{ old('company_name') }}"
                                               required
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Organisasjonsnummer <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" 
                                               name="company_org_number" 
                                               value="{{ old('company_org_number') }}"
                                               placeholder="123 456 789"
                                               required
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                    </div>
                                    
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Fakturaadresse <span class="text-red-500">*</span>
                                        </label>
                                        <textarea name="invoice_address" 
                                                  rows="3" 
                                                  required
                                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">{{ old('invoice_address') }}</textarea>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Faktura e-post <span class="text-red-500">*</span>
                                        </label>
                                        <input type="email" 
                                               name="invoice_email" 
                                               value="{{ old('invoice_email') }}"
                                               required
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Information -->
                            <div class="border rounded-lg p-6">
                                <h2 class="text-xl font-semibold mb-4">Tilleggsinformasjon</h2>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Spesielle krav (allergier, diett, etc.)
                                        </label>
                                        <textarea name="special_requirements" 
                                                  rows="3" 
                                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">{{ old('special_requirements') }}</textarea>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Andre kommentarer
                                        </label>
                                        <textarea name="catering_notes" 
                                                  rows="3" 
                                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">{{ old('catering_notes') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Summary Sidebar -->
                        <div class="lg:col-span-1">
                            <div class="sticky top-4">
                                <div class="bg-gray-50 rounded-lg p-6">
                                    <h3 class="font-semibold text-lg mb-4">Din bestilling</h3>
                                    
                                    <div class="space-y-2 mb-4">
                                        @php $total = 0; @endphp
                                        @foreach($selectedProducts as $product)
                                            @php $subtotal = $product['price'] * $product['quantity']; $total += $subtotal; @endphp
                                            <div class="flex justify-between text-sm">
                                                <span>{{ $product['quantity'] }}x {{ $product['name'] }}</span>
                                                <span>{{ number_format($subtotal, 0, ',', ' ') }} kr</span>
                                            </div>
                                        @endforeach
                                    </div>
                                    
                                    <div class="border-t pt-4">
                                        <div class="flex justify-between items-center mb-6">
                                            <span class="font-semibold">Total:</span>
                                            <span class="text-xl font-bold text-purple-600">{{ number_format($total, 0, ',', ' ') }} kr</span>
                                        </div>
                                        
                                        <button type="submit" class="w-full bg-purple-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-purple-700 transition-colors">
                                            Send bestilling
                                        </button>
                                        
                                        <p class="mt-4 text-xs text-gray-600 text-center">
                                            Ved å sende bestillingen godtar du våre vilkår. Du vil motta faktura på e-post etter levering.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deliveryDateInput = document.querySelector('input[name="delivery_date"]');
    const blockedDates = @json($blockedDates);
    
    // Disable blocked dates
    deliveryDateInput.addEventListener('input', function() {
        const selectedDate = this.value;
        if (blockedDates.includes(selectedDate)) {
            alert('Denne datoen er ikke tilgjengelig for catering. Vennligst velg en annen dato.');
            this.value = '';
        }
    });
    
    // Form validation
    const form = document.getElementById('catering-form');
    form.addEventListener('submit', function(e) {
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Sender bestilling...';
    });
});
</script>
@endpush