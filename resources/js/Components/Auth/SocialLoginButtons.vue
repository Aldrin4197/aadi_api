<script setup lang="ts">
withDefaults(defineProps<{providers?:string[]}>(), {providers:()=>[]});
</script>
<template>
    <div v-if="providers.length" class="social-login">
        <a
            v-for="provider in providers"
            :key="provider"
            class="social-login__link"
            :href="`/auth/${provider}/redirect`"
        >
            <svg v-if="provider === 'google'" class="social-login__google" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09Z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.15v2.84A11 11 0 0 0 12 23Z"/>
                <path fill="#FBBC05" d="M5.84 14.1A6.6 6.6 0 0 1 5.5 12c0-.73.12-1.43.34-2.1V7.06H2.15A11 11 0 0 0 1 12c0 1.79.43 3.48 1.15 4.94l3.69-2.84Z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15A10.5 10.5 0 0 0 12 1 11 11 0 0 0 2.15 7.06L5.84 9.9C6.71 7.3 9.14 5.38 12 5.38Z"/>
            </svg>
            <svg v-else class="social-login__facebook" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M14 22v-9h3l.5-4H14V6.5c0-1.2.3-2 2-2h2V1.1A25 25 0 0 0 15 1c-3 0-5 1.8-5 5v3H7v4h3v9Z"/>
            </svg>
            <span>Continue with {{ provider === 'google' ? 'Google' : 'Facebook' }}</span>
        </a>
        <p>or use your email</p>
    </div>
</template>
<style scoped>
.social-login { display:grid; gap:10px; margin-block:20px; }
.social-login__link {
    display:flex;
    justify-content:center;
    align-items:center;
    gap:12px;
    min-height:48px;
    border:1px solid #cbdedb;
    border-radius:12px;
    padding:10px 16px;
    background:#fff;
    color:#193e3b;
    font-size:14px;
    font-weight:600;
    text-decoration:none;
    transition:background-color 160ms ease, border-color 160ms ease, transform 100ms ease;
}
.social-login__link:focus-visible { outline:3px solid #e6bb51; outline-offset:3px; }
.social-login__link:active { transform:translateY(1px); }
.social-login__google, .social-login__facebook { width:20px; height:20px; flex:none; }
.social-login__facebook { fill:currentColor; }
.social-login p { margin-top:8px; color:#586e6c; font-size:12px; text-align:center; }
@media (hover:hover) and (pointer:fine) {
    .social-login__link:hover { border-color:#a9c5c0; background:#f7fbfa; }
}
@media (prefers-reduced-motion:reduce) {
    .social-login__link { transition:none; }
}
</style>
