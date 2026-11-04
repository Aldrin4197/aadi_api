<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import RoleStatusBadge from './RoleStatusBadge.vue';

const props = defineProps({ item: { type: Object, required: true }, requirements: { type: Object, default: () => ({}) } });
const emit = defineEmits(['close']);

const titles = { spaces: 'Aadi Properties', haul: 'Aadi UV', raketeer: 'Aadi Raket' };
const labels = { applicantType: 'Applicant type', businessName: 'Registered business', relationship: 'Ownership / authority', serviceCategory: 'Service category', skills: 'Skills and qualifications', experienceYears: 'Years of experience' };
const reasons = [
    'A required document is missing or unreadable.',
    'One or more documents have expired.',
    'The name on the documents does not match the applicant.',
    'The documents do not show ownership or authority for this role.',
];

const dialog = ref(null);
const documentIndex = ref(0);
const imageFailed = ref(false);
const viewed = ref([]);
const step = ref('choose');
const acknowledged = ref(false);

const application = computed(() => props.item.application);
const documents = computed(() => application.value?.documents || []);
const current = computed(() => documents.value[documentIndex.value]);
const decided = computed(() => props.item.status !== 'pending');
const today = new Date().toISOString().slice(0, 10);
const expired = doc => !!doc.expiresAt && doc.expiresAt < today;
const missing = computed(() => Object.entries(props.requirements).filter(([type, rule]) => rule.required && !documents.value.some(doc => doc.type === type)));
const expiredDocs = computed(() => documents.value.filter(expired));
const allViewed = computed(() => documents.value.length > 0 && viewed.value.length >= documents.value.length);
const blockers = computed(() => {
    const list = [];
    if (!application.value) list.push('This older request has no application or documents.');
    if (missing.value.length) list.push(`${missing.value.length} required ${missing.value.length === 1 ? 'document is' : 'documents are'} missing.`);
    if (expiredDocs.value.length) list.push(`${expiredDocs.value.length} ${expiredDocs.value.length === 1 ? 'document has' : 'documents have'} expired.`);
    if (documents.value.length && !allViewed.value) list.push(`Open every attachment first (${viewed.value.length} of ${documents.value.length} viewed).`);
    return list;
});
const canApprove = computed(() => !blockers.value.length);

const form = useForm({ version: application.value?.version || null, status: '', reviewNote: '' });
const noteOk = computed(() => form.reviewNote.trim().length >= 10);
const date = value => (value ? new Date(value).toLocaleString('en-PH') : 'Not provided');
const isImage = doc => /\.(jpe?g|png|webp)$/i.test(doc.name);
const isPdf = doc => /\.pdf$/i.test(doc.name);

function view(index) {
    documentIndex.value = index;
    imageFailed.value = false;
    if (!viewed.value.includes(index)) viewed.value.push(index);
}
function choose(next) { form.clearErrors(); step.value = next; acknowledged.value = false; }
function addReason(text) { form.reviewNote = form.reviewNote.includes(text) ? form.reviewNote : `${form.reviewNote.trim()} ${text}`.trim(); }

let overflow, opener;
onMounted(() => {
    opener = window.document.activeElement;
    overflow = window.document.body.style.overflow;
    window.document.body.style.overflow = 'hidden';
    dialog.value.showModal();
    if (documents.value.length) view(0);
});
onBeforeUnmount(() => { dialog.value?.close(); window.document.body.style.overflow = overflow; opener?.focus?.(); });
function close() { if (!form.processing) dialog.value.close(); }
function submit() {
    if (form.processing) return;
    if (step.value === 'approve' && (!canApprove.value || !acknowledged.value)) return;
    if (step.value === 'reject' && !noteOk.value) return;
    form.status = step.value === 'approve' ? 'approved' : 'rejected';
    if (form.status === 'approved') form.reviewNote = form.reviewNote.trim();
    form.post(`/admin/approvals/${props.item.userId}/${props.item.service}`, { preserveScroll: true, onSuccess: () => emit('close') });
}
</script>

<template>
    <Teleport to="body">
        <dialog ref="dialog" class="review" aria-labelledby="review-title" @close="emit('close')" @cancel="form.processing && $event.preventDefault()">
            <header>
                <div>
                    <p>Provider verification</p>
                    <h2 id="review-title">{{ application?.displayName || item.name }}</h2>
                    <div class="head-meta">
                        <RoleStatusBadge :service="item.service" :status="item.status" />
                        <span v-if="application?.submittedAt">Submitted {{ date(application.submittedAt) }}</span>
                    </div>
                </div>
                <button type="button" class="close" aria-label="Close application review" :disabled="form.processing" autofocus @click="close">×</button>
            </header>

            <div class="review-grid">
                <section class="details" aria-label="Application details">
                    <h3>Applicant</h3>
                    <dl>
                        <dt>Account holder</dt><dd>{{ item.name }}</dd>
                        <dt>Email</dt><dd>{{ item.email }}</dd>
                        <template v-if="application">
                            <dt>Phone</dt><dd>{{ application.phone }}</dd>
                            <dt>Area</dt><dd>{{ application.area }}</dd>
                            <dt>Role</dt><dd>{{ titles[item.service] }}</dd>
                        </template>
                    </dl>
                    <template v-if="application">
                        <h3>About the provider</h3>
                        <p class="description">{{ application.description }}</p>
                        <dl>
                            <template v-for="(label, key) in labels" :key="key">
                                <template v-if="application.details?.[key] != null && application.details[key] !== ''"><dt>{{ label }}</dt><dd>{{ application.details[key] }}</dd></template>
                            </template>
                        </dl>
                    </template>
                    <p v-else class="callout">This older request has no application details or documents. It cannot be approved. Reject it with a note so the applicant can submit a full application.</p>

                    <h3>Document checklist</h3>
                    <ul class="checklist">
                        <li v-for="(rule, type) in requirements" :key="type">
                            <span>{{ rule.label }}</span>
                            <strong :class="{ missing: rule.required && !documents.some(doc => doc.type === type) }">{{ documents.some(doc => doc.type === type) ? 'Provided' : rule.required ? 'Missing required' : 'Optional' }}</strong>
                        </li>
                    </ul>
                    <p class="muted">“Provided” only confirms that a file was uploaded. Check that it is genuine, valid and matches the applicant.</p>
                </section>

                <section class="attachments" aria-label="Attachments">
                    <h3>Attachments <span>({{ documents.length }})</span><small v-if="documents.length">{{ viewed.length }} of {{ documents.length }} viewed</small></h3>
                    <ul v-if="documents.length" class="thumbs">
                        <li v-for="(doc, index) in documents" :key="doc.index">
                            <button type="button" :aria-pressed="index === documentIndex" :class="{ seen: viewed.includes(index) }" @click="view(index)">
                                <span class="thumb">
                                    <img v-if="isImage(doc)" :src="doc.url + '?preview=1'" alt="" loading="lazy" />
                                    <svg v-else viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h8l4 4v14H7zM15 3v4h4M10 13h6M10 17h6" /></svg>
                                </span>
                                <span class="thumb-text"><strong>{{ requirements[doc.type]?.label || 'Supporting document' }}</strong><small>{{ doc.name }}</small></span>
                                <span v-if="expired(doc)" class="flag bad">Expired</span>
                                <span v-else-if="viewed.includes(index)" class="flag ok" aria-label="Viewed">✓</span>
                            </button>
                        </li>
                    </ul>
                    <template v-if="current">
                        <div class="doc-meta">
                            <strong>{{ current.name }}</strong>
                            <span v-if="current.reference">Reference: {{ current.reference }}</span>
                            <span v-if="current.expiresAt" :class="{ bad: expired(current) }">Expires: {{ current.expiresAt }}</span>
                            <span class="links"><a :href="current.url + '?preview=1'" target="_blank" rel="noopener">Open in new tab ↗</a><a :href="current.url" target="_blank" rel="noopener">Download ↓</a></span>
                        </div>
                        <div class="preview">
                            <img v-if="isImage(current) && !imageFailed" :key="current.url" :src="current.url + '?preview=1'" :alt="current.name" @error="imageFailed = true" />
                            <iframe v-else-if="isPdf(current)" :key="current.url" :src="current.url + '?preview=1'" :title="'Document preview: ' + current.name" />
                            <p v-else>Preview unavailable. Download the original document to review it.</p>
                        </div>
                    </template>
                    <p v-else class="callout">No supporting documents were submitted.</p>
                </section>
            </div>

            <section class="decision" aria-label="Review decision">
                <template v-if="decided">
                    <h3>Decision recorded</h3>
                    <p class="muted"><strong>{{ item.status === 'approved' ? 'Approved' : 'Rejected' }}</strong> on {{ date(item.decidedAt) }}<template v-if="item.reviewer"> by {{ item.reviewer }}</template>.</p>
                    <p v-if="application?.reviewNote" class="note"><strong>Note to applicant</strong>{{ application.reviewNote }}</p>
                    <p v-if="item.status === 'rejected'" class="muted">The applicant can update their documents and apply again from their account.</p>
                    <footer><button type="button" class="secondary" @click="close">Close</button></footer>
                </template>

                <form v-else @submit.prevent="submit">
                    <template v-if="step === 'choose'">
                        <h3>Review decision</h3>
                        <p class="muted">Approval grants this role only. It does not verify individual listings or replace email verification. Rejected applicants can update and apply again.</p>
                        <ul v-if="blockers.length" class="blockers" role="status"><li v-for="blocker in blockers" :key="blocker">{{ blocker }}</li></ul>
                        <footer>
                            <button type="button" class="secondary" @click="close">Cancel</button>
                            <button type="button" class="danger" @click="choose('reject')">Reject…</button>
                            <button type="button" class="primary" :disabled="!canApprove" @click="choose('approve')">Approve…</button>
                        </footer>
                    </template>

                    <template v-else-if="step === 'approve'">
                        <h3>Confirm approval</h3>
                        <p>Approve <strong>{{ application?.displayName || item.name }}</strong> as <strong>{{ titles[item.service] }}</strong>? They will be able to publish listings for this role right away.</p>
                        <label class="check"><input v-model="acknowledged" type="checkbox" required :disabled="form.processing" />I opened every attachment and confirm the documents are genuine, valid and match this applicant.</label>
                        <label class="field">Note to applicant (optional)<textarea v-model="form.reviewNote" maxlength="2000" rows="2" :disabled="form.processing" /></label>
                        <p v-for="(error, key) in form.errors" :key="key" class="error" role="alert">{{ error }}</p>
                        <footer>
                            <button type="button" class="secondary" :disabled="form.processing" @click="choose('choose')">Back</button>
                            <button type="submit" class="primary" :disabled="form.processing || !acknowledged">{{ form.processing ? 'Saving…' : 'Confirm approval' }}</button>
                        </footer>
                    </template>

                    <template v-else>
                        <h3>Reject application</h3>
                        <p>Tell the applicant what to fix. They will see this note and can update their documents and apply again.</p>
                        <div class="reasons" role="group" aria-label="Common reasons"><button v-for="reason in reasons" :key="reason" type="button" @click="addReason(reason)">+ {{ reason }}</button></div>
                        <label class="field">Reason (required, at least 10 characters)<textarea v-model="form.reviewNote" maxlength="2000" rows="3" required :disabled="form.processing" placeholder="Explain missing, unclear or expired documents." /></label>
                        <p v-for="(error, key) in form.errors" :key="key" class="error" role="alert">{{ error }}</p>
                        <footer>
                            <button type="button" class="secondary" :disabled="form.processing" @click="choose('choose')">Back</button>
                            <button type="submit" class="danger solid" :disabled="form.processing || !noteOk">{{ form.processing ? 'Saving…' : 'Confirm rejection' }}</button>
                        </footer>
                    </template>
                </form>
            </section>
        </dialog>
    </Teleport>
</template>

<style scoped>
.review { margin:auto; width:min(1120px, calc(100% - 32px)); max-height:calc(100dvh - 32px); overflow:auto; overscroll-behavior:contain; border:0; border-radius:18px; padding:0; background:#fff; color:#183e3b; font-family:Quicksand, system-ui, sans-serif; }
.review::backdrop { background:#082c2999; }
.review header { display:flex; justify-content:space-between; gap:20px; padding:22px 24px; border-bottom:1px solid #dce7e4; }
.review header p { font-size:12px; color:#637b76; margin:0 0 6px; }
.review h2 { font-size:24px; font-weight:700; margin:0; overflow-wrap:anywhere; }
.head-meta { display:flex; align-items:center; flex-wrap:wrap; gap:12px; margin-top:12px; font-size:12px; color:#637b76; }
.close { width:44px; height:44px; font-size:28px; flex:none; cursor:pointer; }
.review-grid { display:grid; grid-template-columns:minmax(0, 1fr) minmax(0, 1.25fr); }
.review-grid > section { min-width:0; padding:22px 24px; }
.attachments { background:#f4f8f6; border-left:1px solid #dce7e4; }
.review h3 { font-size:16px; font-weight:700; margin:0 0 12px; display:flex; align-items:baseline; gap:8px; }
.review h3 span { color:#637b76; font-weight:500; }
.review h3 small { margin-left:auto; font-size:12px; color:#637b76; font-weight:600; }
.review dl { display:grid; grid-template-columns:130px minmax(0, 1fr); gap:8px 16px; font-size:13px; margin:0 0 24px; }
.review dt { color:#637b76; }
.review dd { margin:0; overflow-wrap:anywhere; white-space:pre-wrap; }
.review p { font-size:13px; line-height:1.6; }
.description { white-space:pre-wrap; overflow-wrap:anywhere; margin:0 0 14px; }
.checklist { list-style:none; margin:0; padding:0; }
.checklist li { display:flex; gap:12px; justify-content:space-between; padding:10px 0; border-bottom:1px solid #dce7e4; font-size:12px; }
.checklist strong { white-space:nowrap; color:#137671; }
.checklist .missing, .bad, .error { color:#b42318; }
.muted { color:#637b76; }
.callout { padding:14px 16px; background:#fff3d6; border-radius:10px; color:#6b4600; }
.thumbs { list-style:none; margin:0 0 16px; padding:0; display:grid; gap:8px; }
.thumbs button { display:flex; align-items:center; gap:12px; width:100%; padding:8px; text-align:left; border:1px solid #cbded8; border-radius:10px; background:#fff; cursor:pointer; }
.thumbs button[aria-pressed=true] { border-color:#137671; box-shadow:0 0 0 2px #13767133; }
.thumb { width:48px; height:48px; flex:none; border-radius:8px; overflow:hidden; background:#e5eeeb; display:grid; place-items:center; }
.thumb img { width:100%; height:100%; object-fit:cover; }
.thumb svg { width:22px; height:22px; fill:none; stroke:#42665e; stroke-width:1.6; stroke-linecap:round; stroke-linejoin:round; }
.thumb-text { min-width:0; display:flex; flex-direction:column; gap:2px; flex:1; }
.thumb-text strong { font-size:13px; }
.thumb-text small { font-size:11px; color:#637b76; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.flag { font-size:11px; font-weight:700; padding:3px 8px; border-radius:20px; flex:none; }
.flag.ok { background:#e1f5ec; color:#12744a; }
.flag.bad { background:#fdeaea; color:#b42318; }
.doc-meta { display:flex; flex-wrap:wrap; align-items:center; gap:6px 16px; font-size:12px; margin-bottom:10px; overflow-wrap:anywhere; }
.doc-meta strong { font-size:13px; flex-basis:100%; }
.links { display:inline-flex; gap:16px; margin-left:auto; }
.links a { color:#137671; text-decoration:underline; min-height:32px; display:inline-flex; align-items:center; }
.preview { background:#e5eeeb; border:1px solid #dce7e4; border-radius:10px; overflow:hidden; }
.preview img { display:block; width:100%; max-height:480px; object-fit:contain; }
.preview iframe { display:block; width:100%; height:460px; border:0; }
.preview p { padding:16px; margin:0; }
.decision { padding:22px 24px; border-top:1px solid #dce7e4; }
.decision h3 { margin-bottom:8px; }
.blockers { margin:12px 0 0; padding:12px 16px 12px 30px; background:#fff3d6; border-radius:10px; font-size:12px; color:#6b4600; }
.note { padding:12px 14px; background:#f4f8f6; border-radius:10px; }
.note strong { display:block; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:#637b76; margin-bottom:4px; }
.check { display:flex; gap:10px; align-items:flex-start; margin:14px 0; font-size:13px; }
.check input { width:18px; height:18px; margin-top:2px; flex:none; accent-color:#137671; }
.field { display:grid; gap:6px; font-size:13px; margin-top:12px; }
.review textarea { width:100%; border:1px solid #b7d8d0; background:#fff; border-radius:9px; padding:10px; color:#183e3b; font-size:13px; }
.reasons { display:flex; flex-wrap:wrap; gap:8px; margin:12px 0 4px; }
.reasons button { padding:7px 12px; font-size:12px; border:1px solid #cbded8; border-radius:20px; background:#fff; color:#42665e; cursor:pointer; text-align:left; }
.reasons button:hover { background:#f4f8f6; }
.review footer { display:flex; justify-content:flex-end; gap:12px; margin-top:18px; flex-wrap:wrap; }
.primary, .secondary, .danger { min-height:44px; border:1px solid #137671; border-radius:10px; padding:10px 18px; font-size:13px; font-weight:700; cursor:pointer; }
.primary { background:#137671; color:#fff; }
.secondary { background:#fff; color:#137671; }
.danger { background:#fff; color:#b42318; border-color:#e5a29c; }
.danger.solid { background:#b42318; color:#fff; border-color:#b42318; }
.review button:disabled { opacity:.5; cursor:not-allowed; }
.review :is(button, a, input, select, textarea):focus-visible { outline:3px solid #e6bb51; outline-offset:2px; }
@media (max-width:760px) {
  .review-grid { grid-template-columns:1fr; }
  .attachments { border-left:0; border-top:1px solid #dce7e4; }
  .review { width:calc(100% - 16px); max-height:calc(100dvh - 16px); }
  .review header, .review-grid > section, .decision { padding:18px; }
  .review dl { grid-template-columns:1fr; gap:2px; }
  .review dd { margin-bottom:10px; }
  .preview iframe { height:360px; }
  .review footer > button { flex:1 1 auto; }
}
</style>
