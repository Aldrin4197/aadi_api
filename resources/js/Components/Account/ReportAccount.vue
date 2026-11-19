<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({ userId: { type: String, required: true }, name: { type: String, required: true }, showGuest: Boolean, profileAction: Boolean, listingAction: Boolean });
const page = usePage();
const dialog = ref(null);
const sent = ref(false);
const form = useForm({ reason: '' });
function openDialog() {
    dialog.value?.showModal();
}
function closeDialog() {
    dialog.value?.close();
}
function closeOnBackdrop(event) {
    if (event.target !== dialog.value) return;
    const bounds = dialog.value.getBoundingClientRect();
    if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) {
        closeDialog();
    }
}
function submit() {
    form.post(`/users/${props.userId}/reports`, {
        preserveScroll: true,
        onSuccess: () => { form.reset(); closeDialog(); sent.value = true; },
    });
}
</script>

<template>
    <div v-if="(page.props.auth?.user && page.props.auth.user.id !== userId) || (showGuest && !page.props.auth?.user)" class="report-account" :class="{ 'profile-action': profileAction, 'listing-action': listingAction }">
        <a v-if="!page.props.auth?.user" href="/login" class="report-toggle" :aria-label="listingAction || profileAction ? `Log in to report ${name}` : undefined">
            <svg v-if="profileAction || listingAction" viewBox="0 0 24 24" aria-hidden="true" fill="none"><path d="M6 3.5v17m1-16h11l-2.5 4L18 12H7" /></svg>
            <template v-else>{{ listingAction ? 'Log in to report' : `Log in to report ${name}` }}</template>
        </a>
        <p v-else-if="sent" role="status">Report sent for administrator review.</p>
        <button v-else type="button" class="report-toggle" :aria-label="listingAction || profileAction ? `Report ${name}` : undefined" @click="openDialog">
            <svg v-if="profileAction || listingAction" viewBox="0 0 24 24" aria-hidden="true" fill="none"><path d="M6 3.5v17m1-16h11l-2.5 4L18 12H7" /></svg>
            <template v-else>{{ listingAction ? 'Report' : `Report ${name}` }}</template>
        </button>
        <Teleport to="body">
            <dialog ref="dialog" class="report-dialog" :aria-label="`Report ${name}`" @click="closeOnBackdrop">
                <form class="report-dialog__content" @submit.prevent="submit">
                    <header class="report-dialog__header">
                        <h2>Report {{ name }}</h2>
                        <button type="button" class="report-dialog__close" aria-label="Close report dialog" @click="closeDialog">×</button>
                    </header>
                    <label class="report-dialog__label">
                        What happened?
                        <textarea v-model="form.reason" minlength="20" maxlength="4000" rows="5" required />
                    </label>
                    <p class="report-dialog__note">Reports are reviewed by administrators. Please do not include sensitive information unless necessary.</p>
                    <p v-if="form.errors.reason" class="report-dialog__error" role="alert">{{ form.errors.reason }}</p>
                    <div class="report-dialog__actions">
                        <button type="button" class="report-dialog__cancel" :disabled="form.processing" @click="closeDialog">Cancel</button>
                        <button type="submit" class="report-dialog__submit" :disabled="form.processing">Submit report</button>
                    </div>
                </form>
            </dialog>
        </Teleport>
    </div>
</template>

<style scoped>
.report-account { margin: 12px 0; font-size: 13px; }
.report-toggle { min-height: 44px; color: #825139; text-decoration: underline; }
.report-account.profile-action { margin:0; }
.profile-action .report-toggle { display:inline-grid; place-items:center; width:44px; height:44px; padding:0; border:1px solid #d5e0dd; border-radius:50%; background:#fff; color:#825139; text-decoration:none; cursor:pointer; }
.profile-action .report-toggle:hover { background:#faf3ef; }
.profile-action .report-toggle svg { width:20px; height:20px; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
.report-account.listing-action { margin:0; max-width:100%; }
.listing-action .report-toggle { display:inline-grid; place-items:center; width:40px; height:40px; padding:0; border:1px solid #e6d5ca; border-radius:50%; background:#fff; color:#825139; text-decoration:none; cursor:pointer; }
.listing-action .report-toggle:hover { background:#faf3ef; }
.listing-action .report-toggle svg { width:18px; height:18px; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
.report-account > p { margin:0; line-height:1.5; }
.report-toggle:focus-visible { outline:2px solid #137671; outline-offset:2px; }
.report-dialog { width:min(440px, calc(100% - 32px)); max-height:min(90dvh, 680px); padding:0; overflow:auto; border:1px solid #dce7e4; border-radius:14px; background:#fff; color:#193e3b; box-shadow:0 24px 64px #193e3b30; }
.report-dialog::backdrop { background:#102a2870; }
.report-dialog__content { display:grid; gap:16px; padding:24px; }
.report-dialog__header { display:flex; align-items:center; justify-content:space-between; gap:16px; }
.report-dialog__header h2 { margin:0; font-size:20px; font-weight:700; line-height:1.3; overflow-wrap:anywhere; }
.report-dialog__close { display:grid; width:40px; height:40px; flex:none; place-items:center; border:0; border-radius:8px; background:transparent; color:#193e3b; font-size:24px; cursor:pointer; }
.report-dialog__label { display:grid; gap:8px; color:#193e3b; font-weight:600; }
.report-dialog textarea { width:100%; min-height:150px; padding:12px; resize:vertical; border:1px solid #b8cfca; border-radius:8px; background:#fff; color:#193e3b; font:inherit; font-size:16px; line-height:1.5; }
.report-dialog__note, .report-dialog__error { margin:0; color:#586e6c; line-height:1.5; }
.report-dialog__error { color:#a12828; }
.report-dialog__actions { display:flex; justify-content:flex-end; gap:8px; }
.report-dialog__actions button { min-height:44px; padding:10px 16px; border:1px solid #cbdedb; border-radius:9px; font:inherit; font-weight:600; cursor:pointer; }
.report-dialog__cancel { background:#fff; color:#193e3b; }
.report-dialog__submit { border-color:#137671; background:#137671; color:#fff; }
.report-dialog__actions button:disabled { cursor:wait; opacity:.65; }
.report-dialog :is(button,textarea):focus-visible { outline:2px solid #137671; outline-offset:2px; }
@media(max-width:480px) {
    .report-dialog { width:calc(100% - 24px); }
    .report-dialog__content { padding:20px; }
    .report-dialog__actions { display:grid; grid-template-columns:1fr 1fr; }
}
</style>
