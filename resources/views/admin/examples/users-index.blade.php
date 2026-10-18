@extends('admin.layouts.app')

@section('title', 'Users Management')

@section('content')

{{-- Page Header with Actions --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-900">Users Management</h2>
        <p class="mt-1 text-sm text-slate-600">Manage all platform users, tenants, and landlords</p>
    </div>
    <x-admin.button variant="primary" icon="plus" @click="$dispatch('open-modal', 'createUser')">
        Add New User
    </x-admin.button>
</div>

{{-- Statistics --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
    <x-admin.stat-card
        label="Total Users"
        :value="245"
        icon="users"
        color="teal"
    />
    <x-admin.stat-card
        label="Active Tenants"
        :value="189"
        icon="user"
        color="blue"
    />
    <x-admin.stat-card
        label="Landlords"
        :value="56"
        icon="home"
        color="orange"
    />
    <x-admin.stat-card
        label="Pending Approval"
        :value="12"
        icon="alert-circle"
        color="amber"
    />
</div>

{{-- Filters & Search --}}
<x-admin.card class="mb-6">
    <form method="GET" action="/admin/users" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-admin.input
            label="Search"
            name="search"
            placeholder="Name or email..."
            icon="search"
        />
        
        <x-admin.select
            label="User Type"
            name="type"
            :options="['all' => 'All Users', 'tenant' => 'Tenants', 'landlord' => 'Landlords']"
        />
        
        <x-admin.select
            label="Status"
            name="status"
            :options="['all' => 'All Status', 'active' => 'Active', 'pending' => 'Pending', 'suspended' => 'Suspended']"
        />
        
        <div class="flex items-end gap-2">
            <x-admin.button type="submit" variant="primary" icon="filter" class="flex-1">
                Filter
            </x-admin.button>
            <x-admin.button type="button" variant="outline" href="/admin/users">
                Reset
            </x-admin.button>
        </div>
    </form>
</x-admin.card>

{{-- Users Table --}}
<x-admin.card :padding="false">
    <x-admin.table>
        <thead class="bg-slate-50">
            <tr>
                <x-admin.table.th>User</x-admin.table.th>
                <x-admin.table.th>Type</x-admin.table.th>
                <x-admin.table.th>Trust Score</x-admin.table.th>
                <x-admin.table.th>Status</x-admin.table.th>
                <x-admin.table.th>Joined</x-admin.table.th>
                <x-admin.table.th align="right">Actions</x-admin.table.th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            {{-- Sample Data Row 1 --}}
            <x-admin.table.row>
                <x-admin.table.td>
                    <div class="flex items-center gap-3">
                        <x-admin.avatar name="Maria Santos" size="md" />
                        <div>
                            <p class="font-medium text-slate-900">Maria Santos</p>
                            <p class="text-sm text-slate-500">maria@example.com</p>
                            <p class="text-xs text-slate-400">+63 912 345 6789</p>
                        </div>
                    </div>
                </x-admin.table.td>
                <x-admin.table.td>
                    <x-admin.badge variant="primary">Tenant</x-admin.badge>
                </x-admin.table.td>
                <x-admin.table.td>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-slate-200 rounded-full h-2 max-w-[100px]">
                            <div class="bg-[#1FA6A0] h-2 rounded-full" style="width: 85%"></div>
                        </div>
                        <span class="text-sm font-medium">85</span>
                    </div>
                </x-admin.table.td>
                <x-admin.table.td>
                    <x-admin.badge variant="success" :dot="true">Active</x-admin.badge>
                </x-admin.table.td>
                <x-admin.table.td>
                    <span class="text-sm text-slate-600">Jan 15, 2026</span>
                </x-admin.table.td>
                <x-admin.table.td align="right">
                    <x-admin.dropdown align="right">
                        <x-slot:trigger>
                            <x-admin.button variant="ghost" icon="dots-vertical" size="sm" />
                        </x-slot:trigger>
                        <x-admin.dropdown.item icon="eye" href="#">View Profile</x-admin.dropdown.item>
                        <x-admin.dropdown.item icon="edit" href="#">Edit Details</x-admin.dropdown.item>
                        <x-admin.dropdown.item icon="message-square" href="#">Send Message</x-admin.dropdown.item>
                        <x-admin.dropdown.divider />
                        <x-admin.dropdown.item icon="flag" href="#">Suspend Account</x-admin.dropdown.item>
                        <x-admin.dropdown.item icon="trash" variant="danger">Delete User</x-admin.dropdown.item>
                    </x-admin.dropdown>
                </x-admin.table.td>
            </x-admin.table.row>

            {{-- Sample Data Row 2 --}}
            <x-admin.table.row>
                <x-admin.table.td>
                    <div class="flex items-center gap-3">
                        <x-admin.avatar name="Juan Dela Cruz" size="md" />
                        <div>
                            <p class="font-medium text-slate-900">Juan Dela Cruz</p>
                            <p class="text-sm text-slate-500">juan@example.com</p>
                            <p class="text-xs text-slate-400">+63 917 234 5678</p>
                        </div>
                    </div>
                </x-admin.table.td>
                <x-admin.table.td>
                    <x-admin.badge variant="orange">Landlord</x-admin.badge>
                </x-admin.table.td>
                <x-admin.table.td>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-slate-200 rounded-full h-2 max-w-[100px]">
                            <div class="bg-[#F59E42] h-2 rounded-full" style="width: 92%"></div>
                        </div>
                        <span class="text-sm font-medium">92</span>
                    </div>
                </x-admin.table.td>
                <x-admin.table.td>
                    <x-admin.badge variant="success" :dot="true">Active</x-admin.badge>
                </x-admin.table.td>
                <x-admin.table.td>
                    <span class="text-sm text-slate-600">Dec 22, 2025</span>
                </x-admin.table.td>
                <x-admin.table.td align="right">
                    <x-admin.dropdown align="right">
                        <x-slot:trigger>
                            <x-admin.button variant="ghost" icon="dots-vertical" size="sm" />
                        </x-slot:trigger>
                        <x-admin.dropdown.item icon="eye" href="#">View Profile</x-admin.dropdown.item>
                        <x-admin.dropdown.item icon="home" href="#">View Listings</x-admin.dropdown.item>
                        <x-admin.dropdown.item icon="edit" href="#">Edit Details</x-admin.dropdown.item>
                        <x-admin.dropdown.divider />
                        <x-admin.dropdown.item icon="flag" href="#">Suspend Account</x-admin.dropdown.item>
                        <x-admin.dropdown.item icon="trash" variant="danger">Delete User</x-admin.dropdown.item>
                    </x-admin.dropdown>
                </x-admin.table.td>
            </x-admin.table.row>

            {{-- Sample Data Row 3 --}}
            <x-admin.table.row>
                <x-admin.table.td>
                    <div class="flex items-center gap-3">
                        <x-admin.avatar name="Pedro Reyes" size="md" />
                        <div>
                            <p class="font-medium text-slate-900">Pedro Reyes</p>
                            <p class="text-sm text-slate-500">pedro@example.com</p>
                            <p class="text-xs text-slate-400">+63 905 876 5432</p>
                        </div>
                    </div>
                </x-admin.table.td>
                <x-admin.table.td>
                    <x-admin.badge variant="orange">Landlord</x-admin.badge>
                </x-admin.table.td>
                <x-admin.table.td>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-slate-200 rounded-full h-2 max-w-[100px]">
                            <div class="bg-amber-500 h-2 rounded-full" style="width: 65%"></div>
                        </div>
                        <span class="text-sm font-medium">65</span>
                    </div>
                </x-admin.table.td>
                <x-admin.table.td>
                    <x-admin.badge variant="warning">Pending</x-admin.badge>
                </x-admin.table.td>
                <x-admin.table.td>
                    <span class="text-sm text-slate-600">Aug 10, 2026</span>
                </x-admin.table.td>
                <x-admin.table.td align="right">
                    <div class="flex items-center justify-end gap-2">
                        <x-admin.button variant="primary" size="xs">Approve</x-admin.button>
                        <x-admin.button variant="danger" size="xs">Reject</x-admin.button>
                    </div>
                </x-admin.table.td>
            </x-admin.table.row>
        </tbody>
    </x-admin.table>
    
    {{-- Pagination --}}
    <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-between">
        <p class="text-sm text-slate-600">Showing <span class="font-medium">1-20</span> of <span class="font-medium">245</span> users</p>
        <div class="flex gap-2">
            <x-admin.button variant="outline" size="sm" disabled>Previous</x-admin.button>
            <x-admin.button variant="outline" size="sm">Next</x-admin.button>
        </div>
    </div>
</x-admin.card>

{{-- Create User Modal --}}
<x-admin.modal id="createUser" title="Add New User" size="lg">
    <form method="POST" action="/admin/users" class="space-y-4">
        @csrf
        
        <div class="grid grid-cols-2 gap-4">
            <x-admin.input
                label="Full Name"
                name="name"
                placeholder="Enter full name"
                :required="true"
            />
            
            <x-admin.input
                label="Email Address"
                name="email"
                type="email"
                placeholder="user@example.com"
                :required="true"
            />
        </div>
        
        <div class="grid grid-cols-2 gap-4">
            <x-admin.input
                label="Phone Number"
                name="phone"
                placeholder="+63 XXX XXX XXXX"
            />
            
            <x-admin.select
                label="User Type"
                name="type"
                :options="['tenant' => 'Tenant', 'landlord' => 'Landlord']"
                :required="true"
            />
        </div>
        
        <x-admin.input
            label="Password"
            name="password"
            type="password"
            placeholder="Minimum 8 characters"
            :required="true"
            help="Password must be at least 8 characters long"
        />
        
        <x-admin.textarea
            label="Notes (Optional)"
            name="notes"
            placeholder="Add any admin notes about this user..."
            :rows="3"
        />
    </form>
    
    <x-slot:footer>
        <x-admin.button variant="outline" @click="open = false">
            Cancel
        </x-admin.button>
        <x-admin.button variant="primary" icon="check">
            Create User
        </x-admin.button>
    </x-slot:footer>
</x-admin.modal>

@endsection
