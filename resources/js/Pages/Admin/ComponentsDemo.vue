<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminSidebar from '@/Components/Aadi/Layout/AdminSidebar.vue';
import Card from '@/Components/Aadi/UI/Card.vue';
import Button from '@/Components/Aadi/UI/Button.vue';
import Badge from '@/Components/Aadi/UI/Badge.vue';
import StatCard from '@/Components/Aadi/UI/StatCard.vue';
import Avatar from '@/Components/Aadi/UI/Avatar.vue';
import Alert from '@/Components/Aadi/UI/Alert.vue';
import Input from '@/Components/Aadi/Forms/Input.vue';
import Textarea from '@/Components/Aadi/Forms/Textarea.vue';
import Select from '@/Components/Aadi/Forms/Select.vue';
import Table from '@/Components/Aadi/Table/Table.vue';
import Th from '@/Components/Aadi/Table/Th.vue';
import Td from '@/Components/Aadi/Table/Td.vue';

interface Props {
    auth?: {
        user?: {
            name: string;
            email: string;
            avatar?: string;
        };
    };
}

const props = defineProps<Props>();

// Sample data
const name = ref('John Doe');
const email = ref('john@example.com');
const message = ref('');
const role = ref('');

const roleOptions = [
    { label: 'Tenant', value: 'tenant' },
    { label: 'Landlord', value: 'landlord' },
    { label: 'Admin', value: 'admin' },
];

const navSections = [
    {
        title: 'Overview',
        items: [
            { label: 'Dashboard', href: route('admin.dashboard'), active: true },
            { label: 'Analytics', href: '#', badge: 'New' },
        ],
    },
    {
        title: 'Management',
        items: [
            { label: 'Users', href: '#', badge: 24 },
            { label: 'Properties', href: '#' },
            { label: 'Bookings', href: '#' },
        ],
    },
    {
        title: 'System',
        items: [
            { label: 'Settings', href: '#' },
            { label: 'Reports', href: '#' },
        ],
    },
];

const users = [
    { id: 1, name: 'Alice Johnson', email: 'alice@example.com', role: 'Tenant', status: 'active' },
    { id: 2, name: 'Bob Smith', email: 'bob@example.com', role: 'Landlord', status: 'active' },
    { id: 3, name: 'Charlie Brown', email: 'charlie@example.com', role: 'Tenant', status: 'inactive' },
];

const handleLogout = () => {
    // Handle logout
    console.log('Logout clicked');
};
</script>

<template>
    <Head title="Aadi Components Demo" />

    <AdminSidebar
        :sections="navSections"
        :user="props.auth?.user || { name: 'Demo User', email: 'demo@aadi.test' }"
        @logout="handleLogout"
    >
        <template #header>
            <h1 class="text-2xl font-bold text-slate-900">Aadi Component Library</h1>
        </template>

        <div class="max-w-7xl mx-auto space-y-8">
            <!-- Welcome Section -->
            <div>
                <h2 class="text-3xl font-bold text-slate-900 mb-2">Welcome to Aadi! 👋</h2>
                <p class="text-slate-600">Explore the complete component library with Aadi branding.</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <StatCard
                    label="Total Users"
                    :value="1247"
                    icon="users"
                    color="teal"
                    :trend="{ value: 12.5, label: 'from last month' }"
                />
                <StatCard
                    label="Active Listings"
                    :value="89"
                    icon="home"
                    color="orange"
                    :trend="{ value: 8.2, label: 'from last week' }"
                />
                <StatCard
                    label="Total Bookings"
                    :value="456"
                    icon="calendar"
                    color="blue"
                />
                <StatCard
                    label="Revenue"
                    value="$12,456"
                    icon="dollar"
                    color="emerald"
                    :trend="{ value: -3.1, label: 'from last month' }"
                />
            </div>

            <!-- Alerts -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <Alert type="info" title="New Feature Available">
                    Check out the new dashboard analytics feature!
                </Alert>
                <Alert type="success" title="Successfully Updated">
                    Your profile has been updated successfully.
                </Alert>
            </div>

            <!-- Buttons & Badges -->
            <Card title="Buttons & Badges">
                <div class="space-y-6">
                    <!-- Buttons -->
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 mb-3">Buttons</h4>
                        <div class="flex flex-wrap gap-3">
                            <Button variant="primary">Primary (Teal)</Button>
                            <Button variant="orange">Orange Accent</Button>
                            <Button variant="secondary">Secondary</Button>
                            <Button variant="success">Success</Button>
                            <Button variant="danger">Danger</Button>
                            <Button variant="outline">Outline</Button>
                            <Button variant="ghost">Ghost</Button>
                            <Button variant="primary" :loading="true">Loading</Button>
                        </div>
                    </div>

                    <!-- Button Sizes -->
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 mb-3">Button Sizes</h4>
                        <div class="flex flex-wrap items-center gap-3">
                            <Button size="xs">Extra Small</Button>
                            <Button size="sm">Small</Button>
                            <Button size="md">Medium</Button>
                            <Button size="lg">Large</Button>
                            <Button size="xl">Extra Large</Button>
                        </div>
                    </div>

                    <!-- Badges -->
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 mb-3">Badges</h4>
                        <div class="flex flex-wrap gap-3">
                            <Badge variant="primary">Tenant</Badge>
                            <Badge variant="orange">Landlord</Badge>
                            <Badge variant="success">Active</Badge>
                            <Badge variant="danger">Suspended</Badge>
                            <Badge variant="warning">Pending</Badge>
                            <Badge variant="info">New</Badge>
                            <Badge variant="primary" :dot="true">With Dot</Badge>
                            <Badge variant="orange" rounded>Rounded</Badge>
                        </div>
                    </div>

                    <!-- Avatars -->
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 mb-3">Avatars</h4>
                        <div class="flex flex-wrap items-center gap-3">
                            <Avatar name="John Doe" size="xs" />
                            <Avatar name="Jane Smith" size="sm" />
                            <Avatar name="Bob Wilson" size="md" />
                            <Avatar name="Alice Cooper" size="lg" />
                            <Avatar name="Charlie Brown" size="xl" />
                            <Avatar name="Diana Prince" size="2xl" />
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Forms -->
            <Card title="Form Components">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <Input
                        v-model="name"
                        label="Full Name"
                        placeholder="Enter your name"
                        required
                        help="This will be displayed on your profile"
                    />
                    
                    <Input
                        v-model="email"
                        type="email"
                        label="Email Address"
                        placeholder="you@example.com"
                        required
                    />
                    
                    <Select
                        v-model="role"
                        label="User Role"
                        :options="roleOptions"
                        placeholder="Select a role"
                        required
                    />
                    
                    <div class="md:col-span-2">
                        <Textarea
                            v-model="message"
                            label="Message"
                            placeholder="Type your message here..."
                            :rows="4"
                            help="Maximum 500 characters"
                        />
                    </div>
                </div>

                <template #footer>
                    <div class="flex justify-end gap-3">
                        <Button variant="ghost">Cancel</Button>
                        <Button variant="primary">Save Changes</Button>
                    </div>
                </template>
            </Card>

            <!-- Table -->
            <Card title="Data Table">
                <template #actions>
                    <Button variant="primary" size="sm">Add User</Button>
                </template>

                <Table>
                    <thead>
                        <tr>
                            <Th>Name</Th>
                            <Th>Email</Th>
                            <Th>Role</Th>
                            <Th>Status</Th>
                            <Th align="right">Actions</Th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50">
                            <Td>
                                <div class="flex items-center gap-3">
                                    <Avatar :name="user.name" size="sm" />
                                    <span class="font-medium">{{ user.name }}</span>
                                </div>
                            </Td>
                            <Td>{{ user.email }}</Td>
                            <Td>
                                <Badge :variant="user.role === 'Landlord' ? 'orange' : 'primary'">
                                    {{ user.role }}
                                </Badge>
                            </Td>
                            <Td>
                                <Badge
                                    :variant="user.status === 'active' ? 'success' : 'secondary'"
                                    :dot="true"
                                >
                                    {{ user.status }}
                                </Badge>
                            </Td>
                            <Td align="right">
                                <div class="flex justify-end gap-2">
                                    <Button variant="ghost" size="sm">Edit</Button>
                                    <Button variant="ghost" size="sm">Delete</Button>
                                </div>
                            </Td>
                        </tr>
                    </tbody>
                </Table>
            </Card>
        </div>
    </AdminSidebar>
</template>
