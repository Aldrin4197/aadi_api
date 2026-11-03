<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
const props = defineProps({metrics:{type:Object,required:true}});
const busy=ref(false), error=ref('');
const number=value=>Number(value).toLocaleString('en-PH');
const peak=computed(()=>Math.max(1,...props.metrics.signups.map(day=>day.count)));
const snapshots=computed(()=>[
  {label:'Total accounts',value:props.metrics.totalUsers,note:'All registered users',url:'/admin/users'},
  {label:'Approved providers',value:props.metrics.approvedProviders,note:'Unique users with at least one approved role',url:'/admin/users'},
  {label:'Available listings',value:props.metrics.availableListings,note:'Active spaces and available vehicles / rakets',url:'/explore'},
  {label:'Pending role reviews',value:props.metrics.pendingRoles,note:'Each requested role is counted separately',url:'/admin/approvals'},
]);
const activity=computed(()=>[
  {label:'New accounts',...props.metrics.newUsers},
  {label:'New listings',...props.metrics.newListings},
  {label:'Viewing requests created',...props.metrics.viewingRequests},
]);
function change(days){if(busy.value)return;busy.value=true;error.value='';router.get('/admin/dashboard',{days},{preserveScroll:true,preserveState:true,onError:()=>error.value='Dashboard could not refresh. Please try again.',onFinish:()=>busy.value=false});}
function delta(item){const difference=item.current-item.previous;if(!item.previous)return item.current?'New activity; no previous-period records':'No change';return `${difference>0?'+':''}${Math.round(difference/item.previous*100)}% vs previous period`;}
</script>
<template>
<section class="admin-kpis" :aria-busy="busy" aria-label="Marketplace performance">
  <div class="kpi-toolbar"><div><h2>Marketplace overview</h2><p>{{ metrics.start }} – {{ metrics.end }} · {{ metrics.timezone }}</p></div><div class="period-buttons" aria-label="Reporting period"><button v-for="days in [7,30,90]" :key="days" :aria-pressed="metrics.days===days" :disabled="busy" @click="change(days)">{{ days }} days</button><button :disabled="busy" @click="change(metrics.days)">{{ busy?'Refreshing…':'Refresh' }}</button></div></div>
  <p v-if="error" role="alert" class="kpi-error">{{ error }}</p>
  <div class="snapshot-grid"><Link v-for="item in snapshots" :key="item.label" :href="item.url" class="snapshot"><span>{{ item.label }} <span aria-hidden="true">↗</span></span><strong>{{ number(item.value) }}</strong><small>{{ item.note }}</small></Link></div>
  <p class="kpi-note">Overview totals are current snapshots. Activity below compares the selected calendar-day window (including today so far) with the preceding {{ metrics.days }} days. Deleted records are excluded.</p>
  <div class="activity-grid"><article v-for="item in activity" :key="item.label" class="activity-stat"><h3>{{ item.label }}</h3><strong>{{ number(item.current) }}</strong><p>{{ delta(item) }}</p><small>Previous: {{ number(item.previous) }}</small></article></div>
  <div class="kpi-detail-grid">
    <section class="kpi-panel"><header><h3>Account growth</h3><span>Daily registrations</span></header><div class="signup-bars" role="img" :aria-label="`${metrics.newUsers.current} registrations over ${metrics.days} days; daily values available below.`"><div v-for="day in metrics.signups" :key="day.date" class="signup-column" :title="`${day.date}: ${day.count}`"><span :style="{height: `${day.count/peak*100}%`}"/></div></div><div class="chart-dates"><span>{{ metrics.start }}</span><span>{{ metrics.end }}</span></div><p v-if="!metrics.newUsers.current" class="kpi-note">No new accounts in this period.</p><details><summary>View daily counts</summary><div class="kpi-table-scroll"><table><thead><tr><th>Date</th><th>Accounts</th></tr></thead><tbody><tr v-for="day in metrics.signups" :key="day.date"><td>{{ day.date }}</td><td>{{ day.count }}</td></tr></tbody></table></div></details></section>
    <section class="kpi-panel"><header><h3>Listing inventory</h3><span>Current availability</span></header><div class="kpi-table-scroll"><table><thead><tr><th>Category</th><th>Total</th><th>Available</th></tr></thead><tbody><tr v-for="item in metrics.inventory" :key="item.label"><td><Link :href="item.url">{{ item.label }} ↗</Link></td><td>{{ number(item.total) }}</td><td>{{ number(item.available) }}</td></tr></tbody></table></div><p class="kpi-note">Available excludes drafts, reserved/busy, rented, maintenance and inactive records. This is listing availability, not occupancy or confirmed bookings.</p></section>
    <section class="kpi-panel approval-panel"><header><h3>Needs attention</h3><Link href="/admin/approvals">Review applications ↗</Link></header><div class="approval-counts"><div v-for="role in metrics.pending" :key="role.label"><span>{{ role.label }}</span><strong>{{ number(role.count) }}</strong></div><div><span>Pending viewing requests</span><strong>{{ number(metrics.pendingViewings) }}</strong></div></div><p v-if="!metrics.pendingRoles" class="kpi-note">No provider roles are waiting for review.</p><p class="kpi-note">Role counts include older requests that may still need supporting documents. Viewing requests are reported for visibility; providers handle scheduling.</p></section>
  </div>
</section>
</template>
<style scoped>
.admin-kpis{--kpi-ink:#0b3a38;--kpi-muted:#586775;--kpi-line:#dde5e8;margin-bottom:2rem;color:var(--kpi-ink)}
.kpi-toolbar,.kpi-panel header{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.kpi-toolbar{margin-bottom:1rem}h2{font-size:1.25rem;font-weight:700}h3{font-size:.9375rem;font-weight:700}p{margin:.5rem 0}.kpi-toolbar p,.kpi-panel header>span,.kpi-note,small{font-size:.75rem;color:var(--kpi-muted);line-height:1.6}.period-buttons{display:flex;gap:.25rem;flex-wrap:wrap}.period-buttons button{min-height:44px;padding:.5rem .75rem;border:1px solid var(--kpi-line);border-radius:.5rem;background:white;font-size:.8125rem;font-weight:700}.period-buttons [aria-pressed=true]{background:var(--kpi-ink);color:white}.period-buttons button:disabled{opacity:.6;cursor:wait}.snapshot-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}.snapshot,.activity-stat,.kpi-panel{border:1px solid var(--kpi-line);background:white;border-radius:.75rem;padding:1.25rem;min-width:0}.snapshot{display:flex;flex-direction:column;gap:.5rem}.snapshot>span{display:flex;justify-content:space-between;font-size:.8125rem;font-weight:700}.snapshot strong,.activity-stat strong{font-size:2rem;font-weight:700;font-variant-numeric:tabular-nums}.snapshot:hover{border-color:#1fa6a0}.activity-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;margin:1.25rem 0}.activity-stat p{font-size:.8125rem}.kpi-detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.approval-panel{grid-column:1/-1}.signup-bars{height:140px;display:flex;align-items:end;gap:2px;margin-top:1.5rem;border-bottom:1px solid var(--kpi-line)}.signup-column{height:100%;flex:1;display:flex;align-items:end}.signup-column span{width:100%;background:#157873;border-radius:2px 2px 0 0;min-height:1px}.chart-dates{display:flex;justify-content:space-between;font-size:.6875rem;color:var(--kpi-muted);margin:.5rem 0 1rem}.kpi-table-scroll{overflow:auto;max-height:260px}table{width:100%;border-collapse:collapse;font-size:.8125rem}th,td{padding:.75rem .5rem;border-bottom:1px solid var(--kpi-line);text-align:right}th:first-child,td:first-child{text-align:left}.kpi-panel a{font-size:.8125rem;text-decoration:underline;font-weight:700}summary{cursor:pointer;min-height:44px;font-size:.8125rem}.approval-counts{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem;margin-top:1rem}.approval-counts>div{display:flex;flex-direction:column;gap:.5rem;font-size:.8125rem}.approval-counts strong{font-size:1.5rem}.kpi-error{color:#b42318}.admin-kpis :focus-visible{outline:3px solid #f59e42;outline-offset:3px}
@media(max-width:1100px){.snapshot-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:700px){.activity-grid,.kpi-detail-grid{grid-template-columns:1fr}.approval-counts{grid-template-columns:repeat(2,minmax(0,1fr))}.snapshot{padding:1rem}.snapshot strong{font-size:1.75rem}}
@media(max-width:360px){.snapshot-grid{grid-template-columns:1fr}}
</style>
