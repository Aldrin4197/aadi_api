# Aadi Admin Dashboard - Component Library Documentation

This document provides examples and usage guidelines for all reusable Blade components in the admin dashboard.

---

## 📦 Component Index

### Layout Components
- [Sidebar](#sidebar)
- [Navbar](#navbar)
- [Card](#card)

### UI Components
- [Icon](#icon)
- [Avatar](#avatar)
- [Badge](#badge)
- [Button](#button)
- [Alert](#alert)
- [Empty State](#empty-state)
- [Stat Card](#stat-card)

### Form Components
- [Input](#input)
- [Textarea](#textarea)
- [Select](#select)

### Interactive Components
- [Modal](#modal)
- [Dropdown](#dropdown)
- [Table](#table)

---

## Layout Components

### Sidebar
**Component:** `<x-admin.sidebar />`

The sidebar is automatically included in the admin layout. It displays navigation, user info, and logout button.

```blade
<x-admin.sidebar />
<!-- Or pass custom user -->
<x-admin.sidebar :user="$admin" />
```

---

### Navbar
**Component:** `<x-admin.navbar>`

Top navigation bar with page title and optional actions.

```blade
<x-admin.navbar title="Dashboard" />

<!-- With actions -->
<x-admin.navbar>
    <x-slot:title>Users</x-slot:title>
    <x-slot:actions>
        <x-admin.button variant="primary" icon="plus">
            Add User
        </x-admin.button>
    </x-slot:actions>
</x-admin.navbar>
```

---

### Card
**Component:** `<x-admin.card>`

Container with optional title and action buttons.

```blade
<!-- Simple card -->
<x-admin.card>
    Your content here
</x-admin.card>

<!-- Card with title -->
<x-admin.card title="Recent Users">
    Content here
</x-admin.card>

<!-- Card with title and actions -->
<x-admin.card title="Settings" :padding="false">
    <x-slot:actions>
        <x-admin.button size="sm" variant="outline">
            Edit
        </x-admin.button>
    </x-slot:actions>
    
    Content without padding
</x-admin.card>
```

**Props:**
- `title` - Card header title
- `actions` - Slot for header action buttons
- `padding` - Boolean (default: true)

---

## UI Components

### Icon
**Component:** `<x-icon>`

Centralized SVG icon system with 40+ icons.

```blade
<x-icon name="users" class="w-5 h-5" />
<x-icon name="check-circle" class="w-4 h-4 text-green-500" />
```

**Available Icons:**
- Navigation: `grid`, `home`, `users`, `user`, `calendar`, `star`
- Actions: `check`, `check-circle`, `x`, `x-circle`, `plus`, `plus-circle`, `edit`, `trash`, `eye`, `search`, `filter`, `download`, `upload`
- UI: `menu`, `dots-vertical`, `dots-horizontal`, `chevron-down`, `chevron-up`, `chevron-left`, `chevron-right`
- Status: `alert-circle`, `info`
- Business: `credit-card`, `message-square`, `flag`, `bar-chart`, `settings`, `logout`

---

### Avatar
**Component:** `<x-admin.avatar>`

User avatar with automatic initials fallback.

```blade
<!-- With user object -->
<x-admin.avatar :user="$user" />

<!-- With custom name -->
<x-admin.avatar name="John Doe" />

<!-- With image -->
<x-admin.avatar src="/path/to/image.jpg" name="John Doe" />

<!-- Different sizes -->
<x-admin.avatar :user="$user" size="xs" />
<x-admin.avatar :user="$user" size="sm" />
<x-admin.avatar :user="$user" size="md" />
<x-admin.avatar :user="$user" size="lg" />
<x-admin.avatar :user="$user" size="xl" />
<x-admin.avatar :user="$user" size="2xl" />
```

**Props:**
- `user` - User object (optional)
- `name` - Display name (optional)
- `src` - Image URL (optional)
- `size` - xs|sm|md|lg|xl|2xl (default: md)

---

### Badge
**Component:** `<x-admin.badge>`

Status badges with color variants.

```blade
<!-- Simple badge -->
<x-admin.badge>Default</x-admin.badge>

<!-- Variants -->
<x-admin.badge variant="primary">Primary</x-admin.badge>
<x-admin.badge variant="success">Active</x-admin.badge>
<x-admin.badge variant="danger">Rejected</x-admin.badge>
<x-admin.badge variant="warning">Pending</x-admin.badge>
<x-admin.badge variant="info">Info</x-admin.badge>

<!-- Sizes -->
<x-admin.badge size="sm">Small</x-admin.badge>
<x-admin.badge size="md">Medium</x-admin.badge>
<x-admin.badge size="lg">Large</x-admin.badge>

<!-- With dot indicator -->
<x-admin.badge variant="success" :dot="true">Online</x-admin.badge>
```

**Props:**
- `variant` - default|primary|success|danger|warning|info|purple
- `size` - sm|md|lg
- `dot` - Boolean (shows colored dot)

---

### Button
**Component:** `<x-admin.button>`

Styled buttons with variants and icons.

```blade
<!-- Variants -->
<x-admin.button variant="primary">Primary</x-admin.button>
<x-admin.button variant="secondary">Secondary</x-admin.button>
<x-admin.button variant="success">Success</x-admin.button>
<x-admin.button variant="danger">Delete</x-admin.button>
<x-admin.button variant="outline">Cancel</x-admin.button>
<x-admin.button variant="ghost">Ghost</x-admin.button>

<!-- Sizes -->
<x-admin.button size="xs">Extra Small</x-admin.button>
<x-admin.button size="sm">Small</x-admin.button>
<x-admin.button size="md">Medium</x-admin.button>
<x-admin.button size="lg">Large</x-admin.button>
<x-admin.button size="xl">Extra Large</x-admin.button>

<!-- With icon -->
<x-admin.button variant="primary" icon="plus">
    Add User
</x-admin.button>

<!-- As link -->
<x-admin.button href="/admin/users" variant="primary">
    View Users
</x-admin.button>

<!-- Disabled -->
<x-admin.button disabled>Disabled</x-admin.button>
```

**Props:**
- `variant` - primary|secondary|success|danger|warning|outline|ghost
- `size` - xs|sm|md|lg|xl
- `icon` - Icon name
- `href` - URL (renders as link)

---

### Alert
**Component:** `<x-admin.alert>`

Alert messages with icon and color variants.

```blade
<x-admin.alert type="success">
    Your changes have been saved successfully!
</x-admin.alert>

<x-admin.alert type="error">
    An error occurred. Please try again.
</x-admin.alert>

<x-admin.alert type="warning">
    This action cannot be undone.
</x-admin.alert>

<x-admin.alert type="info">
    Did you know? You can use keyboard shortcuts.
</x-admin.alert>
```

**Props:**
- `type` - success|error|warning|info

---

### Empty State
**Component:** `<x-admin.empty-state>`

Empty state placeholder with icon and optional action.

```blade
<!-- Simple -->
<x-admin.empty-state
    icon="users"
    title="No users found"
    description="Get started by adding your first user."
/>

<!-- With action -->
<x-admin.empty-state
    icon="inbox"
    title="No listings yet"
    description="Create your first property listing to get started.">
    <x-slot:action>
        <x-admin.button variant="primary" icon="plus">
            Create Listing
        </x-admin.button>
    </x-slot:action>
</x-admin.empty-state>
```

**Props:**
- `icon` - Icon name (default: inbox)
- `title` - Heading text
- `description` - Description text
- `action` - Slot for action button

---

### Stat Card
**Component:** `<x-admin.stat-card>`

Statistics display card with icon.

```blade
<x-admin.stat-card
    label="Total Users"
    :value="150"
    icon="users"
    color="blue"
/>

<x-admin.stat-card
    label="Active Listings"
    :value="$stats['listings']"
    icon="home"
    color="indigo"
    :sub="$stats['pending'] . ' pending approval'"
/>
```

**Props:**
- `label` - Card label
- `value` - Numeric value (will be formatted)
- `icon` - Icon name
- `color` - blue|indigo|violet|amber|emerald|red
- `sub` - Subtitle text (optional)

---

## Form Components

### Input
**Component:** `<x-admin.input>`

Text input field with label, validation, and help text.

```blade
<x-admin.input
    label="Email Address"
    name="email"
    type="email"
    placeholder="user@example.com"
    :required="true"
/>

<!-- With error -->
<x-admin.input
    label="Username"
    name="username"
    :value="old('username')"
    :error="$errors->first('username')"
/>

<!-- With help text -->
<x-admin.input
    label="API Key"
    name="api_key"
    help="This key will be used to authenticate API requests."
/>
```

**Props:**
- `label` - Field label
- `name` - Input name
- `type` - Input type (default: text)
- `value` - Input value
- `placeholder` - Placeholder text
- `required` - Boolean
- `error` - Error message
- `help` - Help text

---

### Textarea
**Component:** `<x-admin.textarea>`

Multi-line text input.

```blade
<x-admin.textarea
    label="Description"
    name="description"
    placeholder="Enter description..."
    :rows="5"
    :required="true"
/>

<!-- With value -->
<x-admin.textarea
    label="Notes"
    name="notes"
    :value="$listing->notes"
/>
```

**Props:**
- Same as Input, plus:
- `rows` - Number of rows (default: 4)

---

### Select
**Component:** `<x-admin.select>`

Dropdown select field.

```blade
<!-- With options array -->
<x-admin.select
    label="Status"
    name="status"
    :options="['active' => 'Active', 'inactive' => 'Inactive']"
    :value="$user->status"
/>

<!-- With slot options -->
<x-admin.select
    label="Category"
    name="category"
    placeholder="Choose a category">
    <option value="bedspace">Bedspace</option>
    <option value="apartment">Apartment</option>
    <option value="house">House</option>
</x-admin.select>
```

**Props:**
- Same as Input, plus:
- `options` - Array of options
- `placeholder` - Placeholder option text

---

## Interactive Components

### Modal
**Component:** `<x-admin.modal>`

Modal dialog with Alpine.js.

```blade
<x-admin.modal
    id="confirmModal"
    title="Confirm Action"
    size="md">
    
    <p class="text-sm text-slate-600">
        Are you sure you want to proceed?
    </p>
    
    <x-slot:footer>
        <x-admin.button variant="outline" @click="open = false">
            Cancel
        </x-admin.button>
        <x-admin.button variant="danger">
            Confirm
        </x-admin.button>
    </x-slot:footer>
</x-admin.modal>

<!-- Trigger button -->
<x-admin.button @click="$dispatch('open-modal', 'confirmModal')">
    Open Modal
</x-admin.button>
```

**Props:**
- `id` - Modal identifier
- `title` - Modal title
- `size` - sm|md|lg|xl|full
- `show` - Boolean (initial state)
- `footer` - Slot for footer actions

---

### Dropdown
**Component:** `<x-admin.dropdown>`

Dropdown menu with Alpine.js.

```blade
<x-admin.dropdown align="right">
    <x-slot:trigger>
        <x-admin.button variant="outline" icon="dots-vertical" />
    </x-slot:trigger>
    
    <x-admin.dropdown.item icon="eye" href="/view">
        View Details
    </x-admin.dropdown.item>
    <x-admin.dropdown.item icon="edit" href="/edit">
        Edit
    </x-admin.dropdown.item>
    
    <x-admin.dropdown.divider />
    
    <x-admin.dropdown.item icon="trash" variant="danger">
        Delete
    </x-admin.dropdown.item>
</x-admin.dropdown>
```

**Props:**
- `align` - left|right (default: right)
- `trigger` - Slot for trigger button

**Dropdown Item Props:**
- `icon` - Icon name
- `href` - Link URL
- `variant` - default|danger

---

### Table
**Component:** `<x-admin.table>`

Data table with styling.

```blade
<x-admin.card :padding="false">
    <x-admin.table>
        <thead class="bg-slate-50">
            <tr>
                <x-admin.table.th>Name</x-admin.table.th>
                <x-admin.table.th>Email</x-admin.table.th>
                <x-admin.table.th>Status</x-admin.table.th>
                <x-admin.table.th align="right">Actions</x-admin.table.th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @forelse($users as $user)
                <x-admin.table.row>
                    <x-admin.table.td>
                        <div class="flex items-center gap-3">
                            <x-admin.avatar :user="$user" size="sm" />
                            <span class="font-medium">{{ $user->name }}</span>
                        </div>
                    </x-admin.table.td>
                    <x-admin.table.td>{{ $user->email }}</x-admin.table.td>
                    <x-admin.table.td>
                        <x-admin.badge variant="success">Active</x-admin.badge>
                    </x-admin.table.td>
                    <x-admin.table.td align="right">
                        <x-admin.button size="sm" variant="outline">
                            View
                        </x-admin.button>
                    </x-admin.table.td>
                </x-admin.table.row>
            @empty
                <tr>
                    <td colspan="4">
                        <x-admin.empty-state
                            icon="users"
                            title="No users found"
                        />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.table>
</x-admin.card>
```

**Table Props:**
- `striped` - Boolean
- `hoverable` - Boolean (default: true)

**Table.th Props:**
- `sortable` - Boolean
- `align` - left|center|right

**Table.td Props:**
- `align` - left|center|right

---

## Complete Page Example

```blade
@extends('admin.layouts.app')

@section('title', 'Users Management')

@section('content')

{{-- Stats Grid --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
    <x-admin.stat-card
        label="Total Users"
        :value="$stats['total']"
        icon="users"
        color="blue"
    />
    <x-admin.stat-card
        label="Active"
        :value="$stats['active']"
        icon="check-circle"
        color="emerald"
    />
</div>

{{-- Users Table --}}
<x-admin.card>
    <x-slot:actions>
        <x-admin.button variant="primary" icon="plus" href="/admin/users/create">
            Add User
        </x-admin.button>
    </x-slot:actions>
    
    <x-admin.table>
        <!-- Table content here -->
    </x-admin.table>
</x-admin.card>

@endsection
```

---

## Notes

- All components use Tailwind CSS for styling
- Interactive components require Alpine.js (included in layout)
- Icons are SVG-based and fully customizable
- Components follow Laravel Blade component conventions
- All form components support Laravel validation errors
