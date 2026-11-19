<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import UserAvatar from '@/Components/Account/UserAvatar.vue';
import AadiPlusBadge from '@/Components/Badges/AadiPlusBadge.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';

const props = defineProps({
    members: Object,
    filters: { type: Object, default: () => ({}) },
    summary: { type: Object, default: () => ({}) },
    perks: { type: Array, default: () => [] },
    plusEnabled: Boolean,
});

const audiences = [['provider', 'For providers'], ['seeker', 'For seekers']];
const groups = computed(() => audiences.map(([key, label]) => [label, props.perks.filter(perk => perk.audience === key)]));

const blank = value => (value === '' || value === undefined ? null : value);
// Local, editable copy of each perk so typing a limit does not save until the admin confirms.
const drafts = reactive(Object.fromEntries(props.perks.map(perk => [perk.key, { enabled: perk.enabled, freeLimit: perk.freeLimit, plusLimit: perk.plusLimit }])));
const saving = ref('');
const dirty = perk => {
    const draft = drafts[perk.key];
    return draft.enabled !== perk.enabled || (perk.type === 'limit' && (blank(draft.freeLimit) !== blank(perk.freeLimit) || blank(draft.plusLimit) !== blank(perk.plusLimit)));
};
function savePerk(perk) {
    const draft = drafts[perk.key];
    saving.value = perk.key;
    router.patch(`/admin/aadiplus/perks/${perk.key}`, { enabled: draft.enabled, freeLimit: blank(draft.freeLimit), plusLimit: blank(draft.plusLimit) }, {
        preserveScroll: true, onFinish: () => { saving.value = ''; },
    });
}
function toggle(perk) {
    drafts[perk.key].enabled = !drafts[perk.key].enabled;
    savePerk(perk);
}

const query = reactive({ q: props.filters.q || '', status: props.filters.status || '' });
let timer;
function visit() {
    router.get('/admin/aadiplus', Object.fromEntries(Object.entries(query).filter(([, value]) => value)), { preserveState: true, preserveScroll: true, replace: true });
}
function search() { clearTimeout(timer); timer = setTimeout(visit, 300); }

const grant = useForm({ email: '', until: '' });
const extendUntil = reactive({});
const today = new Date().toISOString().slice(0, 10);
function submitGrant() { grant.post('/admin/aadiplus/members', { preserveScroll: true, onSuccess: () => grant.reset() }); }
function setUntil(member, until) {
    router.patch(`/admin/users/${member.id}/aadi-plus`, { aadi_plus_until: until }, { preserveScroll: true, onSuccess: () => { extendUntil[member.id] = ''; } });
}
function revoke(member) {
    if (window.confirm(`Remove Aadi Plus from ${member.name}?`)) setUntil(member, null);
}
// The server sends the calendar date it stored, so the browser timezone cannot shift it by a day.
const dateText = value => new Date(`${value}T12:00:00`).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
const expiringSoon = member => member.active && new Date(member.until) < new Date(Date.now() + 30 * 864e5);
const limitText = value => (value === null || value === '' ? 'Unlimited' : Number(value).toLocaleString());
</script>

<template>
    <Head title="Aadi Plus — Admin" />
    <AdminLayout>
        <template #header><h1 class="plus-title">Aadi Plus</h1></template>

        <p v-if="!plusEnabled" role="status">Aadi Plus is not launched. Perks, limits, and public badges are inactive; settings and membership dates are retained for future use.</p>
        <div class="plus-summary">
            <div><strong>{{ summary.active.toLocaleString() }}</strong><span>Active members</span></div>
            <div :class="{ 'is-attention': summary.expiring }"><strong>{{ summary.expiring.toLocaleString() }}</strong><span>Expiring in 30 days</span></div>
            <div><strong>{{ summary.expired.toLocaleString() }}</strong><span>Expired</span></div>
        </div>

        <section class="panel" aria-labelledby="perks-heading">
            <header>
                <h2 id="perks-heading">Perks</h2>
                <p>Turn a perk off and members get the free-tier behaviour for it. {{ plusEnabled ? 'Changes apply immediately.' : 'Changes are saved for launch.' }}</p>
            </header>
            <div v-for="[label, items] in groups" :key="label" class="perk-group">
                <h3>{{ label }}</h3>
                <article v-for="perk in items" :key="perk.key" class="perk" :class="{ off: !drafts[perk.key].enabled }">
                    <div class="perk-copy">
                        <h4>{{ perk.label }}</h4>
                        <p>{{ perk.description }}</p>
                    </div>
                    <div v-if="perk.type === 'limit'" class="perk-limits">
                        <label>Free<input v-model="drafts[perk.key].freeLimit" type="number" min="0" :max="perk.max" placeholder="Unlimited" :aria-label="`${perk.label}: free limit`" /></label>
                        <label>Members<input v-model="drafts[perk.key].plusLimit" type="number" min="0" :max="perk.max" placeholder="Unlimited" :disabled="!drafts[perk.key].enabled" :aria-label="`${perk.label}: member limit`" /></label>
                        <span class="unit">{{ perk.unit }}</span>
                        <button type="button" class="save" :disabled="!dirty(perk) || saving === perk.key" @click="savePerk(perk)">{{ saving === perk.key ? 'Saving…' : 'Save' }}</button>
                    </div>
                    <button type="button" class="switch" role="switch" :aria-checked="drafts[perk.key].enabled" :aria-label="`${perk.label} enabled`" :disabled="saving === perk.key" @click="toggle(perk)"><span /></button>
                    <p v-if="perk.type === 'limit'" class="perk-now">Now: {{ limitText(perk.freeLimit) }} free → {{ perk.enabled ? limitText(perk.plusLimit) : 'same as free' }} for members</p>
                </article>
            </div>
        </section>

        <section class="panel" aria-labelledby="members-heading">
            <header>
                <h2 id="members-heading">Members</h2>
                <p>Plus is granted manually with an expiry date. Payments are not connected yet.</p>
            </header>
            <form class="grant" @submit.prevent="submitGrant">
                <label>Account email<input v-model="grant.email" type="email" required placeholder="name@example.com" /></label>
                <label>Valid until<input v-model="grant.until" type="date" required :min="today" /></label>
                <button type="submit" :disabled="grant.processing">Grant Aadi Plus</button>
                <p v-if="grant.errors.email || grant.errors.until" class="error" role="alert">{{ grant.errors.email || grant.errors.until }}</p>
            </form>
            <div class="member-filters">
                <input v-model="query.q" type="search" placeholder="Search members" maxlength="100" aria-label="Search members" @input="search" />
                <select v-model="query.status" aria-label="Status" @change="visit"><option value="">All</option><option value="active">Active</option><option value="expiring">Expiring in 30 days</option><option value="expired">Expired</option></select>
            </div>
            <div class="table">
                <table>
                    <thead><tr><th scope="col">Member</th><th scope="col">Status</th><th scope="col">Valid until</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        <tr v-for="member in members.data" :key="member.id">
                            <td><div class="who"><UserAvatar :user="member" /><div><a :href="`/admin/users/${member.id}/listings`">{{ member.name }}</a><small>{{ member.email }}</small></div></div></td>
                            <td><span class="status" :class="{ ok: member.active && !expiringSoon(member), soon: expiringSoon(member) }"><AadiPlusBadge :active="plusEnabled && member.active" variant="icon" size="sm" />{{ !member.active ? 'Expired' : expiringSoon(member) ? 'Expiring' : 'Active' }}</span></td>
                            <td>{{ dateText(member.untilDate) }}</td>
                            <td>
                                <div class="actions">
                                    <input v-model="extendUntil[member.id]" type="date" :min="today" :aria-label="`New expiry for ${member.name}`" />
                                    <button type="button" :disabled="!extendUntil[member.id]" @click="setUntil(member, extendUntil[member.id])">Set date</button>
                                    <button type="button" class="danger" @click="revoke(member)">Remove</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!members.data.length"><td colspan="4" class="empty">No members match. Grant Aadi Plus above to add one.</td></tr>
                    </tbody>
                </table>
            </div>
            <AdminPagination :paginator="members" label="Member pages" />
        </section>
    </AdminLayout>
</template>

<style scoped>
.plus-title { font-size:22px; letter-spacing:-.02em; }
.plus-summary { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:12px; margin-bottom:16px; }
.plus-summary > div { display:flex; flex-direction:column; gap:2px; padding:14px 16px; background:#fff; border:1px solid #dce7e4; border-radius:12px; }
.plus-summary strong { font-size:24px; line-height:1.1; letter-spacing:-.02em; }
.plus-summary span { font-size:12px; color:#637b76; }
.plus-summary .is-attention { border-color:#f2dba0; background:#fffaf0; }
.panel { background:#fff; border:1px solid #dce7e4; border-radius:12px; margin-bottom:16px; padding:18px 20px; }
.panel > header { margin-bottom:12px; }
.panel h2 { font-size:16px; margin:0; }
.panel header p { margin:2px 0 0; font-size:13px; color:#637b76; }
.perk-group h3 { margin:16px 0 6px; font-size:11px; text-transform:uppercase; letter-spacing:.06em; color:#42665e; }
.perk { display:grid; grid-template-columns:minmax(0, 1fr) auto 52px; gap:4px 20px; align-items:center; padding:14px 0; border-top:1px solid #e5edeb; }
.perk-copy h4 { margin:0; font-size:14px; }
.perk-copy p { margin:2px 0 0; font-size:12.5px; color:#637b76; max-width:56ch; }
.perk-now { grid-column:1 / -1; margin:0; font-size:12px; color:#42665e; }
.perk.off .perk-copy h4 { color:#8a9d99; }
.perk-limits { display:flex; align-items:flex-end; gap:10px; }
.perk-limits label { display:flex; flex-direction:column; gap:3px; font-size:11px; color:#637b76; text-transform:uppercase; letter-spacing:.04em; }
.perk-limits input, .member-filters input, .member-filters select, .grant input, .actions input { height:36px; padding:0 10px; border:1px solid #cfdcd8; border-radius:8px; font-size:13px; background:#fff; color:#183e3b; }
.perk-limits input { width:104px; }
.perk-limits input:disabled { background:#f3f6f5; color:#8a9d99; }
.unit { align-self:center; font-size:12px; color:#637b76; }
.switch { grid-column:3; grid-row:1; justify-self:end; width:44px; height:26px; padding:3px; border-radius:999px; border:0; background:#c9d6d2; cursor:pointer; transition:background-color .15s; }
.switch[aria-checked='true'] { background:#167c71; }
.switch span { display:block; width:20px; height:20px; border-radius:50%; background:#fff; transition:transform .15s; }
.switch[aria-checked='true'] span { transform:translateX(18px); }
.save, .grant button, .actions button { height:36px; padding:0 14px; border-radius:8px; border:1px solid #cfdcd8; background:#fff; color:#183e3b; font-size:13px; font-weight:700; cursor:pointer; }
.save:not(:disabled), .grant button:not(:disabled) { background:#167c71; border-color:#167c71; color:#fff; }
button:disabled { opacity:.5; cursor:not-allowed; }
.actions .danger { color:#b42318; border-color:#f3c3c0; }
.grant { display:flex; flex-wrap:wrap; align-items:flex-end; gap:12px; padding:14px; margin-bottom:14px; background:#f8faf9; border-radius:10px; }
.grant label { display:flex; flex-direction:column; gap:3px; font-size:12px; color:#42665e; font-weight:600; }
.grant input { min-width:240px; }
.grant .error { flex-basis:100%; margin:0; font-size:12.5px; color:#b42318; }
.member-filters { display:flex; gap:10px; margin-bottom:12px; }
.member-filters input { flex:1; max-width:340px; }
.table { overflow:auto; border:1px solid #dce7e4; border-radius:12px; }
table { width:100%; text-align:left; font-size:13px; border-collapse:collapse; }
th { background:#f8faf9; padding:10px 16px; font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:#42665e; white-space:nowrap; }
td { padding:10px 16px; border-top:1px solid #e5edeb; vertical-align:middle; }
.who { display:flex; align-items:center; gap:10px; }
.who a { display:block; font-weight:700; color:#183e3b; text-decoration:none; }
.who small { color:#637b76; }
.status { display:inline-flex; align-items:center; gap:6px; font-weight:700; color:#8a9d99; }
.status.ok { color:#12744a; }
.status.soon { color:#8a5a00; }
.actions { display:flex; gap:8px; justify-content:flex-end; }
.empty { text-align:center; color:#637b76; padding:28px; }
.sr-only { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); }
@media (max-width:820px) {
    .plus-summary { grid-template-columns:1fr; }
    .perk { grid-template-columns:minmax(0, 1fr) 52px; }
    .perk-limits { grid-column:1 / -1; flex-wrap:wrap; }
}
</style>
