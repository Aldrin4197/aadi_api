<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ insights: { type: Array, default: () => [] } });
const toneIcon = {
    warning: 'M12 9v4m0 4h.01M10.3 3.9L2.4 18a2 2 0 001.7 3h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z',
    good: 'M5 13l4 4L19 7',
    info: 'M12 8h.01M11 12h1v5h1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
};
</script>

<template>
    <ul class="insight-list">
        <li v-for="insight in insights" :key="insight.title" :class="`tone-${insight.tone}`">
            <span class="insight-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path :d="toneIcon[insight.tone]" /></svg></span>
            <div>
                <strong>{{ insight.title }}</strong>
                <p>{{ insight.text }}</p>
                <Link v-if="insight.href" :href="insight.href">{{ insight.cta }} →</Link>
            </div>
        </li>
    </ul>
</template>

<style scoped>
.insight-list { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:10px; overflow-y:auto; max-height:380px; }
.insight-list li { display:flex; gap:12px; padding:12px; border-radius:11px; background:#f6faf9; --tone:#137671; --tone-bg:#e3f6f4; }
.insight-list li.tone-warning { --tone:#b45f12; --tone-bg:#fdeadb; background:#fff8f0; }
.insight-list li.tone-good { --tone:#12744a; --tone-bg:#e1f5ec; }
.insight-icon { display:grid; place-items:center; width:30px; height:30px; border-radius:50%; background:var(--tone-bg); color:var(--tone); flex-shrink:0; }
.insight-icon svg { width:16px; height:16px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
.insight-list strong { display:block; font-size:13px; line-height:1.4; }
.insight-list p { font-size:12px; line-height:1.55; color:#586e6c; margin:3px 0 0; }
.insight-list a { display:inline-block; margin-top:6px; font-size:12px; font-weight:700; color:var(--tone); }
.insight-list a:focus-visible { outline:2px solid #137671; outline-offset:2px; }
</style>
