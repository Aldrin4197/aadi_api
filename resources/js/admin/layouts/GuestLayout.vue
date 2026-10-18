<script setup>
// Guest layout for login/register pages
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-aadi-teal-50 to-white flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <img 
          src="/images/logos/Aadi_Color_w_tagname.png" 
          alt="Aadi" 
          class="h-24 mx-auto mb-4"
        />
      </div>

      <!-- Content Card -->
      <div class="bg-white rounded-lg shadow-lg p-8">
        <slot />
      </div>

      <!-- Footer -->
      <p class="text-center text-sm text-slate-600 mt-6">
        © 2026 Aadi. All rights reserved.
      </p>
    </div>
  </div>
</template>
