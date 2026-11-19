<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AccountPageHeader from '@/Components/Account/AccountPageHeader.vue';
import AccountEmptyState from '@/Components/Account/AccountEmptyState.vue';
import { accountIcons } from '@/Components/Account/accountLinks.mjs';
import CalendarGrid from '@/Components/CalendarGrid.vue';
import { monthLabel, shiftMonth } from '@/Components/calendarDates.mjs';
import Layout from '@/Layouts/AccountLayout.vue';

const props = defineProps({ month: String, today: String, selectedDate: String, timezone: String, events: Array });
const initialDate = () => props.selectedDate || (props.today.startsWith(props.month) ? props.today : `${props.month}-01`);
const selected = ref(initialDate());
const editing = ref(null);
const adding = ref(false);
const deleting = ref(null);
const form = useForm({ title: '', event_date: selected.value, event_time: '', note: '' });
const deleteForm = useForm({});
const counts = computed(() => props.events.reduce((result, event) => {
    result[event.date] = (result[event.date] || 0) + 1;
    return result;
}, {}));
const selectedEvents = computed(() => props.events.filter(event => event.date === selected.value)
    .slice().sort((a, b) => (a.time || '').localeCompare(b.time || '') || a.title.localeCompare(b.title)));
const kinds = { spaces: 'Property viewing', haul: 'Vehicle rental', raketeer: 'Service request' };
watch(() => [props.month, props.selectedDate], () => { selected.value = initialDate(); });
function changeMonth(offset) {
    router.get('/calendar', { month: shiftMonth(props.month, offset) }, { preserveState: true, preserveScroll: true });
}
function startNew() {
    editing.value = null;
    adding.value = true;
    deleting.value = null;
    form.clearErrors();
    form.defaults({ title: '', event_date: selected.value, event_time: '', note: '' });
    form.reset();
}
function edit(event) {
    editing.value = event.id;
    adding.value = true;
    deleting.value = null;
    form.clearErrors();
    form.defaults({ title: event.title, event_date: event.date, event_time: event.time || '', note: event.note || '' });
    form.reset();
}
function submit() {
    const options = { preserveScroll: true, onSuccess: () => { selected.value = form.event_date; adding.value = false; editing.value = null; } };
    if (editing.value) form.patch(`/calendar/events/${editing.value}`, options);
    else form.post('/calendar/events', options);
}
function remove(id) {
    deleteForm.delete(`/calendar/events/${id}`, { preserveScroll: true, onSuccess: () => { deleting.value = null; } });
}
</script>

<template>
    <Layout><Head title="Calendar" />
        <main class="account-page">
            <AccountPageHeader title="Calendar" description="Keep track of accepted requests and your private events." />
            <p class="calendar-intro">Accepted requests appear automatically. Personal events are visible only to you and do not block availability. Times use {{ timezone }}.</p>
            <div class="calendar-layout">
                <section class="account-panel calendar-panel" aria-label="Calendar">
                    <div class="calendar-month">
                        <button type="button" class="button calendar-step" aria-label="Previous month" :disabled="adding" @click="changeMonth(-1)"><svg viewBox="0 0 24 24" aria-hidden="true"><path :d="accountIcons.previous" /></svg></button>
                        <h2>{{ monthLabel(month) }}</h2>
                        <button type="button" class="button calendar-step" aria-label="Next month" :disabled="adding" @click="changeMonth(1)"><svg viewBox="0 0 24 24" aria-hidden="true"><path :d="accountIcons.next" /></svg></button>
                    </div>
                    <CalendarGrid :month="month" :selected="selected" :today="today" :counts="counts" @select="selected = $event" />
                    <p class="calendar-key"><span aria-hidden="true">●</span> Scheduled on this date</p>
                </section>
                <section class="account-panel agenda" aria-label="Selected date schedule">
                    <div class="agenda-heading"><div><p class="agenda-kicker">Your schedule</p><h2>{{ selected }}</h2></div><button v-if="!adding" type="button" class="button primary" @click="startNew">Add event</button></div>
                    <form v-if="adding" class="event-form" @submit.prevent="submit">
                        <h3>{{ editing ? 'Edit personal event' : 'New personal event' }}</h3>
                        <label>Title<input v-model="form.title" maxlength="120" required /></label>
                        <label>Date<input v-model="form.event_date" type="date" required /></label>
                        <label>Time (optional)<input v-model="form.event_time" type="time" /></label>
                        <label>Note (optional)<textarea v-model="form.note" maxlength="2000" rows="3"></textarea></label>
                        <div v-if="Object.keys(form.errors).length" class="notice error" role="alert"><p v-for="(error, key) in form.errors" :key="key">{{ error }}</p></div>
                        <div class="event-actions"><button class="button primary" :disabled="form.processing">{{ form.processing ? 'Saving…' : 'Save event' }}</button><button type="button" class="button" :disabled="form.processing" @click="adding = false; editing = null">Cancel</button></div>
                    </form>
                    <AccountEmptyState
                        v-if="!selectedEvents.length"
                        :icon="accountIcons.calendar"
                        title="Nothing scheduled"
                        description="Choose another date or add a personal event to your calendar."
                    />
                    <div v-if="Object.keys(deleteForm.errors).length" class="notice error" role="alert"><p v-for="(error, key) in deleteForm.errors" :key="key">{{ error }}</p></div>
                    <article v-for="event in selectedEvents" :key="`${event.kind}-${event.id}`" class="event">
                        <p class="event-kind">{{ event.kind === 'personal' ? 'Private event' : event.kind === 'tenancy' ? 'Property tenancy · Provider' : `${kinds[event.service]} · ${event.isProvider ? 'Provider' : 'Customer'}` }}</p>
                        <h3>{{ event.title }}</h3>
                        <p>{{ event.time ? `${event.time} (${timezone})` : 'Date only' }}</p>
                        <p v-if="event.note" class="event-note">{{ event.note }}</p>
                        <Link v-if="event.kind === 'request'" href="/requests">View requests →</Link>
                        <Link v-else-if="event.kind === 'tenancy'" href="/provider/records">View rentals and jobs →</Link>
                        <div v-else-if="deleting === event.id" class="event-actions">
                            <span>Delete this event?</span><button type="button" class="button danger-button" :disabled="deleteForm.processing" @click="remove(event.id)">Confirm delete</button><button type="button" class="button" :disabled="deleteForm.processing" @click="deleting = null">Keep</button>
                        </div>
                        <div v-else class="event-actions"><button type="button" class="button" :disabled="adding" @click="edit(event)">Edit</button><button type="button" class="button danger-button" :disabled="adding" @click="deleting = event.id">Delete</button></div>
                    </article>
                </section>
            </div>
        </main>
    </Layout>
</template>

<style scoped>
.calendar-intro { color:var(--muted); line-height:1.6; font-size:var(--aadi-text-body); margin:0 0 22px; max-width:76ch; }
.calendar-layout { display:grid; grid-template-columns:minmax(280px, 1fr) minmax(0, 1.15fr); gap:20px; align-items:start; }
.calendar-panel,.agenda { padding:20px; min-width:0; }
.calendar-month,.agenda-heading { display:flex; align-items:center; justify-content:space-between; gap:12px; }
.calendar-month h2,.agenda h2 { margin:0; font-size:var(--aadi-text-heading); }
.calendar-step { width:44px; height:44px; padding:0; }
.calendar-step svg { width:18px; height:18px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
.calendar-month button:disabled { opacity:.4; }
.calendar-key,.agenda-kicker,.event-kind { font-size:var(--aadi-text-small); color:var(--muted); }
.calendar-key span { color:#db8c20; }
.agenda-kicker { margin:0 0 4px; font-weight:700; color:var(--teal); }
.event { padding:16px 0; border-top:1px solid #dcece9; overflow-wrap:anywhere; }
.event:first-of-type { margin-top:16px; }
.event h3 { margin:4px 0; font-size:var(--aadi-text-subheading); }
.event p { margin:5px 0; font-size:var(--aadi-text-small); color:var(--muted); }
.event .event-kind { color:var(--teal); font-weight:700; }
.event-note { white-space:pre-wrap; }
.event a { color:var(--teal); text-decoration:underline; font-weight:700; }
.event-actions { display:flex; flex-wrap:wrap; gap:12px; align-items:center; margin-top:12px; font-size:13px; }
.danger-button { color:#9e342b!important; border-color:#e7c4c0!important; }
.event-form { display:grid; gap:12px; padding:18px 0; }
.event-form h3 { margin:0; font-size:var(--aadi-text-subheading); }
.event-form label { display:grid; gap:5px; font-size:13px; font-weight:700; }
.event-form input,.event-form textarea { width:100%; min-height:44px; border:1px solid #c9dbd8; border-radius:8px; padding:10px; font-size:16px; }
@media(max-width:700px) { .calendar-layout { grid-template-columns:1fr; } .calendar-panel,.agenda { padding:16px; } }
</style>
