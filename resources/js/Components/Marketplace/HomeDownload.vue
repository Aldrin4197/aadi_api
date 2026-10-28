<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ downloads: { type: Object, default: () => ({}) } });
const platforms = [{ key: 'android', name: 'Google Play', device: 'Android' }, { key: 'ios', name: 'App Store', device: 'iPhone & iPad' }];
</script>

<template>
    <section id="download-aadi" class="landing-section" aria-labelledby="download-title">
        <div class="landing-download">
            <div class="download-copy"><p class="landing-kicker">Aadi, wherever your day takes you</p><h2 id="download-title">Your neighborhood.<br />In your pocket.</h2><p>Keep spaces, vehicles, and local skills within reach. {{ downloads.android || downloads.ios ? 'Choose an available mobile app below, or keep exploring in your browser.' : 'Explore Aadi in your browser today, and find the mobile app here when it’s available.' }}</p>
                <div class="download-options">
                    <div v-for="platform in platforms" :key="platform.key" class="download-option">
                        <a v-if="downloads[platform.key]" :href="downloads[platform.key]" class="download-store" target="_blank" rel="noopener noreferrer"><span>{{ platform.device }}</span><strong>Get it on {{ platform.name }}</strong><span aria-hidden="true">↗</span></a>
                        <div v-else class="download-store unavailable"><span>{{ platform.device }}</span><strong>{{ platform.name }}</strong><span class="download-availability">Coming soon</span></div>
                    </div>
                </div>
                <Link href="/explore" class="landing-text-link">Use Aadi on the web <span aria-hidden="true">↗</span></Link><p class="download-note">No installation needed to browse the map.</p>
            </div>
            <div class="download-art" aria-hidden="true"><div class="aadi-phone"><div class="phone-camera"></div><img class="phone-logo" src="/images/logos/Aadi_Color.png" alt="" width="92" height="42" loading="lazy" /><span class="phone-heading">Good things.<br />Nearby.</span><div class="phone-categories"><span>Spaces</span><span>Vehicles</span><span>Rakets</span></div><img class="phone-scene" src="/images/illustrations/space-studio.svg" alt="" width="480" height="300" loading="lazy" /><div class="phone-caption"><strong>A place for your plans.</strong><span>Start with what’s around you.</span></div><div class="phone-home"></div></div></div>
        </div>
    </section>
</template>

<style scoped>
.landing-download{background:#0b3a38;color:#fff}.download-copy h2,.download-copy .landing-kicker,.download-copy .landing-text-link{color:#fff}.download-copy>p{color:#d0e6e3}.download-store{color:#0b3a38}.download-store.unavailable{color:#d0e6e3}.download-availability{color:#d0e6e3}
</style>
