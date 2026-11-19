<script setup>
import { nextTick, onBeforeUnmount, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import ListingImage from '@/Components/Marketplace/ListingImage.vue';
import ReportAccount from './ReportAccount.vue';
import UserAvatar from './UserAvatar.vue';

const props = defineProps({ item: { type: Object, required: true }, timezone: { type: String, required: true } });
const accepting = ref(false);
const open = ref(false);
const dialog = ref(null);
const form = useForm({ action: '', shareContact: false });
const kind = { spaces: 'Property viewing', haul: 'Vehicle rental', raketeer: 'Service request' };

async function showDetails() {
    open.value = true;
    await nextTick();
    dialog.value?.showModal();
}

function closeDetails() {
    if (dialog.value?.open) dialog.value.close();
}

function closeOnBackdrop(event) {
    if (event.target === dialog.value) closeDetails();
}

function handleClose() {
    open.value = false;
    accepting.value = false;
    form.clearErrors();
}

function update(action) {
    form.action = action;
    form.patch(`/requests/${props.item.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            accepting.value = false;
            form.reset();
            form.clearErrors();
        },
    });
}

onBeforeUnmount(closeDetails);
</script>

<template>
    <article class="request-entry">
        <button
            type="button"
            class="request-summary"
            :aria-label="`${kind[item.service]} request for ${item.listingTitle}, ${item.requestedAt}, ${item.status}`"
            aria-haspopup="dialog"
            @click="showDetails"
        >
            <span class="request-summary-photo">
                <ListingImage :src="item.photo" :alt="item.listingTitle" :service="item.service" :type="item.listingType" />
            </span>
            <span class="request-summary-copy">
                <span class="request-kind">{{ kind[item.service] }} · {{ item.isProvider ? 'Received' : 'Sent' }}</span>
                <strong class="request-summary-title">{{ item.listingTitle }}</strong>
                <span class="request-summary-meta">{{ item.isProvider ? 'Customer' : 'Provider' }}: {{ item.otherName }}</span>
            </span>
            <span class="request-summary-schedule">
                <strong>{{ item.scheduledTime || item.scheduledDate }}</strong>
                <span>{{ item.scheduledTime ? `${item.scheduledDate} · ${timezone}` : item.requestedUntil ? `Through ${item.requestedUntil}` : 'Date only' }}</span>
            </span>
            <span class="request-summary-status account-status" :class="`is-${item.status}`">{{ item.status }}</span>
            <span class="request-summary-arrow" aria-hidden="true">→</span>
        </button>

        <Teleport to="body">
            <dialog v-if="open" ref="dialog" class="request-dialog" :aria-labelledby="`request-title-${item.id}`" @close="handleClose" @click="closeOnBackdrop">
                <div class="request-dialog-content">
                    <header class="request-dialog-header">
                        <div>
                            <p class="request-kind">{{ kind[item.service] }} · {{ item.isProvider ? 'Received' : 'Sent' }}</p>
                            <h2 :id="`request-title-${item.id}`">{{ item.listingTitle }}</h2>
                        </div>
                        <button type="button" class="request-dialog-close" aria-label="Close request details" autofocus @click="closeDetails">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
                        </button>
                    </header>
                    <div class="request-dialog-photo">
                        <ListingImage :src="item.photo" :alt="item.listingTitle" :service="item.service" :type="item.listingType" />
                    </div>
                    <div class="request-dialog-body">
                        <div class="request-dialog-topline">
                            <span class="account-status" :class="`is-${item.status}`">{{ item.status }}</span>
                            <ReportAccount :user-id="item.otherId" :name="item.otherName" listing-action />
                        </div>
                        <section class="request-dialog-details" aria-label="Request details">
                            <div class="request-dialog-detail request-dialog-detail--person">
                                <UserAvatar :user="{ name: item.otherName, avatar_url: item.otherAvatar }" />
                                <div><span>{{ item.isProvider ? 'Customer' : 'Provider' }}</span><strong>{{ item.otherName }}</strong></div>
                            </div>
                            <div class="request-dialog-detail">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15" rx="2" /><path d="M7.5 3.5v3M16.5 3.5v3M3.5 9.5h17" /></svg>
                                <div><span>{{ item.service === 'spaces' ? 'Preferred viewing' : 'Preferred start' }}</span><strong>{{ item.requestedAt }}<template v-if="item.service === 'spaces'"> ({{ timezone }})</template></strong></div>
                            </div>
                            <div v-if="item.service !== 'spaces'" class="request-dialog-detail">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5h16M6.5 4v7m11-7v7M5 12.5h14v7H5z" /></svg>
                                <div><span>Last day</span><strong>{{ item.requestedUntil || item.requestedAt }}<template v-if="!item.requestedUntil"> (duration arranged directly)</template></strong></div>
                            </div>
                            <div v-if="item.service === 'haul'" class="request-dialog-detail">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h11v11H3zM14 10h4l3 3v4h-7z" /><circle cx="7.5" cy="18" r="1.5" /><circle cx="17.5" cy="18" r="1.5" /></svg>
                                <div><span>Driver requested</span><strong>{{ item.driverRequested ? 'Yes' : 'No' }}</strong></div>
                            </div>
                        </section>
                        <section v-if="item.note" class="request-dialog-note">
                            <h3>Your note</h3>
                            <p>{{ item.note }}</p>
                        </section>
                        <div v-if="item.contact" class="request-contact">
                            <h3>Contact details</h3>
                            <p>Email: <a :href="`mailto:${item.contact.email}`">{{ item.contact.email }}</a></p>
                            <p>Phone: <a v-if="item.contact.phone" :href="`tel:${item.contact.phone}`">{{ item.contact.phone }}</a><span v-else>Not provided</span></p>
                            <p>Arrange the details and payment directly. No payment is collected here.</p>
                        </div>
                        <div v-if="Object.keys(form.errors).length" class="notice error" role="alert"><p v-for="(error, key) in form.errors" :key="key">{{ error }}</p></div>
                    </div>
                    <footer class="request-dialog-footer">
                        <form v-if="accepting" class="request-confirm" @submit.prevent="update('accept')">
                            <label><input v-model="form.shareContact" type="checkbox" required /> I agree to share my account email and phone with this customer and see theirs.</label>
                            <div class="request-actions"><button class="button primary" :disabled="form.processing">Confirm acceptance</button><button type="button" class="button" @click="accepting = false">Back</button></div>
                        </form>
                        <div v-else class="request-actions">
                            <button v-if="item.isProvider && item.status === 'pending'" type="button" class="button primary" @click="accepting = true">Accept</button>
                            <button v-if="item.isProvider && item.status === 'pending'" type="button" class="button" :disabled="form.processing" @click="update('decline')">Decline</button>
                            <button v-if="['pending', 'accepted'].includes(item.status)" type="button" class="button" :disabled="form.processing" @click="update('cancel')">Cancel request</button>
                        </div>
                        <Link v-if="item.canViewListing" class="button primary request-view-listing" :href="`/offers/${item.service}/${item.listingId}`">
                            View listing
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
                        </Link>
                        <p v-else class="request-listing-unavailable">This listing is no longer available to view.</p>
                    </footer>
                </div>
            </dialog>
        </Teleport>
    </article>
</template>

<style scoped>
.request-entry{min-width:0}
.request-summary{display:grid;width:100%;grid-template-columns:64px minmax(0,1fr) minmax(120px,auto) auto 18px;align-items:center;gap:12px;padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--ink);text-align:left;cursor:pointer}
.request-summary:hover{background:#f8fbfa;border-color:#bdd7d1}
.request-summary:focus-visible,.request-dialog :is(button,a,input):focus-visible{outline:2px solid var(--teal);outline-offset:3px}
.request-summary-photo{display:block;width:64px;height:56px;overflow:hidden;border-radius:7px;background:#e3f6f4}
.request-summary-photo :deep(.listing-image){height:100%}
.request-summary-photo :deep(.listing-image-caption){font-size:8px}
.request-summary-copy{display:grid;min-width:0;gap:3px}
.request-kind{margin:0;color:var(--teal);font-size:11px;font-weight:700}
.request-summary-title{overflow:hidden;font-size:var(--aadi-text-body);text-overflow:ellipsis;white-space:nowrap}
.request-summary-meta{overflow:hidden;color:var(--muted);font-size:12px;text-overflow:ellipsis;white-space:nowrap}
.request-summary-schedule{display:grid;gap:3px;white-space:nowrap}
.request-summary-schedule strong{color:var(--ink);font-size:var(--aadi-text-body);font-variant-numeric:tabular-nums}
.request-summary-schedule span{color:var(--muted);font-size:11px}
.request-summary-status{white-space:nowrap}
.request-summary-arrow{color:var(--teal);font-size:18px}
.request-dialog{--ink:#193e3b;--teal:#167c71;--line:#dce6e2;--muted:#64746d;width:min(640px,calc(100% - 24px));max-height:min(calc(100dvh - 24px),760px);margin:auto;padding:0;overflow:hidden;border:1px solid var(--line);border-radius:14px;background:#fff;color:var(--ink);box-shadow:0 24px 64px #193e3b30}
.request-dialog[open]{display:flex;flex-direction:column}
.request-dialog::backdrop{background:#102a2870}
.request-dialog-content{display:flex;min-height:0;max-height:min(calc(100dvh - 24px),760px);flex-direction:column}
.request-dialog-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:18px 20px;border-bottom:1px solid var(--line);background:#fff}
.request-dialog-header h2{margin:4px 0 0;font-size:var(--aadi-text-heading);line-height:1.35;overflow-wrap:anywhere}
.request-dialog-close{display:grid;width:40px;height:40px;flex:none;place-items:center;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--ink);cursor:pointer}
.request-dialog-close:hover{background:#f4f8f6}
.request-dialog-close svg,.request-dialog-detail svg,.request-view-listing svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.request-dialog-photo{height:128px;overflow:hidden;background:#e3f6f4}
.request-dialog-photo :deep(.listing-image){height:100%}
.request-dialog-photo :deep(.listing-image-caption){font-size:11px}
.request-dialog-body{display:grid;gap:10px;padding:10px 20px 12px}
.request-dialog-topline{display:flex;align-items:center;justify-content:space-between;gap:12px}
.request-dialog-topline .report-account{margin:0}
.request-dialog-details{display:grid;border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
.request-dialog-detail{display:flex;align-items:flex-start;gap:12px;padding:8px 2px}
.request-dialog-detail+.request-dialog-detail{border-top:1px solid var(--line)}
.request-dialog-detail>svg{flex:none;margin-top:2px;color:var(--teal)}
.request-dialog-detail>div{display:grid;gap:3px;min-width:0}
.request-dialog-detail span{color:var(--muted);font-size:12px}
.request-dialog-detail strong{font-size:var(--aadi-text-small);font-weight:600;line-height:1.45;overflow-wrap:anywhere}
.request-dialog-detail--person{align-items:center}
.request-dialog-detail--person :deep(.user-avatar){width:36px;height:36px;font-size:11px}
.request-dialog-note{padding-top:10px;border-top:1px solid var(--line)}
.request-dialog-note h3,.request-contact h3{margin:0 0 6px;font-size:13px;font-weight:700}
.request-dialog-note p{margin:0;color:var(--ink);font-size:var(--aadi-text-small);line-height:1.55;white-space:pre-wrap;overflow-wrap:anywhere}
.request-dialog-body>p{margin:0;color:var(--muted);font-size:var(--aadi-text-small);line-height:1.55;overflow-wrap:anywhere}
.request-listing-unavailable{color:var(--muted);font-size:var(--aadi-text-small)}
.request-contact{padding:10px 0 0;border-top:1px solid var(--line)}
.request-contact p{margin:6px 0 0;color:var(--muted);font-size:var(--aadi-text-small);overflow-wrap:anywhere}
.request-contact a{color:var(--teal);text-decoration:underline}
.request-dialog-footer{display:flex;flex:none;align-items:center;justify-content:space-between;gap:12px;padding:14px 20px 18px;border-top:1px solid var(--line);background:#fff}
.request-actions{display:flex;flex-wrap:wrap;gap:8px}
.request-actions .button{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:8px 14px;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--ink);font:inherit;font-size:13px;font-weight:650;cursor:pointer}
.request-actions .button.primary{border-color:var(--teal);background:var(--teal);color:#fff}
.request-actions .button:disabled{opacity:.55;cursor:wait}
.request-view-listing{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:42px;padding:8px 14px;border:1px solid var(--teal);border-radius:8px;background:var(--teal);color:#fff;font-size:13px;font-weight:650;text-decoration:none}
.request-view-listing:hover{background:#0b5b54}
.request-dialog-footer>.request-listing-unavailable{margin:0}
.request-confirm{padding:12px 0 0;border-top:1px solid var(--line)}
.request-confirm label{display:flex;align-items:flex-start;gap:8px;font-size:13px;line-height:1.5}
.request-confirm input{flex:none;margin-top:4px}
@media(max-width:600px){
    .request-summary{grid-template-columns:48px minmax(0,1fr) auto;gap:8px;padding:9px}
    .request-summary-photo{width:48px;height:48px;grid-row:1/3}
    .request-summary-title{white-space:normal}
    .request-summary-schedule{grid-column:2;grid-row:2;display:flex;align-items:baseline;gap:6px}
    .request-summary-schedule strong{font-size:var(--aadi-text-small)}
    .request-summary-schedule span{font-size:10px}
    .request-summary-status{grid-column:3;grid-row:1}
    .request-summary-arrow{display:none}
    .request-dialog-header,.request-dialog-body{padding-inline:14px}
    .request-dialog-header{padding-block:12px}
    .request-dialog-photo{height:88px}
    .request-dialog-body{gap:8px;padding-block:8px 10px}
    .request-dialog-detail{gap:10px;padding-block:6px}
    .request-dialog-note,.request-contact{padding-top:8px}
    .request-dialog-footer{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;padding:9px 12px 11px}
    .request-dialog-footer>.request-actions{display:contents}
    .request-dialog-footer .request-actions .button{width:100%;min-width:0;padding-inline:8px;white-space:normal}
    .request-dialog-footer>.request-confirm,.request-dialog-footer>.request-listing-unavailable{grid-column:1/-1}
    .request-dialog-footer>.request-view-listing{width:100%;padding-inline:8px}
    .request-dialog-footer>.request-view-listing:only-child{grid-column:1/-1}
    .request-dialog-footer .request-confirm .request-actions{display:flex}
}
@media(max-height:700px){
    .request-dialog-photo{display:none}
}
</style>
