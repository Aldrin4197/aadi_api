# Admin Component Library - Quick Reference
## ✨ Aadi Rental Marketplace Branding

All components use the official **Aadi brand colors**:
- **Primary Teal**: `#1FA6A0` - Main brand color
- **Dark Teal**: `#0B3A38` - Sidebar & dark elements
- **Light Teal**: `#C4ECEA`, `#E3F6F4` - Badges & accents
- **Accent Orange**: `#F59E42` - Secondary actions
- **Deep Orange**: `#D9822B` - Orange hover states
- **Font**: Quicksand (via Bunny.net CDN)

## 🎨 All Available Components

### Layout & Structure
```blade
<x-admin.sidebar />
<x-admin.navbar title="Page Title" />
<x-admin.card title="Card Title" />
```

### UI Elements
```blade
<x-icon name="users" class="w-5 h-5" />
<x-admin.avatar :user="$user" size="md" />
<x-admin.badge variant="primary">Tenant</x-admin.badge>
<x-admin.button variant="primary" icon="plus">Add New</x-admin.button>
<x-admin.alert type="info">Info message (Aadi teal)</x-admin.alert>
<x-admin.stat-card label="Total Users" :value="150" icon="users" color="teal" />
<x-admin.empty-state icon="inbox" title="No data" />
```

### Form Inputs
```blade
<x-admin.input label="Name" name="name" required />
<x-admin.textarea label="Description" name="description" rows="5" />
<x-admin.select label="Status" name="status" :options="[]" />
```

### Interactive
```blade
<x-admin.modal title="Confirm">Content</x-admin.modal>
<x-admin.dropdown>
    <x-slot:trigger>Button</x-slot:trigger>
    <x-admin.dropdown.item>Item</x-admin.dropdown.item>
</x-admin.dropdown>
```

### Tables
```blade
<x-admin.table>
    <thead>
        <tr>
            <x-admin.table.th>Header</x-admin.table.th>
        </tr>
    </thead>
    <tbody>
        <x-admin.table.row>
            <x-admin.table.td>Data</x-admin.table.td>
        </x-admin.table.row>
    </tbody>
</x-admin.table>
```

## 📁 File Structure

```
resources/views/components/
├── icon.blade.php                          # 40+ SVG icons
├── admin/
│   ├── layouts/
│   │   └── app.blade.php                   # Main admin layout
│   ├── sidebar.blade.php                   # Navigation sidebar
│   ├── navbar.blade.php                    # Top navigation bar
│   ├── nav-item.blade.php                  # Sidebar nav link
│   ├── avatar.blade.php                    # User avatar
│   ├── badge.blade.php                     # Status badge
│   ├── button.blade.php                    # Button component
│   ├── card.blade.php                      # Container card
│   ├── alert.blade.php                     # Alert message
│   ├── stat-card.blade.php                 # Statistics card
│   ├── empty-state.blade.php               # Empty state placeholder
│   ├── modal.blade.php                     # Modal dialog
│   ├── input.blade.php                     # Text input
│   ├── textarea.blade.php                  # Multi-line input
│   ├── select.blade.php                    # Dropdown select
│   ├── table.blade.php                     # Table wrapper
│   ├── table/
│   │   ├── th.blade.php                    # Table header
│   │   ├── td.blade.php                    # Table cell
│   │   └── row.blade.php                   # Table row
│   └── dropdown/
│       ├── dropdown.blade.php              # Dropdown menu
│       ├── item.blade.php                  # Dropdown item
│       └── divider.blade.php               # Dropdown divider
```

## 🎯 Component Variants

### Badge Variants
- `default` (gray)
- `primary` (Aadi light teal - `#C4ECEA`)
- `success` (green)
- `danger` (red)
- `warning` (yellow)
- `info` (blue)
- `purple` (purple)
- `orange` (Aadi accent - `#FCE6CE`)

### Button Variants
- `primary` (Aadi teal - `#1FA6A0`) ⭐ Brand default
- `secondary` (gray)
- `success` (green)
- `danger` (red)
- `warning` (yellow)
- `outline` (white with border)
- `ghost` (transparent)
- `orange` (Aadi accent - `#F59E42`) ⭐ Brand accent

### Sizes
Most components support: `xs`, `sm`, `md`, `lg`, `xl`, `2xl`

### Colors (for stat-card)
- `teal` (Aadi primary) ⭐ Brand default
- `orange` (Aadi accent) ⭐ Brand accent
- `blue`
- `indigo`
- `violet`
- `amber`
- `emerald`
- `red` with custom Aadi config)
- **Alpine.js** - Interactivity for modal, dropdown (loaded via CDN)
- **Quicksand Font** - Loaded from Bunny.net CDN
- **Aadi Brand Colors** - Injected via Tailwind config in layout

## 🎨 Design System

### Aadi Colors (configured in layout)
```javascript
colors: {
    brand: {
        DEFAULT: '#1FA6A0',   // Primary teal
        dark: '#0B3A38',      // Sidebar dark
        light: '#C4ECEA',     // Light teal
        lighter: '#E3F6F4',   // Very light teal
    },
    accent: {
        DEFAULT: '#F59E42',   // Orange
        deep: '#D9822B',      // Deep orange
        light: '#FCE6CE',     // Light orange
    }
}
```

### Usage Examples
- Primary actions: `variant="primary"` (teal)
- Secondary actions: `variant="orange"` (accent)
- Info alerts: `type="info"` (teal)
- Primary badges: `variant="primary"` (light teal)
- Sidebar: Dark teal gradient background
- Avatar: Teal to dark teal gradient
## 🔧 Dependencies

- **Tailwind CSS** - Styling (loaded via CDN)
- **Alpine.js** - Interactivity for modal, dropdown (loaded via CDN)

## 📚 Full Documentation

See [COMPONENTS.md](./COMPONENTS.md) for complete documentation with examples.
