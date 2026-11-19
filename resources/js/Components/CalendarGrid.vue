<script setup>
import { computed } from 'vue';
import { calendarDays } from './calendarDates.mjs';

const props = defineProps({ month: { type: String, required: true }, selected: String, today: String, min: String, counts: { type: Object, default: () => ({}) }, unavailableRanges: { type: Array, default: () => [] } });
defineEmits(['select']);
const days = computed(() => calendarDays(props.month));
const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const unavailable = day => props.unavailableRanges.some(range => day >= range.start && day <= range.end);
</script>

<template>
    <div class="calendar-grid" role="group" :aria-label="`Days in ${month}`">
        <span v-for="day in weekdays" :key="day" class="weekday">{{ day }}</span>
        <template v-for="(day, index) in days" :key="day || `blank-${index}`">
            <span v-if="!day" aria-hidden="true"></span>
            <button v-else type="button" class="calendar-day" :class="{ selected: day === selected, today: day === today }"
                :aria-label="`${day}${counts[day] ? `, ${counts[day]} scheduled` : ''}${unavailable(day) ? ', booked' : ''}`"
                :aria-pressed="day === selected" :disabled="(!!min && day < min) || unavailable(day)" @click="$emit('select', day)">
                <span>{{ Number(day.slice(-2)) }}</span><span v-if="counts[day]" class="day-dot" aria-hidden="true"></span>
            </button>
        </template>
    </div>
</template>

<style scoped>
.calendar-grid { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:4px; }
.weekday { padding:8px 0; text-align:center; font-size:12px; font-weight:700; color:#586e6c; }
.calendar-day { position:relative; min-width:0; min-height:44px; border:1px solid transparent; border-radius:10px; color:#0b3a38; font-size:14px; touch-action:manipulation; }
.calendar-day.today { border-color:#137671; }
.calendar-day.selected { background:#137671; color:white; }
.calendar-day:disabled { color:#a0aeac; cursor:not-allowed; }
.calendar-day:active:not(:disabled) { background:#bde5dd; color:#0b3a38; }
.day-dot { position:absolute; bottom:4px; left:calc(50% - 3px); width:6px; height:6px; border-radius:50%; background:#db8c20; }
@media(hover:hover) and (pointer:fine) { .calendar-day:hover:not(:disabled):not(.selected) { background:#e3f6f4; } }
</style>
