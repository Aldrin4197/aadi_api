<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - Aadi</title>
    
    {{-- Shared Tailwind 4 theme, built by Vite --}}
    @vite('resources/css/app.css')
    
    {{-- Alpine.js for interactive components --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    
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
