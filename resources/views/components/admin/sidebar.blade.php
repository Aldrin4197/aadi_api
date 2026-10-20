@props([
    'user' => null,
])

@php
$currentUser = $user ?? auth()->user() ?? (object)['name' => 'Demo Admin', 'email' => 'demo@aadi.test'];
@endphp

<aside class="fixed left-0 top-0 h-full w-64 bg-linear-to-b from-[#0B3A38] to-[#062827] text-white shadow-xl z-40">
    {{-- Logo --}}
    <div class="flex items-center gap-3 px-6 py-5 border-b border-[#1FA6A0]/20">
        <div class="w-10 h-10 bg-linear-to-br from-[#1FA6A0] to-[#F59E42] rounded-lg flex items-center justify-center font-bold text-xl">
            A
        </div>
        <div>
            <h1 class="text-xl font-bold">Aadi</h1>
            <p class="text-xs text-[#C4ECEA]">Admin Dashboard</p>
        </div>
    </div>
    
    {{-- Navigation --}}
    <nav class="flex-1 px-4 py-6 space-y-6 overflow-y-auto">
        {{-- Overview Section --}}
        <div>
            <x-admin.nav-item href="/admin/dashboard" icon="grid" :active="request()->is('admin/dashboard')">
                Overview
            </x-admin.nav-item>
        </div>
        
        {{-- Users Section --}}
        <div>
            <p class="px-4 mb-2 text-xs font-semibold text-[#C4ECEA] uppercase tracking-wider">Users</p>
            <div class="space-y-1">
                <x-admin.nav-item href="/admin/users" icon="users" :active="request()->is('admin/users')">
                    All Users
                </x-admin.nav-item>
                <x-admin.nav-item href="/admin/verifications" icon="check-circle" :active="request()->is('admin/verifications')">
                    Verifications
                </x-admin.nav-item>
            </div>
        </div>
        
        {{-- Properties Section --}}
        <div>
            <p class="px-4 mb-2 text-xs font-semibold text-[#C4ECEA] uppercase tracking-wider">Properties</p>
            <div class="space-y-1">
                <x-admin.nav-item href="/admin/listings" icon="home" :active="request()->is('admin/listings')">
                    Listings
                </x-admin.nav-item>
                <x-admin.nav-item href="/admin/amenities" icon="star" :active="request()->is('admin/amenities')">
                    Amenities
                </x-admin.nav-item>
            </div>
        </div>
        
        {{-- Operations Section --}}
        <div>
            <p class="px-4 mb-2 text-xs font-semibold text-[#C4ECEA] uppercase tracking-wider">Operations</p>
            <div class="space-y-1">
                <x-admin.nav-item href="/admin/viewings" icon="calendar" :active="request()->is('admin/viewings')">
                    Viewing Requests
                </x-admin.nav-item>
                <x-admin.nav-item href="/admin/messages" icon="message-square" :active="request()->is('admin/messages')">
                    Messages
                </x-admin.nav-item>
            </div>
        </div>
        
        {{-- System Section --}}
        <div>
            <p class="px-4 mb-2 text-xs font-semibold text-[#C4ECEA] uppercase tracking-wider">System</p>
            <div class="space-y-1">
                <x-admin.nav-item href="/admin/analytics" icon="bar-chart" :active="request()->is('admin/analytics')">
                    Analytics
                </x-admin.nav-item>
                <x-admin.nav-item href="/admin/settings" icon="settings" :active="request()->is('admin/settings')">
                    Settings
                </x-admin.nav-item>
            </div>
        </div>
    </nav>
    
    {{-- User Profile Footer --}}
    <div class="border-t border-[#1FA6A0]/20 px-4 py-4">
        <div class="flex items-center gap-3 mb-3">
            <x-admin.avatar :user="$currentUser" size="md" />
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">{{ $currentUser->name }}</p>
                <p class="text-xs text-[#C4ECEA] truncate">{{ $currentUser->email }}</p>
            </div>
        </div>
        <form method="POST" action="/admin/logout">
            @csrf
            <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-[#C4ECEA] hover:bg-[#0B3A38] rounded-lg transition-colors duration-200">
                <x-icon name="logout" class="w-4 h-4" />
                Logout
            </button>
        </form>
    </div>
</aside>
