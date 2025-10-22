<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Aroi Asia - Catering</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Additional Styles -->
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center">
                        <a href="{{ route('catering.index') }}" class="flex items-center">
                            <img src="/images/logo.png" alt="Aroi" class="h-10 w-auto">
                            <span class="ml-3 text-xl font-semibold text-gray-900">Catering</span>
                        </a>
                    </div>
                    
                    <!-- Progress indicator for multi-step form -->
                    @if(isset($step))
                    <div class="hidden md:flex items-center space-x-4">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $step >= 1 ? 'bg-purple-600 text-white' : 'bg-gray-300 text-gray-600' }}">
                                1
                            </div>
                            <span class="ml-2 text-sm {{ $step >= 1 ? 'text-gray-900' : 'text-gray-500' }}">Velg lokasjon</span>
                        </div>
                        
                        <div class="w-16 h-0.5 {{ $step >= 2 ? 'bg-purple-600' : 'bg-gray-300' }}"></div>
                        
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $step >= 2 ? 'bg-purple-600 text-white' : 'bg-gray-300 text-gray-600' }}">
                                2
                            </div>
                            <span class="ml-2 text-sm {{ $step >= 2 ? 'text-gray-900' : 'text-gray-500' }}">Velg mat</span>
                        </div>
                        
                        <div class="w-16 h-0.5 {{ $step >= 3 ? 'bg-purple-600' : 'bg-gray-300' }}"></div>
                        
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $step >= 3 ? 'bg-purple-600 text-white' : 'bg-gray-300 text-gray-600' }}">
                                3
                            </div>
                            <span class="ml-2 text-sm {{ $step >= 3 ? 'text-gray-900' : 'text-gray-500' }}">Bestillingsskjema</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main>
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="text-center text-sm text-gray-500">
                    <p>&copy; {{ date('Y') }} Aroi Food Truck. Alle rettigheter reservert.</p>
                    <p class="mt-2">
                        <a href="tel:+4712345678" class="text-purple-600 hover:text-purple-800">
                            <i class="fas fa-phone mr-1"></i> Kontakt oss
                        </a>
                    </p>
                </div>
            </div>
        </footer>
    </div>

    <!-- Scripts -->
    @stack('scripts')
</body>
</html>