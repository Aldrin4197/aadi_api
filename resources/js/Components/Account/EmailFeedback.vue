<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
const page = usePage();
const toast = ref(null);
const dialog = ref(null);
const outcome = ref(null);
let timer;

function show(kind, message) {
  clearTimeout(timer);
  toast.value = { kind, message, id: Date.now() };
  timer = setTimeout(() => { toast.value = null; }, 7000);
}
async function openModal(value) {
  outcome.value = value;
  await nextTick();
  if (dialog.value && !dialog.value.open) dialog.value.showModal();
}
function closeModal() { dialog.value?.close(); outcome.value = null; }

watch(() => page.props, props => {
  const sent = props.flash?.verification;
  if (sent === 'verification-link-sent') show('success', `Verification email sent to ${props.auth?.user?.email ?? 'your inbox'}. Check your inbox and spam folder.`);
  else if (sent === 'delivery-failed') show('error', 'We could not send the verification email. Please try again shortly.');
  if (props.flash?.emailVerified) openModal(props.flash.emailVerified);
}, { immediate: true });

const alreadyVerified = computed(() => outcome.value === 'already');
onBeforeUnmount(() => clearTimeout(timer));
</script>
<template>
  <Teleport to="body">
    <div class="toast-region" aria-live="polite">
      <div v-if="toast" :key="toast.id" class="toast" :class="toast.kind" :role="toast.kind === 'error' ? 'alert' : 'status'">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path v-if="toast.kind === 'success'" d="M5 12.5l4.2 4.2L19 7" /><path v-else d="M12 7v6M12 17h.01M12 3l10 18H2z" /></svg>
        <span>{{ toast.message }}</span>
        <button type="button" aria-label="Dismiss notification" @click="toast = null">✕</button>
      </div>
    </div>
    <dialog v-if="outcome" ref="dialog" class="verified-dialog" aria-labelledby="verified-title" @close="outcome = null" @click="event => { if (event.target === dialog) closeModal(); }">
      <div class="verified-body">
        <span class="verified-mark" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12.5l4.2 4.2L19 7" /></svg></span>
        <h2 id="verified-title">{{ alreadyVerified ? 'Your email is already verified' : 'Email verified!' }}</h2>
        <p>{{ alreadyVerified ? 'Nothing more to do. Your account is ready.' : `Thanks for confirming ${page.props.auth?.user?.email ?? 'your email'}. Your account now has a verified badge, and you can apply for provider roles.` }}</p>
        <div class="verified-actions">
          <Link href="/become-provider" class="verified-primary" @click="closeModal">Apply for a provider role</Link>
          <button type="button" autofocus @click="closeModal">Continue</button>
        </div>
      </div>
    </dialog>
  </Teleport>
</template>
<style scoped>
.toast-region { position: fixed; top: 16px; right: 16px; z-index: 3000; display: grid; gap: 8px; width: min(420px, calc(100vw - 32px)); pointer-events: none; }
.toast { pointer-events: auto; display: flex; align-items: flex-start; gap: 12px; padding: 14px 16px; border-radius: 12px; background: #fff; border: 1px solid #cfe8d6; box-shadow: 0 12px 32px #193e3b2e; color: #193e3b; font-size: 14px; line-height: 1.5; animation: toast-in 220ms cubic-bezier(.23, 1, .32, 1); }
.toast svg { width: 22px; height: 22px; flex: none; fill: none; stroke: #1c8a4c; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.toast.error { border-color: #f1c5bf; }
.toast.error svg { stroke: #b3382c; }
.toast span { flex: 1; min-width: 0; overflow-wrap: anywhere; }
.toast button { flex: none; width: 28px; height: 28px; border-radius: 6px; color: #64746d; cursor: pointer; }
.toast button:hover { background: #eff5f1; }
@keyframes toast-in { from { opacity: 0; transform: translateY(-8px) scale(.98); } }
.verified-dialog { border: 0; padding: 0; border-radius: 18px; width: min(440px, calc(100vw - 32px)); box-shadow: 0 24px 64px #193e3b40; color: #193e3b; }
.verified-dialog::backdrop { background: #193e3b66; }
.verified-dialog[open] { animation: toast-in 240ms cubic-bezier(.23, 1, .32, 1); }
.verified-body { padding: 32px 28px 26px; text-align: center; }
.verified-mark { display: grid; place-items: center; width: 64px; height: 64px; margin: 0 auto 16px; border-radius: 50%; background: #e3f6ea; }
.verified-mark svg { width: 32px; height: 32px; fill: none; stroke: #1c8a4c; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; }
.verified-body h2 { margin: 0 0 8px; font-size: 22px; font-weight: 650; }
.verified-body p { margin: 0 0 22px; color: #64746d; font-size: 14px; line-height: 1.6; }
.verified-actions { display: grid; gap: 10px; }
.verified-actions a, .verified-actions button { display: flex; align-items: center; justify-content: center; min-height: 46px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; border: 1px solid #dce6e2; background: #fff; color: #193e3b; }
.verified-actions .verified-primary { background: #167c71; border-color: #167c71; color: #fff; }
@media (prefers-reduced-motion: reduce) { .toast, .verified-dialog[open] { animation: none; } }
</style>
