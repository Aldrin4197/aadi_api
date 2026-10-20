<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import '../../../css/home-landing.css';

defineProps({ loading: Boolean });
const emit = defineEmits(['explore']);
const page = usePage();
const hasProviderRole = computed(() => Object.values(page.props.auth?.providerRoles || {}).includes('approved'));
const categories = [
    { key: 'spaces', number: '01', title: 'Spaces for your next chapter.', label: 'Explore spaces', description: 'Find a room, a home, or a space that fits your everyday life. See where it is and compare rental rates before taking a closer look.', tags: 'Rooms · Homes · Rental spaces' },
    { key: 'haul', number: '02', title: 'A ride for what’s ahead.', label: 'Explore vehicles', description: 'Browse vehicles for rent around your chosen location. Compare daily rates and check each listing for the details that matter to your trip.', tags: 'Vehicle rentals · Daily rates' },
    { key: 'raketeer', number: '03', title: 'Local skills. A helping hand.', label: 'Explore rakets', description: 'Discover service providers for your next task or project. Compare their listed rates, location, and the work they offer.', tags: 'Freelance services · Local providers' },
];
const steps = [
    { title: 'Start with a place.', text: 'Search a city, use your location, or move the map and choose “Search near map center.”' },
    { title: 'Find your fit.', text: 'Filter by type and price. Compare up to three listings, keeping an eye on whether rates are per month, day, hour, or job.' },
    { title: 'Take a closer look.', text: 'Log in or create an account to open a listing. See its photos, description, location, and provider details.' },
];
const questions = [
    { title: 'Can I browse without an account?', text: 'Yes. Search the map, filter listings, and compare prices freely. You’ll be asked to log in or register when you open listing details.' },
    { title: 'How do nearby results work?', text: 'Use your location or choose a center on the map, then set a search radius. Results are ordered by distance from that point. Distances are straight-line estimates.' },
    { title: 'Can I list more than one kind of offering?', text: 'Yes. One account can apply separately as a landlord, vehicle owner, and service provider. Each role needs approval before you can publish listings for it.' },
    { title: 'Can I book or pay through Aadi?', text: 'The current web app helps you discover and compare listings. Online booking and payments are not available yet.' },
];
</script>

<template>
    <div class="home-landing">
        <section id="about-aadi" class="landing-section landing-intro" aria-labelledby="about-title" tabindex="-1">
            <div>
                <p class="landing-kicker">Good things. Nearby.</p>
                <h2 id="about-title">A little closer to<br />what you need.</h2>
            </div>
            <div class="landing-intro-copy">
                <p>A place to stay. A vehicle for the day. Someone with the right skills. Aadi brings local rentals and services onto one map, so you can start with what’s around you.</p>
                <a href="#discover-aadi" class="landing-text-link">Meet your nearby possibilities <span aria-hidden="true">↓</span></a>
            </div>
        </section>

        <section id="discover-aadi" class="landing-section landing-categories" aria-labelledby="discover-title">
            <div class="landing-section-heading">
                <h2 id="discover-title">What are you looking for?</h2>
                <p>Three ways to find something nearby.</p>
            </div>
            <article v-for="category in categories" :key="category.key" class="landing-category">
                <span class="landing-category-number" aria-hidden="true">{{ category.number }}</span>
                <div><p class="landing-category-tags">{{ category.tags }}</p><h3>{{ category.title }}</h3></div>
                <p class="landing-category-description">{{ category.description }}</p>
                <button class="landing-text-link" :disabled="loading" @click="emit('explore', category.key)">{{ category.label }} <span aria-hidden="true">↗</span></button>
            </article>
        </section>

        <section id="how-it-works" class="landing-how" aria-labelledby="how-title" tabindex="-1">
            <div class="landing-section">
                <div class="landing-section-heading"><div><p class="landing-kicker">From the map to your next find</p><h2 id="how-title">A few steps. More possibilities.</h2></div><a href="#explore-map" class="landing-text-link">Back to the map <span aria-hidden="true">↑</span></a></div>
                <ol class="landing-steps">
                    <li v-for="(step, index) in steps" :key="step.title"><span class="landing-step-number">0{{ index + 1 }}</span><h3>{{ step.title }}</h3><p>{{ step.text }}</p></li>
                </ol>
            </div>
        </section>

        <section id="for-providers" class="landing-section" aria-labelledby="provider-title" tabindex="-1">
            <div class="landing-provider">
                <div class="landing-provider-copy">
                    <p class="landing-kicker">For the people who make a place</p>
                    <h2 id="provider-title">Your space. Your wheels.<br />Your skills. <span>Your Aadi.</span></h2>
                    <p>Have something to offer? Start with one account and apply as a landlord, vehicle owner, or freelance service provider. Once approved, create and manage your listings from your own workspace.</p>
                    <Link :href="hasProviderRole ? '/my-listings' : '/become-provider'" class="button primary">{{ hasProviderRole ? 'Open provider workspace' : 'Become a provider' }} <span aria-hidden="true">↗</span></Link>
                    <p class="landing-provider-note">{{ page.props.auth?.user ? 'Each provider role is reviewed separately.' : 'Log in or register to start your application.' }}</p>
                </div>
                <div class="landing-provider-aside">
                    <img src="/images/logos/Aadi_icon.png" alt="" width="96" height="96" loading="lazy" />
                    <p>Make room for<br />your next opportunity.</p>
                    <ul><li>One account, multiple provider roles</li><li>Photos, rates, and map locations</li><li>Create, edit, and manage your listings</li></ul>
                </div>
            </div>
        </section>

        <section id="aadi-questions" class="landing-section landing-faq" aria-labelledby="questions-title">
            <div class="landing-section-heading"><div><p class="landing-kicker">Before you get started</p><h2 id="questions-title">A few things to know.</h2></div></div>
            <dl class="landing-questions"><div v-for="question in questions" :key="question.title"><dt>{{ question.title }}</dt><dd>{{ question.text }}</dd></div></dl>
        </section>
    </div>
</template>
