<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AccountPageHeader from '@/Components/Account/AccountPageHeader.vue';
import AccountEmptyState from '@/Components/Account/AccountEmptyState.vue';
import RequestCard from '@/Components/Account/RequestCard.vue';
import InboxNavigation from '@/Components/Account/InboxNavigation.vue';
import { accountIcons } from '@/Components/Account/accountLinks.mjs';
import Layout from '@/Layouts/AccountLayout.vue';

const props = defineProps({ requests: Object, timezone: String, activeTab: String });
const page = usePage();
const isProvider = computed(() => page.props.auth?.isProvider === true);
const description = computed(() => isProvider.value
    ? 'Track requests you sent and manage incoming requests from customers.'
    : 'Track the viewing and booking requests you have sent.');
</script>

<template>
    <Layout><Head title="Requests" />
        <main class="account-page">
            <InboxNavigation active="requests" />
            <AccountPageHeader title="Requests" :description="description" />
            <nav class="request-tabs" role="tablist" aria-label="Request type">
                <Link
                    id="requests-tab-sent"
                    href="/requests?tab=sent"
                    role="tab"
                    class="request-tab"
                    :class="{ 'is-active': activeTab === 'sent' }"
                    :aria-selected="activeTab === 'sent'"
                    aria-controls="requests-panel"
                >Sent requests</Link>
                <Link
                    v-if="isProvider"
                    id="requests-tab-received"
                    href="/requests?tab=received"
                    role="tab"
                    class="request-tab"
                    :class="{ 'is-active': activeTab === 'received' }"
                    :aria-selected="activeTab === 'received'"
                    aria-controls="requests-panel"
                >Received requests</Link>
            </nav>
            <div class="requests-guidance">
                <p>Requests require provider acceptance. Contact details are shared only after acceptance, and payment is arranged directly.</p>
                <Link href="/calendar" class="text-button">View accepted requests on your calendar →</Link>
            </div>
            <section id="requests-panel" class="requests-panel" role="tabpanel" :aria-labelledby="activeTab === 'received' ? 'requests-tab-received' : 'requests-tab-sent'">
                <div v-if="requests.data.length" class="requests-list"><RequestCard v-for="item in requests.data" :key="item.id" :item="item" :timezone="timezone" /></div>
                <AccountEmptyState
                    v-else
                    :icon="accountIcons.requests"
                    :title="activeTab === 'received' ? 'No received requests' : 'No sent requests'"
                    :description="activeTab === 'received' ? 'New requests for your listings will appear here.' : 'Explore listings to request a viewing, vehicle, or service.'"
                    :action-href="activeTab === 'received' ? '/my-listings' : '/explore'"
                    :action-label="activeTab === 'received' ? 'Manage listings' : 'Explore listings'"
                />
            </section>
            <nav v-if="requests.prev_page_url || requests.next_page_url" class="account-pagination" aria-label="Requests pagination"><Link v-if="requests.prev_page_url" :href="requests.prev_page_url">Previous</Link><span>Page {{ requests.current_page }} of {{ requests.last_page }}</span><Link v-if="requests.next_page_url" :href="requests.next_page_url">Next</Link></nav>
        </main>
    </Layout>
</template>

<style scoped>
.requests-guidance { display:flex; align-items:center; justify-content:space-between; gap:16px; margin:0 0 10px; padding:8px 0; border-top:1px solid var(--line); border-bottom:1px solid var(--line); }
.requests-guidance p { max-width:72ch; margin:0; color:var(--muted); font-size:var(--aadi-text-small); line-height:1.55; }
.requests-guidance .text-button { flex:none; }
.requests-list { display:grid; gap:7px; }
.request-tabs { display:flex; gap:20px; margin:0 0 10px; border-bottom:1px solid var(--line); }
.request-tab { display:inline-flex; align-items:center; min-height:42px; margin-bottom:-1px; border-bottom:2px solid transparent; color:var(--muted); font-size:var(--aadi-text-small); font-weight:650; text-decoration:none; }
.request-tab.is-active { border-bottom-color:var(--teal); color:var(--teal); }
.request-tab:focus-visible { outline:2px solid var(--teal); outline-offset:2px; }
.requests-panel:focus { outline:none; }
@media(max-width:700px) { .requests-guidance { align-items:flex-start; flex-direction:column; gap:8px; } }
</style>
