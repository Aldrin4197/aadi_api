<script setup>
import { reactive } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import Layout from '@/Layouts/ProviderLayout.vue';
import AccountPageHeader from '@/Components/Account/AccountPageHeader.vue';
import ProviderRecordCard from '@/Components/Account/ProviderRecordCard.vue';

const props = defineProps({ records: Object, counts: Object, filters: Object, properties: Array, archives: Object });
const filter = reactive({ service: props.filters.service || '', status: props.filters.status || '', listing: props.filters.listing || '' });
const tenancy = useForm({ listing: '', email: '', start: '', end: '', amount: '' });
const money = cents => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(cents / 100);
function search() { router.get('/provider/records', Object.fromEntries(Object.entries(filter).filter(([, value]) => value)), { preserveState: true, replace: true }); }
function create() { tenancy.post('/provider/records/tenancies', { preserveScroll: true, onSuccess: () => tenancy.reset() }); }
</script>

<template>
    <Layout><Head title="Rentals and jobs" />
        <main class="account-page">
            <AccountPageHeader title="Rentals and jobs" description="Manage your customers, schedules and payments across your listings." />
            <div class="crm-stats"><div><strong>{{ counts.current }}</strong><span>Current rentals and jobs</span></div><div><strong>{{ counts.upcoming }}</strong><span>Upcoming records</span></div><div><strong>{{ counts.viewings }}</strong><span>Scheduled viewings</span></div><div><strong>{{ money(counts.recordedCents) }}</strong><span>Payments recorded · all time</span></div></div>
            <p class="crm-note">Only you can see these records. Payments are entered by you and are not processed or verified by Aadi.</p>
            <details v-if="properties.length" class="crm-panel">
                <summary>Record a property tenancy</summary>
                <p>Link a registered Aadi user by email. This records an arrangement; it does not create a lease or collect payment.</p>
                <form class="crm-form" @submit.prevent="create">
                    <label>Property<select v-model="tenancy.listing" required><option value="">Select property</option><option v-for="property in properties" :key="property.id" :value="property.id">{{ property.title }}</option></select></label>
                    <label>Customer email<input v-model="tenancy.email" type="email" required></label>
                    <label>Start date<input v-model="tenancy.start" type="date" required></label>
                    <label>End date (optional)<input v-model="tenancy.end" type="date" :min="tenancy.start"></label>
                    <label>Rent per period (PHP)<input v-model="tenancy.amount" type="number" min="0" max="99999999.99" step="0.01"></label>
                    <button class="button primary" :disabled="tenancy.processing">Save tenancy</button>
                    <p v-for="error in Object.values(tenancy.errors)" :key="error" class="error" role="alert">{{ error }}</p>
                </form>
            </details>
            <form class="crm-form filters" @submit.prevent="search">
                <label>Category<select v-model="filter.service"><option value="">All categories</option><option value="spaces">Properties</option><option value="haul">Vehicles</option><option value="raketeer">Services</option></select></label>
                <label>Status<select v-model="filter.status"><option value="">All statuses</option><option value="current">Current rentals and jobs</option><option value="scheduled">Scheduled</option><option value="active">Active</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></label>
                <button class="button">Apply filters</button><button v-if="filter.listing" type="button" class="button" @click="filter.listing = ''; search()">Clear listing filter</button>
            </form>
            <section class="crm-records" aria-label="Provider records">
                <ProviderRecordCard v-for="item in records.data" :key="item.id" :item="item" />
                <p v-if="!records.data.length" class="crm-panel">No records match. Accepted requests will appear here.</p>
            </section>
            <nav v-if="records.prev_page_url || records.next_page_url" class="account-pagination" aria-label="Record pages"><Link v-if="records.prev_page_url" :href="records.prev_page_url">Previous</Link><span>Page {{ records.current_page }} of {{ records.last_page }}</span><Link v-if="records.next_page_url" :href="records.next_page_url">Next</Link></nav>
            <section v-if="archives.data.length || archives.prev_page_url" class="crm-archives" aria-labelledby="archive-title">
                <h2 id="archive-title">Retained history</h2>
                <p class="crm-note">These read-only records relate to deleted accounts. They are excluded from current totals and removed after the retention period.</p>
                <details v-for="archive in archives.data" :key="archive.id" class="crm-panel archive">
                    <summary>{{ archive.listingTitle }} · {{ archive.customerName }}</summary>
                    <p>{{ { viewing: 'Viewing', tenancy: 'Tenancy', rental: 'Vehicle rental', job: 'Service job' }[archive.kind] }} · {{ archive.status }} · {{ archive.start }}<template v-if="archive.end"> – {{ archive.end }}</template></p>
                    <p>Retained until {{ archive.retainUntil }}.</p>
                    <p v-if="archive.agreedCents !== null">Agreed amount{{ archive.kind === 'tenancy' ? ' per period' : '' }}: {{ money(archive.agreedCents) }}</p>
                    <h3 v-if="archive.payments.length">Payment history</h3>
                    <ul v-if="archive.payments.length">
                        <li v-for="(payment, index) in archive.payments" :key="index">{{ payment.paidOn }} · {{ money(payment.amountCents) }}<template v-if="payment.period"> · {{ payment.period }}</template><template v-if="payment.note"> · {{ payment.note }}</template><strong v-if="payment.voided"> · Voided</strong></li>
                    </ul>
                </details>
                <nav v-if="archives.prev_page_url || archives.next_page_url" class="account-pagination" aria-label="Retained history pages"><Link v-if="archives.prev_page_url" :href="archives.prev_page_url">Previous</Link><span>Page {{ archives.current_page }} of {{ archives.last_page }}</span><Link v-if="archives.next_page_url" :href="archives.next_page_url">Next</Link></nav>
            </section>
        </main>
    </Layout>
</template>

<style scoped>
.crm-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; }
.crm-stats div,.crm-panel { padding:16px; background:#fff; border:1px solid #dce7e4; border-radius:10px; }
.crm-stats strong,.crm-stats span { display:block; }.crm-stats strong { font-size:24px; }.crm-stats span,.crm-note,.crm-panel p { color:#637b76; font-size:13px; }
.crm-note { margin:16px 0; }.crm-panel summary { font-weight:700; cursor:pointer; }
.crm-form { display:flex; align-items:end; flex-wrap:wrap; gap:12px; margin:16px 0; }.crm-form label { display:grid; gap:4px; font-size:12px; font-weight:600; }
.crm-form input,.crm-form select { min-height:40px; padding:8px; border:1px solid #cfdcd8; border-radius:8px; font-size:16px; }
.crm-form .error { width:100%; color:#b42318; }.crm-records { display:grid; gap:8px; }
.crm-archives { margin-top:32px; }.crm-archives h2 { font-size:18px; }.archive { margin-top:8px; overflow-wrap:anywhere; }.archive h3 { font-size:14px; }.archive li { font-size:13px; }
@media(max-width:640px) { .crm-stats { grid-template-columns:1fr; } }
</style>
