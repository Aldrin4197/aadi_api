<script setup>
import { computed, ref, watch } from 'vue';
import CalendarGrid from '@/Components/CalendarGrid.vue';
import { monthLabel, shiftMonth, todayInTimezone } from '@/Components/calendarDates.mjs';

const props = defineProps({ modelValue: { type: String, default: '' }, timezone: { type: String, required: true }, withTime: Boolean });
const emit = defineEmits(['update:modelValue']);
const today = computed(() => todayInTimezone(props.timezone));
const date = ref(props.modelValue.slice(0, 10));
const time = ref(props.modelValue.slice(11, 16));
const month = ref((date.value || today.value).slice(0, 7));
const previousDisabled = computed(() => month.value <= today.value.slice(0, 7));
watch([date, time], () => emit('update:modelValue', props.withTime ? (date.value && time.value ? `${date.value}T${time.value}` : '') : date.value));
watch(date, value => { if (value) month.value = value.slice(0, 7); });
</script>

<template>
    <div class="booking-date">
        <div class="booking-month">
            <button type="button" aria-label="Previous month" :disabled="previousDisabled" @click="month = shiftMonth(month, -1)">‹</button>
            <strong>{{ monthLabel(month) }}</strong>
            <button type="button" aria-label="Next month" @click="month = shiftMonth(month, 1)">›</button>
        </div>
        <CalendarGrid :month="month" :selected="date" :today="today" :min="today" @select="date = $event" />
        <label>Selected date <input v-model="date" type="date" :min="today" required /></label>
        <label v-if="withTime">Preferred time <input v-model="time" type="time" required /></label>
        <p>Dates are a preference, not confirmed availability. Times use {{ timezone }}.</p>
    </div>
</template>

<style scoped>
.booking-date { padding:12px; border:1px solid #dcece9; border-radius:12px; background:#f9fbfa; display:grid; gap:10px; }
.booking-month { display:flex; align-items:center; justify-content:space-between; color:#0b3a38; }
.booking-month button { width:44px; height:44px; font-size:24px; border-radius:8px; }
.booking-month button:disabled { opacity:.4; }
.booking-date label { display:grid; gap:6px; font-size:13px; font-weight:700; }
.booking-date input { width:100%; min-height:44px; border:1px solid #c9dbd8; border-radius:8px; padding:10px; font-size:16px; }
.booking-date p { margin:0; color:#586e6c; font-size:12px; line-height:1.5; }
</style>
