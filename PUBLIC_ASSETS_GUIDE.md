# 📸 Public Assets Guide - Aadi

## 📁 Directory Structure

All public assets (images, logos, etc.) go in the `public` directory:

```
public/
├── images/
│   ├── logos/          ← Brand logos (Aadi logo, partner logos)
│   ├── avatars/        ← User profile pictures
│   ├── properties/     ← Property listing photos
│   ├── banners/        ← Hero images, promotional banners
│   └── icons/          ← App icons, favicons, PWA icons
├── build/              ← Vite compiled assets (auto-generated)
├── favicon.ico         ← Browser tab icon
├── robots.txt          ← Search engine directives
└── index.php           ← Laravel entry point
```

---

## 🎨 Where to Put What

### **Brand Logos** → `public/images/logos/`
```
public/images/logos/
├── aadi-logo.svg           ← Main Aadi logo
├── aadi-logo-white.svg     ← White version for dark backgrounds
├── aadi-logo-icon.svg      ← Square icon version
└── aadi-logo-horizontal.svg ← Horizontal layout
```

### **User Avatars** → `public/images/avatars/`
```
public/images/avatars/
├── default-avatar.png      ← Default fallback avatar
└── [user-uploaded files]   ← User profile pictures
```

### **Property Photos** → `public/images/properties/`
```
public/images/properties/
├── property-1-main.jpg
├── property-1-thumb.jpg
├── property-2-main.jpg
└── ...
```

### **Banners** → `public/images/banners/`
```
public/images/banners/
├── hero-home.jpg           ← Homepage hero image
├── signup-banner.jpg       ← Sign up page banner
└── promo-summer-2026.jpg   ← Seasonal promotions
```

### **Icons** → `public/images/icons/`
```
public/images/icons/
├── icon-192.png            ← PWA icon (small)
├── icon-512.png            ← PWA icon (large)
└── apple-touch-icon.png    ← iOS home screen icon
```

---

## 🔗 How to Reference Images

### **In Vue Components (Inertia.js)**

#### **Method 1: Direct Path (Recommended)**
```vue
<script setup lang="ts">
// No imports needed for public assets
</script>

<template>
  <!-- Brand Logo -->
  <img src="/images/logos/aadi-logo.svg" alt="Aadi Logo" class="h-10" />
  
  <!-- User Avatar -->
  <img :src="`/images/avatars/${user.avatar}`" alt="User Avatar" />
  
  <!-- Property Photo -->
  <img :src="`/images/properties/property-${property.id}.jpg`" :alt="property.name" />
  
  <!-- Banner -->
  <img src="/images/banners/hero-home.jpg" alt="Hero Banner" class="w-full" />
</template>
```

#### **Method 2: Using Props/Data**
```vue
<script setup lang="ts">
interface Props {
  logoSrc?: string;
}

const props = withDefaults(defineProps<Props>(), {
  logoSrc: '/images/logos/aadi-logo.svg'
});
</script>

<template>
  <img :src="props.logoSrc" alt="Logo" />
</template>
```

#### **Method 3: Background Images with Tailwind**
```vue
<template>
  <div 
    class="h-64 bg-cover bg-center"
    :style="{ backgroundImage: 'url(/images/banners/hero-home.jpg)' }"
  >
    <!-- Content -->
  </div>
</template>
```

---

### **In Blade Templates**

```blade
{{-- Brand Logo --}}
<img src="{{ asset('images/logos/aadi-logo.svg') }}" alt="Aadi Logo" class="h-10">

{{-- User Avatar --}}
<img src="{{ asset('images/avatars/' . $user->avatar) }}" alt="{{ $user->name }}">

{{-- Property Photo --}}
<img src="{{ asset('images/properties/property-' . $property->id . '.jpg') }}" alt="{{ $property->name }}">

{{-- With Storage (if using Laravel Storage) --}}
<img src="{{ Storage::url($user->avatar) }}" alt="Avatar">
```

---

### **In CSS/Tailwind**

```css
/* In your CSS file */
.logo-background {
    background-image: url('/images/logos/aadi-logo.svg');
}

.hero-banner {
    background-image: url('/images/banners/hero-home.jpg');
}
```

---

## 🖼️ Example: Aadi Logo Component

### **Vue Component**
```vue
<!-- resources/js/Components/Aadi/UI/Logo.vue -->
<script setup lang="ts">
interface Props {
  variant?: 'default' | 'white' | 'icon';
  size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  size: 'md',
});

const logoSrc = computed(() => {
  const logos = {
    default: '/images/logos/aadi-logo.svg',
    white: '/images/logos/aadi-logo-white.svg',
    icon: '/images/logos/aadi-logo-icon.svg',
  };
  return logos[props.variant];
});

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'h-6',
    md: 'h-10',
    lg: 'h-16',
  };
  return sizes[props.size];
});
</script>

<template>
  <img :src="logoSrc" alt="Aadi Logo" :class="sizeClasses" />
</template>
```

**Usage:**
```vue
<Logo variant="default" size="md" />
<Logo variant="white" size="lg" />
<Logo variant="icon" size="sm" />
```

---

## 📤 File Upload Best Practices

### **For User Uploads (Avatars, Property Photos)**

Use Laravel Storage instead of public directory:

```php
// In your controller
public function uploadAvatar(Request $request)
{
    $request->validate([
        'avatar' => 'required|image|max:2048', // 2MB max
    ]);

    // Store in storage/app/public/avatars/
    $path = $request->file('avatar')->store('avatars', 'public');
    
    // Save path to database
    auth()->user()->update(['avatar' => $path]);
    
    return back()->with('success', 'Avatar uploaded!');
}
```

**Access uploaded files:**
```vue
<!-- Vue -->
<img :src="`/storage/${user.avatar}`" alt="Avatar" />
```

```blade
{{-- Blade --}}
<img src="{{ Storage::url($user->avatar) }}" alt="Avatar">
```

**Don't forget to create the symbolic link:**
```bash
php artisan storage:link
```

---

## 🎯 Recommended File Formats

| Asset Type | Format | Notes |
|------------|--------|-------|
| **Logos** | SVG | Scalable, small file size |
| **Photos** | JPG/WebP | Optimized for web |
| **Screenshots** | PNG | When transparency needed |
| **Icons** | SVG/PNG | SVG preferred, PNG for PWA |
| **Banners** | JPG/WebP | Large images, compressed |

---

## ⚡ Performance Tips

### **1. Image Optimization**
```bash
# Install image optimization (optional)
npm install --save-dev vite-plugin-image-optimizer

# Use WebP format for better compression
# Convert: .jpg → .webp (70% smaller)
```

### **2. Lazy Loading**
```vue
<!-- Vue: Lazy load images -->
<img 
  src="/images/properties/property-1.jpg" 
  loading="lazy"
  alt="Property"
/>
```

### **3. Responsive Images**
```vue
<img 
  srcset="
    /images/properties/property-1-small.jpg 400w,
    /images/properties/property-1-medium.jpg 800w,
    /images/properties/property-1-large.jpg 1200w
  "
  sizes="(max-width: 600px) 400px, (max-width: 900px) 800px, 1200px"
  src="/images/properties/property-1-medium.jpg"
  alt="Property"
/>
```

---

## 🔒 Security Notes

### **DO:**
✅ Store user uploads in `storage/app/public/` (not directly in `public/`)
✅ Validate file types and sizes
✅ Sanitize filenames
✅ Use Laravel Storage facade for uploads
✅ Create symbolic link: `php artisan storage:link`

### **DON'T:**
❌ Allow direct file uploads to `public/` directory
❌ Trust user-provided filenames
❌ Store sensitive files in `public/`
❌ Skip file validation

---

## 📋 Quick Reference

### **Aadi Logo Placements:**

1. **Sidebar** (current): Gradient "A" letter
2. **Navbar**: Add full logo
   ```vue
   <img src="/images/logos/aadi-logo.svg" alt="Aadi" class="h-10" />
   ```
3. **Login Page**: Large logo
   ```vue
   <img src="/images/logos/aadi-logo.svg" alt="Aadi" class="h-20 mx-auto" />
   ```
4. **Email Footer**: Include logo URL
   ```blade
   <img src="{{ config('app.url') }}/images/logos/aadi-logo.svg" alt="Aadi" />
   ```

---

## 🚀 Next Steps

1. **Add your Aadi logo:**
   ```bash
   # Place your logo files in:
   public/images/logos/aadi-logo.svg
   public/images/logos/aadi-logo-white.svg
   ```

2. **Update AdminSidebar to use real logo:**
   ```vue
   <!-- Replace gradient "A" with: -->
   <img src="/images/logos/aadi-logo-icon.svg" alt="Aadi" class="h-10 w-10" />
   ```

3. **Update favicon:**
   ```bash
   # Replace default Laravel favicon
   cp your-aadi-favicon.ico public/favicon.ico
   ```

---

## 📞 Common Paths Summary

```
/images/logos/aadi-logo.svg          → Main logo
/images/avatars/default-avatar.png   → Default avatar
/images/properties/property-1.jpg    → Property photo
/images/banners/hero-home.jpg        → Hero banner
/images/icons/icon-512.png           → PWA icon
/storage/avatars/user-123.jpg        → User uploads
```

---

**Now you can add your brand assets! 🎨**

Place your Aadi logo in `public/images/logos/` and reference it with `/images/logos/aadi-logo.svg` in your components!
