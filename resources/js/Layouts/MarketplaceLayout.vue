<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import '../../css/marketplace.css';
const page = usePage();
</script>
<template>
  <div class="marketplace">
    <header class="market-header">
      <Link href="/" class="brand" aria-label="Aadi home"><img src="/images/logos/Aadi_Color.png" alt="Aadi" /><span>Good things. Nearby.</span></Link>
      <nav aria-label="Main navigation">
        <Link href="/" :class="{ current: page.url === '/' || page.url.startsWith('/explore') }">Explore</Link>
        <template v-if="page.props.auth?.user">
          <Link href="/my-listings">My listings</Link>
          <Link v-if="page.props.auth.user.is_admin" href="/admin/approvals">Approvals</Link>
          <Link href="/profile">{{ page.props.auth.user.name }}</Link>
          <Link href="/logout" method="post" as="button" class="quiet">Log out</Link>
        </template>
        <template v-else><Link href="/login">Log in</Link><Link href="/register" class="button primary">Get started</Link></template>
      </nav>
    </header>
    <div v-if="page.props.flash?.success" class="notice success" role="status">{{ page.props.flash.success }}</div>
    <slot />
  </div>
</template>
