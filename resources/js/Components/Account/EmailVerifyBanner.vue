<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
const page = usePage();
const user = computed(() => page.props.auth?.user);
const sending = ref(false);
const cooldown = ref(0);
let timer;
const outcome = computed(() => page.props.flash?.verification);
const visible = computed(() => user.value && user.value.emailVerified === false);
function resend() {
  if (sending.value || cooldown.value > 0) return;
  sending.value = true;
  router.post('/email/verification-notification', {}, {
    preserveScroll: true,
    preserveState: true,
    onFinish: () => {
      sending.value = false;
      cooldown.value = 60;
      timer = setInterval(() => { cooldown.value -= 1; if (cooldown.value <= 0) clearInterval(timer); }, 1000);
    },
  });
}
onBeforeUnmount(() => clearInterval(timer));
</script>
<template>
  <section v-if="visible" class="verify-banner" aria-label="Email verification">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18v12H3zM3 7l9 7 9-7" /></svg>
    <div class="verify-copy">
      <strong>Your email is not verified yet.</strong>
      <span v-if="outcome === 'verification-link-sent'" role="status">A new link is on its way to {{ user.email }}. Check your inbox and spam folder.</span>
      <span v-else-if="outcome === 'delivery-failed'" role="alert">We could not send the email. Please try again shortly.</span>
      <span v-else>Confirm {{ user.email }} to apply for provider roles and earn a verified badge.</span>
    </div>
    <button type="button" class="verify-resend" :disabled="sending || cooldown > 0" @click="resend">{{ sending ? 'Sending…' : cooldown > 0 ? `Resend in ${cooldown}s` : 'Resend verification email' }}</button>
  </section>
</template>
<style scoped>
.verify-banner { display: flex; align-items: center; gap: 14px; margin: 0; padding: 8px clamp(16px, 4%, 48px); background: #fff5df; border-bottom: 1px solid #f0d9a4; color: #6b4a0a; font-size: 14px; }
.verify-banner svg { width: 22px; height: 22px; flex: none; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.verify-copy { display: flex; flex-direction: column; gap: 2px; min-width: 0; flex: 1; overflow-wrap: anywhere; }
.verify-copy span { font-size: 13px; line-height: 1.5; }
.verify-resend { flex: none; min-height: 44px; padding: 0 16px; border-radius: 8px; border: 1px solid #c58a1b; background: #fff; color: #6b4a0a; font-size: 13px; font-weight: 600; cursor: pointer; }
.verify-resend:hover:not(:disabled) { background: #fff9ea; }
.verify-resend:disabled { opacity: .6; cursor: default; }
@media (max-width: 640px) { .verify-banner { flex-wrap: wrap; } .verify-resend { width: 100%; } }
</style>
