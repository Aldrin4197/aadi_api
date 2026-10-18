<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - Aadi</title>
    
    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    
    {{-- Alpine.js for interactive components --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    {{-- Custom Aadi Brand Colors --}}
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            DEFAULT: '#1FA6A0',
                            dark: '#0B3A38',
                            light: '#C4ECEA',
                            lighter: '#E3F6F4',
                        },
                        accent: {
                            DEFAULT: '#F59E42',
                            deep: '#D9822B',
                            light: '#FCE6CE',
                        }
                    },
                    fontFamily: {
                        sans: ['Quicksand', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    {{-- Google Fonts - Quicksand --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=quicksand:300,400,500,600,700" rel="stylesheet" />
    
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Quicksand', sans-serif; }
    </style>
    
    @stack('styles')
</head>
<body class="bg-slate-50">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <x-admin.sidebar />
        
        {{-- Main Content --}}
        <div class="flex-1 ml-64">
            {{-- Top Navbar --}}
            <x-admin.navbar title="@yield('title', 'Dashboard')" />
            
            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="px-6 pt-6">
                    <x-admin.alert type="success">
                        {{ session('success') }}
                    </x-admin.alert>
                </div>
            @endif
            
            @if(session('error'))
                <div class="px-6 pt-6">
                    <x-admin.alert type="error">
                        {{ session('error') }}
                    </x-admin.alert>
                </div>
            @endif
            
            {{-- Page Content --}}
            <main class="p-6">
                @yield('content')
            </main>
        </div>
    </div>
    
    @stack('scripts')
</body>
</html>
