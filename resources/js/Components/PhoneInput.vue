<script setup>
import { computed, ref, useId, watch, watchEffect } from 'vue';
import { getCountries, getCountryCallingCode } from 'libphonenumber-js';
import { digitsOnly, joinPhone, splitPhone } from './phoneNumbers.mjs';

const props = defineProps({ modelValue: { type: String, default: '' }, id: String, required: Boolean });
const emit = defineEmits(['update:modelValue']);
const country = ref('PH');
const number = ref('');
const input = ref(null);
const filtered = ref(false);
const errorId = useId();
const hintId = useId();
const invalid = computed(() => /[^0-9+().\s-]/.test(number.value));
watchEffect(() => { input.value?.setCustomValidity(invalid.value ? 'Use numbers only for the phone number.' : ''); }, { flush: 'post' });
const names = typeof Intl.DisplayNames === 'function' ? new Intl.DisplayNames(['en'], { type: 'region' }) : null;
const countries = getCountries().map(code => ({
    code, label: names?.of(code) || code, callingCode: getCountryCallingCode(code),
})).sort((a, b) => a.label.localeCompare(b.label));
let lastEmitted = null;

watch(() => props.modelValue, value => {
    if (value === lastEmitted) return;
    const parts = splitPhone(value);
    country.value = parts.country;
    number.value = parts.number;
    filtered.value = false;
}, { immediate: true });

function update() {
    lastEmitted = joinPhone(country.value, number.value);
    emit('update:modelValue', lastEmitted);
}
function inputNumber(event) {
    const element = event.target;
    const raw = element.value;
    const caret = element.selectionStart;
    const parsed = raw.startsWith('+') ? splitPhone(raw) : null;
    if (parsed && parsed.number !== raw) {
        country.value = parsed.country;
        number.value = digitsOnly(parsed.number);
        element.value = number.value;
        filtered.value = false;
    } else {
        number.value = digitsOnly(raw);
        element.value = number.value;
        filtered.value = raw !== number.value;
        if (caret !== null) {
            const position = digitsOnly(raw.slice(0, caret)).length;
            element.setSelectionRange(position, position);
        }
    }
    update();
}
</script>

<template>
    <div class="phone-input">
        <select v-model="country" aria-label="Phone country code" @change="update">
            <option v-for="item in countries" :key="item.code" :value="item.code">+{{ item.callingCode }} · {{ item.label }}</option>
        </select>
        <input :id="id" ref="input" :value="number" type="tel" autocomplete="tel-national" inputmode="numeric" maxlength="35" :required="required" :aria-invalid="invalid || undefined" :aria-describedby="invalid ? errorId : filtered ? hintId : undefined" aria-label="Phone number" @input="inputNumber" />
        <p v-if="invalid" :id="errorId" class="phone-error" role="alert">Use numbers only for the phone number.</p>
        <p v-else-if="filtered" :id="hintId" class="phone-hint" role="status">Only digits are accepted; other characters were removed.</p>
    </div>
</template>

<style scoped>
.phone-input { display:grid; grid-template-columns:minmax(125px, 42%) minmax(0, 1fr); gap:8px; }
.phone-input select,.phone-input input { min-width:0; width:100%; min-height:44px; padding:10px 12px; border:1px solid #cbded9; border-radius:9px; background:#fff; font-size:16px; }
.phone-error { grid-column:1 / -1; color:#b42318; font-size:13px; margin:0; }
.phone-hint { grid-column:1 / -1; color:#586e6c; font-size:13px; margin:0; }
</style>
