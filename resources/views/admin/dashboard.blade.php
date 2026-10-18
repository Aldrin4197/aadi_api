@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')

{{-- Welcome Message --}}
<div class="mb-8">
    <h2 class="text-3xl font-bold text-slate-900">Welcome back, {{ auth()->user()->name ?? 'Admin' }}! 👋</h2>
    <p class="mt-2 text-slate-600">Here's what's happening with your platform today.</p>
</div>

{{-- Statistics Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
    <x-admin.stat-card
        label="Total Users"
        :value="150"
        icon="users"
        color="teal"
        sub="12 new this week"
    />
    
    <x-admin.stat-card
        label="Active Listings"
        :value="89"
        icon="home"
        color="orange"
        sub="5 pending approval"
    />
    
    <x-admin.stat-card
        label="Viewing Requests"
        :value="23"
        icon="calendar"
        color="blue"
        sub="8 this week"
    />
    
    <x-admin.stat-card
        label="Revenue"
        value="₱124,500"
        icon="credit-card"
        color="emerald"
        sub="+18% from last month"
    />
</div>

{{-- Recent Activity Cards --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    {{-- Recent Users --}}
    <x-admin.card title="Recent Users">
        <x-slot:actions>
            <x-admin.button variant="outline" size="sm" href="/admin/users">
                View All
            </x-admin.button>
        </x-slot:actions>
        
        <x-admin.table :padding="false">
            <thead class="bg-slate-50">
                <tr>
                    <x-admin.table.th>User</x-admin.table.th>
                    <x-admin.table.th>Status</x-admin.table.th>
                    <x-admin.table.th align="right">Actions</x-admin.table.th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @for($i = 1; $i <= 5; $i++)
                    <x-admin.table.row>
                        <x-admin.table.td>
                            <div class="flex items-center gap-3">
                                <x-admin.avatar name="User {{ $i }}" size="sm" />
                                <div>
                                    <p class="font-medium">User {{ $i }}</p>
                                    <p class="text-sm text-slate-500">user{{ $i }}@example.com</p>
                                </div>
                            </div>
                        </x-admin.table.td>
                        <x-admin.table.td>
                            <x-admin.badge variant="{{ $i % 2 == 0 ? 'primary' : 'success' }}">
                                {{ $i % 2 == 0 ? 'Tenant' : 'Landlord' }}
                            </x-admin.badge>
                        </x-admin.table.td>
                        <x-admin.table.td align="right">
                            <x-admin.dropdown align="right">
                                <x-slot:trigger>
                                    <x-admin.button variant="ghost" icon="dots-vertical" size="sm" />
                                </x-slot:trigger>
                                <x-admin.dropdown.item icon="eye" href="#">View</x-admin.dropdown.item>
                                <x-admin.dropdown.item icon="edit" href="#">Edit</x-admin.dropdown.item>
                                <x-admin.dropdown.divider />
                                <x-admin.dropdown.item icon="trash" variant="danger">Delete</x-admin.dropdown.item>
                            </x-admin.dropdown>
                        </x-admin.table.td>
                    </x-admin.table.row>
                @endfor
            </tbody>
        </x-admin.table>
    </x-admin.card>
    
    {{-- Recent Listings --}}
    <x-admin.card title="Recent Listings">
        <x-slot:actions>
            <x-admin.button variant="outline" size="sm" href="/admin/listings">
                View All
            </x-admin.button>
        </x-slot:actions>
        
        <x-admin.table :padding="false">
            <thead class="bg-slate-50">
                <tr>
                    <x-admin.table.th>Property</x-admin.table.th>
                    <x-admin.table.th>Status</x-admin.table.th>
                    <x-admin.table.th align="right">Price</x-admin.table.th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @for($i = 1; $i <= 5; $i++)
                    <x-admin.table.row>
                        <x-admin.table.td>
                            <div>
                                <p class="font-medium">Property {{ $i }}</p>
                                <p class="text-sm text-slate-500">Manila, Philippines</p>
                            </div>
                        </x-admin.table.td>
                        <x-admin.table.td>
                            <x-admin.badge variant="{{ ['success', 'warning', 'primary'][rand(0, 2)] }}">
                                {{ ['Active', 'Pending', 'Draft'][rand(0, 2)] }}
                            </x-admin.badge>
                        </x-admin.table.td>
                        <x-admin.table.td align="right">
                            <span class="font-semibold text-[#1FA6A0]">₱{{ number_format(rand(5000, 50000)) }}</span>
                        </x-admin.table.td>
                    </x-admin.table.row>
                @endfor
            </tbody>
        </x-admin.table>
    </x-admin.card>
</div>

{{-- Quick Actions --}}
<x-admin.card title="Quick Actions">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <x-admin.button variant="primary" icon="users" href="/admin/users" class="justify-center">
            Manage Users
        </x-admin.button>
        <x-admin.button variant="orange" icon="home" href="/admin/listings" class="justify-center">
            View Listings
        </x-admin.button>
        <x-admin.button variant="secondary" icon="calendar" href="/admin/viewings" class="justify-center">
            Viewing Requests
        </x-admin.button>
        <x-admin.button variant="outline" icon="bar-chart" href="/admin/analytics" class="justify-center">
            Analytics
        </x-admin.button>
    </div>
</x-admin.card>

@endsection
