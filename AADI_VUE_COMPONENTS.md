# 🎨 Aadi Vue Component Library

Complete Vue 3 component library with Aadi branding for your Laravel + Inertia.js application.

## 🚀 Quick Start

### Installation Complete ✅

All components are ready to use! The Aadi brand colors have been added to your Tailwind configuration.

### Access the Demo

1. **Build assets:**
   ```bash
   npm run dev
   ```

2. **Visit the demo page:**
   ```
   http://127.0.0.1:8000/admin/demo
   ```

---

## 📦 Component Library

### UI Components

#### **Button** (`@/Components/Aadi/UI/Button.vue`)
Versatile button component with Aadi branding.

```vue
<script setup>
import Button from '@/Components/Aadi/UI/Button.vue';
</script>

<template>
  <!-- Primary (Teal) -->
  <Button variant="primary">Save Changes</Button>
  
  <!-- Orange Accent -->
  <Button variant="orange">View Listings</Button>
  
  <!-- Other Variants -->
  <Button variant="secondary">Cancel</Button>
  <Button variant="success">Approve</Button>
  <Button variant="danger">Delete</Button>
  <Button variant="outline">Learn More</Button>
  <Button variant="ghost">Skip</Button>
  
  <!-- Sizes -->
  <Button size="xs">Extra Small</Button>
  <Button size="sm">Small</Button>
  <Button size="md">Medium</Button>
  <Button size="lg">Large</Button>
  <Button size="xl">Extra Large</Button>
  
  <!-- Loading State -->
  <Button :loading="true">Processing...</Button>
  
  <!-- As Link (Inertia) -->
  <Button href="/dashboard">Dashboard</Button>
</template>
```

**Props:**
- `variant`: `'primary' | 'orange' | 'secondary' | 'success' | 'danger' | 'warning' | 'outline' | 'ghost'` (default: `'primary'`)
- `size`: `'xs' | 'sm' | 'md' | 'lg' | 'xl'` (default: `'md'`)
- `href`: Optional Inertia link destination
- `disabled`: Boolean
- `loading`: Boolean
- `type`: `'button' | 'submit' | 'reset'` (default: `'button'`)

---

#### **Badge** (`@/Components/Aadi/UI/Badge.vue`)
Status badges with color variants.

```vue
<script setup>
import Badge from '@/Components/Aadi/UI/Badge.vue';
</script>

<template>
  <Badge variant="primary">Tenant</Badge>
  <Badge variant="orange">Landlord</Badge>
  <Badge variant="success">Active</Badge>
  <Badge variant="danger">Suspended</Badge>
  
  <!-- With Dot Indicator -->
  <Badge variant="primary" :dot="true">Online</Badge>
  
  <!-- Rounded -->
  <Badge variant="orange" rounded>New</Badge>
  
  <!-- Sizes -->
  <Badge size="sm">Small</Badge>
  <Badge size="md">Medium</Badge>
  <Badge size="lg">Large</Badge>
</template>
```

**Props:**
- `variant`: `'primary' | 'orange' | 'success' | 'danger' | 'warning' | 'info' | 'secondary' | 'dark'` (default: `'primary'`)
- `size`: `'sm' | 'md' | 'lg'` (default: `'md'`)
- `dot`: Boolean - Show dot indicator
- `rounded`: Boolean - Fully rounded edges

---

#### **Card** (`@/Components/Aadi/UI/Card.vue`)
Container component with optional header and footer.

```vue
<script setup>
import Card from '@/Components/Aadi/UI/Card.vue';
import Button from '@/Components/Aadi/UI/Button.vue';
</script>

<template>
  <Card title="User Settings">
    <template #actions>
      <Button variant="primary" size="sm">Save</Button>
    </template>
    
    <!-- Card Content -->
    <p>Your content here...</p>
    
    <template #footer>
      <p class="text-sm text-slate-500">Last updated: Today</p>
    </template>
  </Card>
</template>
```

**Props:**
- `title`: Optional string
- `padding`: Boolean (default: `true`)
- `border`: Boolean (default: `true`)

**Slots:**
- `default`: Main content
- `actions`: Header action buttons
- `footer`: Footer content

---

#### **StatCard** (`@/Components/Aadi/UI/StatCard.vue`)
Statistics display cards.

```vue
<script setup>
import StatCard from '@/Components/Aadi/UI/StatCard.vue';
</script>

<template>
  <div class="grid grid-cols-4 gap-6">
    <StatCard
      label="Total Users"
      :value="1247"
      color="teal"
      icon="users"
      :trend="{ value: 12.5, label: 'from last month' }"
    />
    
    <StatCard
      label="Active Listings"
      :value="89"
      color="orange"
      icon="home"
    />
    
    <StatCard
      label="Revenue"
      value="$12,456"
      color="emerald"
      :trend="{ value: -3.1, label: 'from last month' }"
    />
  </div>
</template>
```

**Props:**
- `label`: string (required)
- `value`: string | number (required)
- `subtitle`: Optional string
- `icon`: Optional string
- `color`: `'teal' | 'orange' | 'blue' | 'emerald' | 'purple' | 'rose'` (default: `'teal'`)
- `trend`: Optional `{ value: number, label: string }`

---

#### **Avatar** (`@/Components/Aadi/UI/Avatar.vue`)
User avatar with initials fallback.

```vue
<script setup>
import Avatar from '@/Components/Aadi/UI/Avatar.vue';
</script>

<template>
  <!-- With Image -->
  <Avatar src="/images/avatar.jpg" alt="John Doe" />
  
  <!-- With Initials (Teal Gradient) -->
  <Avatar name="John Doe" size="md" />
  
  <!-- Sizes -->
  <Avatar name="Jane" size="xs" />
  <Avatar name="Jane" size="sm" />
  <Avatar name="Jane" size="md" />
  <Avatar name="Jane" size="lg" />
  <Avatar name="Jane" size="xl" />
  <Avatar name="Jane" size="2xl" />
</template>
```

**Props:**
- `src`: Optional image URL
- `alt`: string (default: `'Avatar'`)
- `name`: Optional string for initials
- `size`: `'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl'` (default: `'md'`)

---

#### **Alert** (`@/Components/Aadi/UI/Alert.vue`)
Alert messages with different types.

```vue
<script setup>
import { ref } from 'vue';
import Alert from '@/Components/Aadi/UI/Alert.vue';

const showAlert = ref(true);
</script>

<template>
  <Alert v-if="showAlert" type="info" title="New Feature" :dismissible="true" @dismiss="showAlert = false">
    Check out the new dashboard analytics!
  </Alert>
  
  <Alert type="success" title="Success">
    Your profile has been updated.
  </Alert>
  
  <Alert type="warning">
    Your subscription expires in 3 days.
  </Alert>
  
  <Alert type="error">
    An error occurred. Please try again.
  </Alert>
</template>
```

**Props:**
- `type`: `'success' | 'error' | 'warning' | 'info'` (default: `'info'`)
- `title`: Optional string
- `dismissible`: Boolean

**Events:**
- `dismiss`: Emitted when close button is clicked

---

### Form Components

#### **Input** (`@/Components/Aadi/Forms/Input.vue`)
Text input with teal focus ring.

```vue
<script setup>
import { ref } from 'vue';
import Input from '@/Components/Aadi/Forms/Input.vue';

const name = ref('');
const email = ref('');
</script>

<template>
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
    error="Invalid email address"
  />
</template>
```

**Props:**
- `modelValue`: string | number (required)
- `label`: Optional string
- `type`: string (default: `'text'`)
- `placeholder`: Optional string
- `required`: Boolean
- `disabled`: Boolean
- `error`: Optional error message
- `help`: Optional help text
- `id`: Optional string

---

#### **Textarea** (`@/Components/Aadi/Forms/Textarea.vue`)
Multi-line text input.

```vue
<script setup>
import { ref } from 'vue';
import Textarea from '@/Components/Aadi/Forms/Textarea.vue';

const message = ref('');
</script>

<template>
  <Textarea
    v-model="message"
    label="Message"
    placeholder="Type your message..."
    :rows="6"
    help="Maximum 500 characters"
  />
</template>
```

**Props:** Same as Input, plus:
- `rows`: number (default: `4`)

---

#### **Select** (`@/Components/Aadi/Forms/Select.vue`)
Dropdown select input.

```vue
<script setup>
import { ref } from 'vue';
import Select from '@/Components/Aadi/Forms/Select.vue';

const role = ref('');

const roleOptions = [
  { label: 'Tenant', value: 'tenant' },
  { label: 'Landlord', value: 'landlord' },
  { label: 'Admin', value: 'admin' },
];
</script>

<template>
  <Select
    v-model="role"
    label="User Role"
    :options="roleOptions"
    placeholder="Select a role"
    required
  />
</template>
```

**Props:**
- `modelValue`: string | number (required)
- `options`: `Array<{ label: string, value: string | number, disabled?: boolean }>` (required)
- Other props same as Input

---

### Table Components

#### **Table, Th, Td** 

```vue
<script setup>
import Table from '@/Components/Aadi/Table/Table.vue';
import Th from '@/Components/Aadi/Table/Th.vue';
import Td from '@/Components/Aadi/Table/Td.vue';
import Badge from '@/Components/Aadi/UI/Badge.vue';
import Avatar from '@/Components/Aadi/UI/Avatar.vue';

const users = [
  { id: 1, name: 'Alice', email: 'alice@example.com', role: 'Tenant' },
  { id: 2, name: 'Bob', email: 'bob@example.com', role: 'Landlord' },
];
</script>

<template>
  <Table>
    <thead>
      <tr>
        <Th>Name</Th>
        <Th>Email</Th>
        <Th>Role</Th>
        <Th align="right">Actions</Th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-200">
      <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50">
        <Td>
          <div class="flex items-center gap-3">
            <Avatar :name="user.name" size="sm" />
            <span>{{ user.name }}</span>
          </div>
        </Td>
        <Td>{{ user.email }}</Td>
        <Td>
          <Badge :variant="user.role === 'Landlord' ? 'orange' : 'primary'">
            {{ user.role }}
          </Badge>
        </Td>
        <Td align="right">
          <Button variant="ghost" size="sm">Edit</Button>
        </Td>
      </tr>
    </tbody>
  </Table>
</template>
```

---

### Layout Components

#### **AdminSidebar** (`@/Components/Aadi/Layout/AdminSidebar.vue`)
Admin layout with dark teal sidebar.

```vue
<script setup>
import { router } from '@inertiajs/vue3';
import AdminSidebar from '@/Components/Aadi/Layout/AdminSidebar.vue';

const navSections = [
  {
    title: 'Overview',
    items: [
      { label: 'Dashboard', href: '/admin/dashboard', active: true },
      { label: 'Analytics', href: '/admin/analytics', badge: 'New' },
    ],
  },
  {
    title: 'Management',
    items: [
      { label: 'Users', href: '/admin/users', badge: 24 },
      { label: 'Properties', href: '/admin/properties' },
    ],
  },
];

const handleLogout = () => {
  router.post('/logout');
};
</script>

<template>
  <AdminSidebar
    :sections="navSections"
    :user="{ name: 'John Doe', email: 'john@aadi.test' }"
    @logout="handleLogout"
  >
    <template #header>
      <h1 class="text-2xl font-bold">Dashboard</h1>
    </template>
    
    <!-- Page Content -->
    <div class="space-y-6">
      <!-- Your content -->
    </div>
  </AdminSidebar>
</template>
```

**Props:**
- `sections`: Array of navigation sections
- `user`: Optional `{ name: string, email: string, avatar?: string }`

**Events:**
- `logout`: Emitted when logout is clicked

---

## 🎨 Aadi Brand Colors

The following colors are configured in Tailwind:

### Primary Teal (`aadi-teal`)
```vue
<div class="bg-aadi-teal text-white">Primary Teal</div>
<div class="bg-aadi-teal-50">Lightest</div>
<div class="bg-aadi-teal-800">Darkest (Sidebar)</div>
```

**Usage:**
- Primary buttons and actions
- Active navigation states
- Links and interactive elements
- Avatar gradient background

### Accent Orange (`aadi-orange`)
```vue
<div class="bg-aadi-orange text-white">Accent Orange</div>
<div class="bg-aadi-orange-100">Light Orange</div>
```

**Usage:**
- Secondary actions
- Landlord badges
- Accent elements

---

## 📐 Complete Color Palette

| Shade | Teal | Orange |
|-------|------|--------|
| 50    | `#E3F6F4` | `#FEF5EB` |
| 100   | `#C4ECEA` | `#FCE6CE` |
| 200   | `#8DD9D4` | `#F9CE9C` |
| 300   | `#56C6BF` | `#F7B66B` |
| 400   | `#1FA6A0` ⭐ | `#F59E42` ⭐ |
| 500   | `#1A8F8A` | `#D9822B` |
| 600   | `#157873` | `#BD6615` |
| 700   | `#0F5F5C` | `#8F4D10` |
| 800   | `#0B3A38` | `#61340B` |
| 900   | `#062827` | `#331B06` |

---

## 🚀 Usage Examples

### Complete Form Example

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Card from '@/Components/Aadi/UI/Card.vue';
import Button from '@/Components/Aadi/UI/Button.vue';
import Input from '@/Components/Aadi/Forms/Input.vue';
import Select from '@/Components/Aadi/Forms/Select.vue';
import Alert from '@/Components/Aadi/UI/Alert.vue';

const form = useForm({
  name: '',
  email: '',
  role: '',
});

const roleOptions = [
  { label: 'Tenant', value: 'tenant' },
  { label: 'Landlord', value: 'landlord' },
];

const submit = () => {
  form.post('/users');
};
</script>

<template>
  <Card title="Create User">
    <Alert v-if="form.recentlySuccessful" type="success">
      User created successfully!
    </Alert>
    
    <form @submit.prevent="submit" class="space-y-6 mt-4">
      <Input
        v-model="form.name"
        label="Full Name"
        :error="form.errors.name"
        required
      />
      
      <Input
        v-model="form.email"
        type="email"
        label="Email"
        :error="form.errors.email"
        required
      />
      
      <Select
        v-model="form.role"
        label="Role"
        :options="roleOptions"
        :error="form.errors.role"
        required
      />
    </form>
    
    <template #footer>
      <div class="flex justify-end gap-3">
        <Button variant="ghost" type="button">Cancel</Button>
        <Button variant="primary" :loading="form.processing" @click="submit">
          Create User
        </Button>
      </div>
    </template>
  </Card>
</template>
```

### Dashboard with Stats

```vue
<script setup>
import { Head } from '@inertiajs/vue3';
import AdminSidebar from '@/Components/Aadi/Layout/AdminSidebar.vue';
import StatCard from '@/Components/Aadi/UI/StatCard.vue';
import Card from '@/Components/Aadi/UI/Card.vue';

defineProps<{
  stats: {
    users: number;
    listings: number;
    bookings: number;
    revenue: string;
  };
}>();
</script>

<template>
  <Head title="Dashboard" />
  
  <AdminSidebar :sections="navSections" :user="$page.props.auth.user">
    <template #header>
      <h1 class="text-3xl font-bold">Dashboard</h1>
    </template>
    
    <div class="grid grid-cols-4 gap-6">
      <StatCard label="Users" :value="stats.users" color="teal" />
      <StatCard label="Listings" :value="stats.listings" color="orange" />
      <StatCard label="Bookings" :value="stats.bookings" color="blue" />
      <StatCard label="Revenue" :value="stats.revenue" color="emerald" />
    </div>
  </AdminSidebar>
</template>
```

---

## 🔧 Development

### Build Assets
```bash
npm run dev      # Development with hot reload
npm run build    # Production build
```

### TypeScript Support
All components are fully typed with TypeScript interfaces.

---

## 📚 Component Files

```
resources/js/Components/Aadi/
├── UI/
│   ├── Button.vue           ✅ Primary action component
│   ├── Badge.vue            ✅ Status badges
│   ├── Card.vue             ✅ Container component
│   ├── StatCard.vue         ✅ Statistics display
│   ├── Avatar.vue           ✅ User avatars
│   └── Alert.vue            ✅ Alert messages
├── Forms/
│   ├── Input.vue            ✅ Text inputs
│   ├── Textarea.vue         ✅ Multi-line text
│   └── Select.vue           ✅ Dropdown select
├── Table/
│   ├── Table.vue            ✅ Table wrapper
│   ├── Th.vue               ✅ Table header
│   └── Td.vue               ✅ Table cell
├── Layout/
│   └── AdminSidebar.vue     ✅ Admin layout with sidebar
└── index.ts                 ✅ Component exports
```

---

## 🎉 You're All Set!

Visit **http://127.0.0.1:8000/admin/demo** (after running `npm run dev`) to see all components in action!

**Built with ❤️ for Aadi Rental Marketplace**
