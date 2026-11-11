<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    requirements: { type: Object, required: true },
    saved: { type: Array, required: true },
    kept: { type: Array, required: true },
    files: { type: Array, required: true },
    metadata: { type: Array, required: true },
    busy: Boolean,
    pending: Boolean,
});
const emit = defineEmits(['update:pending', 'add', 'remove', 'remove-saved', 'keep-saved']);
const type = ref('');
const error = ref('');
const label = value => props.requirements[value]?.label || 'Supporting document';
const keptCount = () => props.saved.filter(doc => props.kept.includes(doc.index)).length;

watch(() => props.pending, value => {
    if (!value) { type.value = ''; error.value = ''; }
});
function choose(event) {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file) return;
    if (keptCount() + props.files.length >= 10) {
        error.value = 'Keep at most 10 documents in total.';
        return;
    }
    if (file.size > 5 * 1024 * 1024 || !['image/jpeg', 'image/png', 'image/webp', 'application/pdf'].includes(file.type)) {
        error.value = 'Use a JPG, PNG, WebP, or PDF file up to 5 MB.';
        return;
    }
    error.value = '';
    emit('add', { file, meta: { type: type.value, reference: '', expiresAt: '' } });
    emit('update:pending', false);
}
</script>

<template>
    <div class="provider-documents">
        <h3>Previously uploaded</h3>
        <p v-if="!saved.length">No documents uploaded yet.</p>
        <div v-for="doc in saved" :key="doc.index" class="document-card">
            <strong>{{ label(doc.type) }}</strong>
            <a :href="doc.url" target="_blank" rel="noopener">{{ doc.name }} · View document</a>
            <p v-if="doc.reference">{{ doc.reference }}</p>
            <p v-if="doc.expiresAt">Expires {{ doc.expiresAt }}</p>
            <p>{{ kept.includes(doc.index) ? 'Saved and kept' : 'Will be removed when you save' }}</p>
            <button v-if="kept.includes(doc.index)" type="button" :disabled="busy" @click="$emit('remove-saved', doc.index)">Remove {{ doc.name }}</button>
            <button v-else type="button" :disabled="busy || keptCount() + files.length >= 10" @click="$emit('keep-saved', doc.index)">Keep {{ doc.name }}</button>
        </div>
        <h3 v-if="files.length">Selected for upload</h3>
        <div v-for="(file, index) in files" :key="index" class="document-card">
            <strong>{{ label(metadata[index].type) }} · {{ file.name }}</strong>
            <p>Selected; not uploaded until you save or submit.</p>
            <label>Property, vehicle, or qualification reference<input v-model="metadata[index].reference" maxlength="255" placeholder="e.g. Vehicle plate or property address (private)" /></label>
            <label>Expiry date (if shown)<input v-model="metadata[index].expiresAt" type="date" /></label>
            <button type="button" :disabled="busy" @click="$emit('remove', index)">Remove {{ file.name }}</button>
        </div>
        <div v-if="pending" class="document-card">
            <label>Document type
                <select v-model="type" aria-label="Document type" :disabled="busy">
                    <option value="" disabled>Select document type</option>
                    <option v-for="(rule, key) in requirements" :key="key" :value="key">{{ rule.label }}</option>
                </select>
            </label>
            <label v-if="type" class="document-upload">Upload file
                <input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" :disabled="busy" @change="choose" />
            </label>
            <p v-if="error" class="document-error" role="alert">{{ error }}</p>
            <button type="button" :disabled="busy" @click="$emit('update:pending', false)">Cancel adding document</button>
        </div>
        <button v-else type="button" class="document-add" :disabled="busy || keptCount() + files.length >= 10" @click="$emit('update:pending', true)">+ Add document</button>
        <p>JPG, PNG, WebP, or PDF. Up to 5 MB per file; 10 files total.</p>
    </div>
</template>

<style scoped>
.provider-documents { display:grid; gap:12px; }
.provider-documents h3 { margin:0; font-size:16px; }
.provider-documents p { margin:0; font-size:13px; line-height:1.5; color:#526764; }
.document-card { display:grid; gap:10px; padding:14px; border:1px solid #dbe9e6; border-radius:12px; overflow-wrap:anywhere; }
.document-card label { display:grid; gap:6px; font-size:13px; font-weight:700; }
.document-card input:not([type=file]),.document-card select { width:100%; min-height:44px; padding:10px 12px; border:1px solid #cbded9; border-radius:9px; background:white; font-size:16px; }
.document-card a { color:#137671; text-decoration:underline; }
.provider-documents button { width:fit-content; min-height:44px; padding:10px 14px; border:1px solid #cbded9; border-radius:9px; background:white; color:#103e3b; font-size:13px; font-weight:700; }
.provider-documents button:disabled { opacity:.6; }
.document-upload { padding:12px; border:2px dashed #8dbbb4; border-radius:10px; background:#f3faf8; }
.document-upload input[type=file] { display:block!important; position:static!important; opacity:1!important; clip:auto!important; clip-path:none!important; width:100%!important; height:auto!important; overflow:visible!important; padding:8px!important; color:#103e3b!important; background:white!important; }
.document-upload input::file-selector-button { border:0; border-radius:7px; background:#137671; color:white; padding:10px 12px; margin-right:10px; font-weight:700; }
.provider-documents .document-error { color:#b42318; }
</style>
