<script setup>
import { computed, useId } from 'vue';
const props=defineProps({modelValue:{type:Number,default:0},editable:Boolean,disabled:Boolean,label:{type:String,default:'Rating'}});
const emit=defineEmits(['update:modelValue']);
const name=useId();
const rating=computed(()=>Math.max(0,Math.min(5,Number(props.modelValue)||0)));
const fill=n=>Math.max(0,Math.min(100,(rating.value-n+1)*100))+'%';
const path='m12 2 3.1 6.3 7 .9-5.1 5 1.2 7-6.2-3.3-6.2 3.3 1.2-7-5.1-5 7-.9Z';
</script>
<template>
<fieldset v-if="editable" class="star-picker" :disabled="disabled"><legend>{{ label }}</legend><div class="star-options"><label v-for="n in 5" :key="n" class="star-option"><input type="radio" :name="name" :value="n" :checked="rating===n" :aria-label="`${n} ${n===1?'star':'stars'}`" @change="emit('update:modelValue',n)"/><svg viewBox="0 0 24 24" aria-hidden="true" :class="{'is-filled':n<=rating}"><path :d="path"/></svg></label><output aria-live="polite">{{ rating ? `${rating} / 5` : 'Choose a rating' }}</output></div></fieldset>
<span v-else class="rating-stars" role="img" :aria-label="`${rating} out of 5 stars`"><span v-for="n in 5" :key="n" class="rating-star"><svg viewBox="0 0 24 24" aria-hidden="true"><path :d="path"/></svg><span class="star-fill" :style="{width:fill(n)}"><svg viewBox="0 0 24 24" aria-hidden="true"><path :d="path"/></svg></span></span></span>
</template>
<style scoped>
.rating-stars{display:inline-flex;gap:3px;vertical-align:middle}.rating-star{position:relative;width:20px;height:20px;display:block}.rating-star svg{width:20px;height:20px;max-width:none;fill:#edf1ef;stroke:#9eafa8;stroke-width:1.3}.star-fill{position:absolute;inset:0 auto 0 0;overflow:hidden}.star-fill svg{fill:#df9b3d;stroke:#a96516}.star-picker{border:0;padding:0;margin:0;min-width:0}.star-picker legend{font-weight:600;font-size:14px;margin-bottom:8px}.star-options{display:flex;align-items:center;flex-wrap:wrap;gap:2px}.star-option{position:relative;display:grid;place-items:center;width:44px;height:44px;cursor:pointer;border-radius:6px}.star-option input{position:absolute;width:1px;height:1px;opacity:0}.star-option svg{width:30px;height:30px;fill:#edf1ef;stroke:#9eafa8;stroke-width:1.3}.star-option svg.is-filled{fill:#df9b3d;stroke:#a96516}.star-option:focus-within{outline:2px solid #137671;outline-offset:1px}.star-picker:disabled{opacity:.55}.star-options output{margin-left:10px;font-size:13px;color:#586e6c}
</style>
