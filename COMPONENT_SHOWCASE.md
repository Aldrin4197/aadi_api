# 🎨 Aadi Component Visual Showcase

Quick visual reference for all components with Aadi branding.

---

## Color Palette

```
🟢 Primary Teal:     #1FA6A0  ████████
🔷 Dark Teal:        #0B3A38  ████████
💠 Light Teal:       #C4ECEA  ████████
💎 Lighter Teal:     #E3F6F4  ████████
🟠 Accent Orange:    #F59E42  ████████
🔶 Deep Orange:      #D9822B  ████████
🟡 Light Orange:     #FCE6CE  ████████
```

---

## Components at a Glance

### Buttons
```blade
Primary (Teal):    [+  Add User]  ← Main actions
Orange:            [🏠  Listings]  ← Secondary actions  
Success:           [✓  Approve]   ← Positive actions
Danger:            [✗  Delete]    ← Destructive actions
Outline:           [ Cancel ]     ← Neutral actions
```

### Badges
```blade
Primary (Light Teal):  [Tenant]     ← User types
Orange (Light):        [Landlord]   ← Owner types
Success:               [• Active]   ← Status
Warning:               [Pending]    ← Waiting
Danger:                [Rejected]   ← Negative
```

### Stat Cards
```blade
┌──────────────────────────┐
│ Total Users          [👥]│  ← Teal icon background
│ 150                      │
│ 12 new this week         │
└──────────────────────────┘

┌──────────────────────────┐
│ Active Listings      [🏠]│  ← Orange icon background
│ 89                       │
│ 5 pending approval       │
└──────────────────────────┘
```

### Sidebar
```
╔════════════════════╗
║  [A]  Aadi         ║  ← Dark teal gradient
║      Admin         ║     with logo
║                    ║
║  ▣  Overview       ║  ← Light teal text
║                    ║
║  USERS             ║  ← Section headers
║  👥  All Users     ║
║  ✓  Verifications  ║  ← Active state: teal bg
║                    ║
║  PROPERTIES        ║
║  🏠  Listings      ║
║  ⭐  Amenities     ║
║                    ║
║────────────────────║
║  [AV] Admin User   ║  ← Teal gradient avatar
║  admin@aadi.test   ║
║  [⎋ Logout]        ║
╚════════════════════╝
```

### Alerts
```blade
[ℹ] This is an info alert (Light teal background)
[✓] This is a success alert (Green background)
[⚠] This is a warning alert (Yellow background)
[✗] This is an error alert (Red background)
```

### Form Inputs
```blade
Email Address *
┌────────────────────────────┐
│ user@example.com           │  ← Teal focus ring
└────────────────────────────┘
```

### Table
```
┌────────────────────────────────────────────┐
│ User              │ Type     │ Status      │
├────────────────────────────────────────────┤
│ [AV] Maria Santos │ [Tenant] │ [• Active]  │  ← Hover: light gray
│ [AV] Juan Cruz    │ [Landlord] │ [• Active] │
│ [AV] Pedro Reyes  │ [Landlord] │ [Pending]  │
└────────────────────────────────────────────┘
```

### Dropdown Menu
```blade
[⋮]  ← Click to open
    ┌─────────────────┐
    │ 👁 View Profile │
    │ ✎  Edit         │
    │ 💬 Message      │
    ├─────────────────┤
    │ 🗑 Delete       │  ← Red text
    └─────────────────┘
```

### Modal
```blade
         ┌──────────────────────┐
         │ Add New User      [X]│
         ├──────────────────────┤
         │                      │
         │ [Form inputs here]   │
         │                      │
         ├──────────────────────┤
         │  [Cancel]  [✓ Create]│  ← Teal primary
         └──────────────────────┘
```

---

## Usage Patterns

### Dashboard Layout
```blade
@extends('admin.layouts.app')

@section('content')
    {{-- Stat cards in grid --}}
    <div class="grid grid-cols-4 gap-6">
        <x-admin.stat-card color="teal" ... />
        <x-admin.stat-card color="orange" ... />
    </div>
    
    {{-- Data tables in cards --}}
    <x-admin.card title="Recent Users">
        <x-admin.table>...</x-admin.table>
    </x-admin.card>
@endsection
```

### CRUD Page Pattern
```blade
{{-- Header with action --}}
<div class="flex justify-between">
    <h2>Users Management</h2>
    <x-admin.button variant="primary">Add</x-admin.button>
</div>

{{-- Stats --}}
<div class="grid grid-cols-4 gap-6">
    <x-admin.stat-card ... />
</div>

{{-- Filters --}}
<x-admin.card>
    <form>
        <x-admin.input ... />
        <x-admin.select ... />
        <x-admin.button>Filter</x-admin.button>
    </form>
</x-admin.card>

{{-- Table --}}
<x-admin.card>
    <x-admin.table>...</x-admin.table>
</x-admin.card>

{{-- Modal --}}
<x-admin.modal>...</x-admin.modal>
```

---

## Brand Rules

✅ **DO:**
- Use `variant="primary"` (teal) for main actions
- Use `variant="orange"` for secondary/accent actions
- Use `color="teal"` for primary stat cards
- Use `color="orange"` for accent stat cards
- Use `type="info"` (teal) for informational alerts
- Keep teal as the dominant brand color

❌ **DON'T:**
- Don't mix teal and blue (use teal instead of blue)
- Don't use generic colors when branded options exist
- Don't override the teal focus rings
- Don't change the sidebar gradient

---

## Component Combinations

### User Row
```blade
<x-admin.avatar :user="$user" /> 
+ 
<x-admin.badge variant="primary">Tenant</x-admin.badge>
+
<x-admin.dropdown>...</x-admin.dropdown>
```

### Action Buttons
```blade
<x-admin.button variant="primary">Main Action</x-admin.button>
<x-admin.button variant="orange">Secondary</x-admin.button>
<x-admin.button variant="outline">Cancel</x-admin.button>
```

### Empty State with Action
```blade
<x-admin.empty-state 
    title="No users found"
    description="Get started...">
    <x-slot:action>
        <x-admin.button variant="primary">Add User</x-admin.button>
    </x-slot:action>
</x-admin.empty-state>
```

---

## Responsive Behavior

### Desktop (>1024px)
- Sidebar: Fixed, always visible
- Grid: 4 columns for stats
- Tables: Full width with all columns

### Tablet (768-1023px)
- Sidebar: Collapsible
- Grid: 2 columns for stats
- Tables: Horizontal scroll

### Mobile (<768px)
- Sidebar: Hidden, hamburger menu
- Grid: 1 column stacked
- Tables: Horizontal scroll, key columns only

---

## Quick Copy-Paste Snippets

### Stat Grid
```blade
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
    <x-admin.stat-card label="Total Users" :value="150" icon="users" color="teal" />
    <x-admin.stat-card label="Listings" :value="89" icon="home" color="orange" />
    <x-admin.stat-card label="Viewings" :value="23" icon="calendar" color="blue" />
    <x-admin.stat-card label="Revenue" value="₱124,500" icon="credit-card" color="emerald" />
</div>
```

### Form Row
```blade
<div class="grid grid-cols-2 gap-4">
    <x-admin.input label="Name" name="name" :required="true" />
    <x-admin.input label="Email" name="email" type="email" :required="true" />
</div>
```

### Table with Dropdown
```blade
<x-admin.table.row>
    <x-admin.table.td>{{ $item->name }}</x-admin.table.td>
    <x-admin.table.td>
        <x-admin.badge variant="success">Active</x-admin.badge>
    </x-admin.table.td>
    <x-admin.table.td align="right">
        <x-admin.dropdown align="right">
            <x-slot:trigger>
                <x-admin.button variant="ghost" icon="dots-vertical" size="sm" />
            </x-slot:trigger>
            <x-admin.dropdown.item icon="eye">View</x-admin.dropdown.item>
            <x-admin.dropdown.item icon="edit">Edit</x-admin.dropdown.item>
            <x-admin.dropdown.divider />
            <x-admin.dropdown.item icon="trash" variant="danger">Delete</x-admin.dropdown.item>
        </x-admin.dropdown>
    </x-admin.table.td>
</x-admin.table.row>
```

---

**All components follow Aadi's teal and orange brand identity! 🎨✨**
