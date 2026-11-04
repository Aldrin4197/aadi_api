<script setup>
import { ref, computed } from 'vue';
import CityPicker from './CityPicker.vue';
import SearchIcon from './SearchIcon.vue';

defineProps({ busy: Boolean });
const emit = defineEmits(['search', 'locate']);
const active = ref('spaces');
const city = ref(null);
const scenes = {
    spaces: { label: 'Spaces', image: 'space-studio', title: 'Room for your next chapter.', text: 'From a first apartment to a space for your business.' },
    haul: { label: 'Vehicles', image: 'vehicle-van', title: 'A little help with the big moves.', text: 'Find a vehicle for moving day and everyday plans.' },
    raketeer: { label: 'Rakets', image: 'raket-repair', title: 'The right skills, closer to home.', text: 'Discover local help for repairs, projects and more.' },
};
const scene = computed(() => scenes[active.value]);
const submit = () => emit('search', { service: active.value, city: city.value });
</script>
<template>
    <section class="aadi-hero" aria-labelledby="home-title">
        <div class="aadi-hero-copy">
            <p class="landing-kicker">YOUR NEIGHBORHOOD, IN ONE PLACE</p>
            <h1 id="home-title">Big plans.<br /><em>Good finds nearby.</em></h1>
            <p class="aadi-hero-description">Find a place, rent a vehicle, or discover a local raketeer. See what’s nearby, compare your options, and keep the ones you love.</p>
            <form class="hero-search" role="search" aria-label="Search Aadi" @submit.prevent="submit">
                <div class="hero-tabs" role="group" aria-label="What are you looking for?">
                    <button v-for="(item, key) in scenes" :key="key" type="button" :aria-pressed="active === key" @click="active = key"><SearchIcon :name="key" />{{ item.label }}</button>
                </div>
                <div class="hero-search-row">
                    <CityPicker :selected="city?.label" :disabled="busy" @select="city = $event" @clear="city = null" />
                    <button class="button primary" type="submit" :disabled="busy">Search nearby <span aria-hidden="true">↗</span></button>
                </div>
                <button type="button" class="hero-locate" :disabled="busy" @click="emit('locate', active)"><SearchIcon name="location" />Use my current location</button>
            </form>
            <div class="aadi-hero-actions"><a href="#how-it-works" class="landing-text-link">See how Aadi works</a></div>
            <p class="aadi-hero-foot">Browse freely, no account needed. Booking and payments aren’t available yet, and providers are reviewed before they publish.</p>
        </div>
        <figure class="hero-scene hero-switcher">
            <img :src="`/images/illustrations/${scene.image}.svg`" :alt="`${scene.label} illustration`" width="480" height="300" fetchpriority="high" />
            <figcaption aria-live="polite"><strong>{{ scene.title }}</strong><p>{{ scene.text }}</p><span class="hero-illustration-note">Illustration · explore live listings below</span></figcaption>
        </figure>
    </section>
</template>
<style scoped>
.hero-search{margin-top:28px;padding:14px;border:1px solid var(--line);border-radius:16px;background:#fff;box-shadow:0 8px 28px #0b3a380f;max-width:560px}
.hero-tabs{display:flex;gap:6px;padding:4px;background:#edf6f2;border-radius:11px}
.hero-tabs button{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 8px;min-height:44px;font-size:12px;font-weight:700;border-radius:8px;color:#42665e;cursor:pointer}
.hero-tabs button[aria-pressed=true]{background:#fff;color:#12675f;box-shadow:0 2px 6px #123e3810}
.hero-tabs button:focus-visible{outline:2px solid #137671;outline-offset:2px}
.hero-search-row{display:flex;gap:10px;margin-top:12px}
.hero-search-row :deep(.city-picker){flex:1 1 auto}
.hero-search-row :deep(.city-trigger){min-height:48px;border-radius:10px;font-size:13px}
.hero-search-row .button{flex-shrink:0;min-height:48px;padding:12px 18px;white-space:nowrap}
.hero-locate{display:inline-flex;align-items:center;gap:8px;min-height:44px;margin-top:4px;padding:0 4px;font-size:12px;font-weight:700;color:#137671;cursor:pointer}
.hero-locate:hover{text-decoration:underline;text-underline-offset:4px}
.hero-locate:disabled{opacity:.5;cursor:wait}
.hero-switcher figcaption{display:block}.hero-switcher figcaption strong{display:block;font-size:17px}.hero-switcher figcaption p{font-size:13px;line-height:1.6;margin-top:6px;color:#586e6c}.hero-switcher .hero-illustration-note{display:block!important;margin-top:10px;font-size:10px;color:#637b76}
.aadi-hero-actions{margin-top:12px}
@media(max-width:700px){.hero-search-row{flex-direction:column}.hero-search-row .button{width:100%}}
</style>
