@extends('layouts.catering', ['step' => 2])

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="mb-6">
                    <a href="{{ route('catering.index') }}" class="text-purple-600 hover:text-purple-800">
                        <i class="fas fa-arrow-left mr-1"></i> Tilbake til lokasjonsliste
                    </a>
                </div>

                <h1 class="text-3xl font-bold mb-2">Velg mat fra {{ $location->name }}</h1>
                <p class="text-gray-600 mb-8">Velg rettene du ønsker til din catering-bestilling</p>

                @if(session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                <form id="product-form" action="{{ route('catering.store-products') }}" method="POST">
                    @csrf
                    <input type="hidden" name="location_id" value="{{ $location->id }}">

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Products list -->
                        <div class="lg:col-span-2">
                            @if(empty($products))
                                <div class="text-center py-12">
                                    <p class="text-gray-500">Ingen produkter tilgjengelig akkurat nå.</p>
                                </div>
                            @else
                                <div class="space-y-4">
                                    @foreach($products as $product)
                                        <div class="border rounded-lg p-4 hover:border-purple-300 transition-colors">
                                            <div class="flex items-start">
                                                @if($product['image'])
                                                    <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" 
                                                         class="w-24 h-24 object-cover rounded-lg mr-4">
                                                @endif
                                                
                                                <div class="flex-1">
                                                    <h3 class="font-semibold text-lg">{{ $product['name'] }}</h3>
                                                    @if($product['description'])
                                                        <p class="text-sm text-gray-600 mt-1">{{ $product['description'] }}</p>
                                                    @endif
                                                    
                                                    <div class="mt-3 flex items-center justify-between">
                                                        <span class="text-lg font-bold text-purple-600">
                                                            {{ number_format($product['price'], 0, ',', ' ') }} kr
                                                        </span>
                                                        
                                                        <div class="flex items-center">
                                                            <label class="mr-2 text-sm text-gray-600">Antall:</label>
                                                            <input type="number" 
                                                                   name="products[{{ $product['id'] }}][quantity]" 
                                                                   data-product-id="{{ $product['id'] }}"
                                                                   data-product-name="{{ $product['name'] }}"
                                                                   data-product-price="{{ $product['price'] }}"
                                                                   min="0" 
                                                                   value="0" 
                                                                   class="product-quantity w-20 rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                                            <input type="hidden" name="products[{{ $product['id'] }}][id]" value="{{ $product['id'] }}">
                                                            <input type="hidden" name="products[{{ $product['id'] }}][name]" value="{{ $product['name'] }}">
                                                            <input type="hidden" name="products[{{ $product['id'] }}][price]" value="{{ $product['price'] }}">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Order summary -->
                        <div class="lg:col-span-1">
                            <div class="sticky top-4">
                                <div class="bg-gray-50 rounded-lg p-6">
                                    <h3 class="font-semibold text-lg mb-4">Bestillingsoversikt</h3>
                                    
                                    <div id="selected-products" class="space-y-2 mb-4">
                                        <p class="text-sm text-gray-500 empty-message">Ingen produkter valgt ennå</p>
                                    </div>
                                    
                                    <div class="border-t pt-4">
                                        <div class="flex justify-between items-center mb-4">
                                            <span class="font-semibold">Total:</span>
                                            <span id="total-amount" class="text-xl font-bold text-purple-600">0 kr</span>
                                        </div>
                                        
                                        <div class="space-y-2 text-sm text-gray-600 mb-4">
                                            <p>Min. antall gjester: <strong>{{ $cateringSettings->min_guests }}</strong></p>
                                            <p>Min. bestillingsbeløp: <strong>{{ number_format($cateringSettings->min_order_amount, 0, ',', ' ') }} kr</strong></p>
                                        </div>
                                        
                                        <button type="submit" 
                                                id="continue-button"
                                                disabled
                                                class="w-full bg-purple-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-purple-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors">
                                            Fortsett til bestillingsskjema
                                        </button>
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
    const form = document.getElementById('product-form');
    const quantityInputs = document.querySelectorAll('.product-quantity');
    const selectedProductsDiv = document.getElementById('selected-products');
    const totalAmountSpan = document.getElementById('total-amount');
    const continueButton = document.getElementById('continue-button');
    const minOrderAmount = {{ $cateringSettings->min_order_amount }};
    
    function updateOrderSummary() {
        let total = 0;
        let hasProducts = false;
        let html = '';
        
        quantityInputs.forEach(input => {
            const quantity = parseInt(input.value) || 0;
            if (quantity > 0) {
                hasProducts = true;
                const name = input.dataset.productName;
                const price = parseFloat(input.dataset.productPrice);
                const subtotal = price * quantity;
                total += subtotal;
                
                html += `
                    <div class="flex justify-between text-sm">
                        <span>${quantity}x ${name}</span>
                        <span>${subtotal.toLocaleString('nb-NO')} kr</span>
                    </div>
                `;
            }
        });
        
        if (!hasProducts) {
            html = '<p class="text-sm text-gray-500 empty-message">Ingen produkter valgt ennå</p>';
        }
        
        selectedProductsDiv.innerHTML = html;
        totalAmountSpan.textContent = total.toLocaleString('nb-NO') + ' kr';
        
        // Enable/disable continue button
        continueButton.disabled = !hasProducts || total < minOrderAmount;
        
        if (hasProducts && total < minOrderAmount) {
            continueButton.textContent = `Minimum ${minOrderAmount.toLocaleString('nb-NO')} kr (mangler ${(minOrderAmount - total).toLocaleString('nb-NO')} kr)`;
        } else {
            continueButton.textContent = 'Fortsett til bestillingsskjema';
        }
    }
    
    quantityInputs.forEach(input => {
        input.addEventListener('input', updateOrderSummary);
    });
    
    // Clean up form data before submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Remove products with quantity 0
        quantityInputs.forEach(input => {
            if (parseInt(input.value) === 0) {
                const productId = input.dataset.productId;
                const productInputs = form.querySelectorAll(`[name^="products[${productId}]"]`);
                productInputs.forEach(el => el.remove());
            }
        });
        
        form.submit();
    });
});
</script>
@endpush