<script setup>
defineProps({
    pending: Object,
    blocked: Boolean,
    reloadRequired: Boolean,
    changedOnServer: Boolean,
    error: String,
    excluded: { type: String, required: true },
});
defineEmits(['restore', 'discard', 'continue']);
</script>

<template>
    <div v-if="pending || blocked || error" class="browser-draft" role="status">
        <p v-if="pending">An unfinished draft was found on this browser. Restore it or discard it before continuing.</p>
        <p v-if="pending && changedOnServer">The server data has changed since this draft was started. Restoring replaces the fields saved in the draft; review them before submitting.</p>
        <p v-if="pending">Not saved in this browser: {{ excluded }}. Please check or re-enter these fields.</p>
        <p v-if="error" role="alert">{{ error }}</p>
        <div v-if="(pending || blocked) && !reloadRequired" class="browser-draft-actions">
            <button v-if="pending" type="button" @click="$emit('restore')">{{ changedOnServer ? 'Restore anyway' : 'Restore draft' }}</button>
            <button type="button" @click="$emit('discard')">Discard draft</button>
            <button v-if="blocked" type="button" @click="$emit('continue')">Continue without browser drafts</button>
        </div>
    </div>
    <p v-else class="browser-draft-hint">A partial draft is saved on this browser as you edit. Not saved: {{ excluded }}. Others using this browser may be able to access saved drafts.</p>
</template>

<style scoped>
.browser-draft{padding:14px;margin:12px 0;border:1px solid #b7ceca;border-radius:10px;background:#f4faf8;color:#193e3b}
.browser-draft p{margin:0 0 8px}
.browser-draft-actions{display:flex;flex-wrap:wrap;gap:8px}
.browser-draft-actions button{min-height:44px;padding:8px 12px;border:1px solid #137671;border-radius:6px;color:#137671;background:white;font-weight:600}
.browser-draft-hint{font-size:13px;color:#586e6c;margin:12px 0}
</style>
