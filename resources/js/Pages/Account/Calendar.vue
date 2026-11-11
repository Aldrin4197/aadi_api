<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AccountPageHeader from '@/Components/Account/AccountPageHeader.vue';
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
                        <button type="button" aria-label="Previous month" :disabled="adding" @click="changeMonth(-1)">‹</button>
                        <h2>{{ monthLabel(month) }}</h2>
                        <button type="button" aria-label="Next month" :disabled="adding" @click="changeMonth(1)">›</button>
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
                    <div v-if="!selectedEvents.length" class="agenda-empty">Nothing scheduled for this date.</div>
                    <div v-if="Object.keys(deleteForm.errors).length" class="notice error" role="alert"><p v-for="(error, key) in deleteForm.errors" :key="key">{{ error }}</p></div>
                    <article v-for="event in selectedEvents" :key="`${event.kind}-${event.id}`" class="event">
                        <p class="event-kind">{{ event.kind === 'personal' ? 'Private event' : `${kinds[event.service]} · ${event.isProvider ? 'Provider' : 'Customer'}` }}</p>
                        <h3>{{ event.title }}</h3>
                        <p>{{ event.time ? `${event.time} (${timezone})` : 'Date only' }}</p>
                        <p v-if="event.note" class="event-note">{{ event.note }}</p>
                        <Link v-if="event.kind === 'request'" href="/requests">View requests →</Link>
                        <div v-else-if="deleting === event.id" class="event-actions">
                            <span>Delete this event?</span><button type="button" :disabled="deleteForm.processing" @click="remove(event.id)">Confirm delete</button><button type="button" :disabled="deleteForm.processing" @click="deleting = null">Keep</button>
                        </div>
                        <div v-else class="event-actions"><button type="button" :disabled="adding" @click="edit(event)">Edit</button><button type="button" :disabled="adding" @click="deleting = event.id">Delete</button></div>
                    </article>
                </section>
            </div>
        </main>
    </Layout>
</template>

<style scoped>
.calendar-intro { color:#586e6c; line-height:1.6; font-size:14px; margin-bottom:24px; }
.calendar-layout { display:grid; grid-template-columns:minmax(280px, 1fr) minmax(0, 1.15fr); gap:20px; align-items:start; }
.calendar-panel,.agenda { padding:20px; min-width:0; }
.calendar-month,.agenda-heading { display:flex; align-items:center; justify-content:space-between; gap:12px; }
.calendar-month h2,.agenda h2 { margin:0; font-size:19px; }
.calendar-month button { width:44px; height:44px; font-size:26px; border-radius:8px; }
.calendar-month button:disabled { opacity:.4; }
.calendar-key,.agenda-kicker,.event-kind { font-size:12px; color:#586e6c; }
.calendar-key span { color:#db8c20; }
.agenda-kicker { margin:0 0 4px; font-weight:700; color:#137671; }
.agenda-empty { color:#586e6c; font-size:14px; padding:24px 0; }
.event { padding:16px 0; border-top:1px solid #dcece9; overflow-wrap:anywhere; }
.event:first-of-type { margin-top:16px; }
.event h3 { margin:4px 0; font-size:16px; }
.event p { margin:5px 0; font-size:13px; color:#586e6c; }
.event .event-kind { color:#137671; font-weight:700; }
.event-note { white-space:pre-wrap; }
.event a,.event-actions button:not(.button) { color:#12675f; text-decoration:underline; font-weight:700; }
.event-actions { display:flex; flex-wrap:wrap; gap:12px; align-items:center; margin-top:12px; font-size:13px; }
.event-actions button:not(.button) { min-height:44px; }
.event-form { display:grid; gap:12px; padding:18px 0; }
.event-form h3 { margin:0; font-size:16px; }
.event-form label { display:grid; gap:5px; font-size:13px; font-weight:700; }
.event-form input,.event-form textarea { width:100%; min-height:44px; border:1px solid #c9dbd8; border-radius:8px; padding:10px; font-size:16px; }
@media(max-width:700px) { .calendar-layout { grid-template-columns:1fr; } .calendar-panel,.agenda { padding:16px; } }
</style>
