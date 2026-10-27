<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import '../../../css/home-landing.css';
import HomeFeatured from './HomeFeatured.vue';
import HomeCommunity from './HomeCommunity.vue';
import HomeDownload from './HomeDownload.vue';

defineProps({ loading: Boolean, content: { type: Object, default: () => ({}) } });
const emit = defineEmits(['explore']);
const page = usePage();
const hasProviderRole = computed(() => Object.values(page.props.auth?.providerRoles || {}).includes('approved'));
const categories = [
    { key: 'spaces', title: 'Space rentals', label: 'Explore spaces', image: 'space-studio', alt: 'Illustration of a furnished rental space', description: 'Rooms, homes, and workspaces for your next chapter.', tags: 'Rooms · Homes · Commercial spaces' },
    { key: 'haul', title: 'Utility vehicles', label: 'Explore vehicles', image: 'vehicle-van', alt: 'Illustration of a utility van', description: 'Vans and utility vehicles for moving day and everyday plans.', tags: 'Vans · Trucks · More ways to move' },
    { key: 'raketeer', title: 'Raket Finder', label: 'Explore rakets', image: 'raket-repair', alt: 'Illustration of tools for local repair services', description: 'Local help with repairs, tutoring, creative work, and more.', tags: 'Repairs · Support · Local expertise' },
];
const steps = [
    { title: 'Find your fit', text: 'Search a city, use your location, or move the map and choose “Search near map center.”' },
    { title: 'Weigh your options', text: 'Filter by type and price. Compare up to three listings, keeping an eye on whether rates are per month, day, hour, or job.' },
    { title: 'Know the details', text: 'Log in or create an account to open a listing. See its photos, description, location, and provider details.' },
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
        <HomeFeatured :items="content.featured || []" />
        <section id="discover-aadi" class="landing-section landing-categories" aria-labelledby="discover-title">
            <div class="landing-section-heading">
                <div><p class="landing-kicker">The Aadi neighborhood</p><h2 id="discover-title">What are you looking for?</h2></div>
                <p>One place for spaces, wheels, and skills.</p>
            </div>
            <div class="landing-category-grid">
                <article v-for="category in categories" :key="category.key" class="landing-category">
                    <img class="landing-category-art" :src="`/images/illustrations/${category.image}.svg`" :alt="category.alt" width="480" height="300" loading="lazy" decoding="async" />
                    <div class="landing-category-body">
                        <p class="landing-category-tags">{{ category.tags }}</p>
                        <h3>{{ category.title }}</h3>
                        <p class="landing-category-description">{{ category.description }}</p>
                        <button class="landing-text-link" :disabled="loading" @click="emit('explore', category.key)">{{ category.label }} <span aria-hidden="true">↗</span></button>
                    </div>
                </article>
            </div>
        </section>

        <section id="how-it-works" class="landing-how" aria-labelledby="how-title" tabindex="-1">
            <div class="landing-section">
                <div class="landing-section-heading"><div><p class="landing-kicker">Less searching. More doing.</p><h2 id="how-title">Find it. Compare it. Take the next step.</h2></div><a href="#explore-map" class="landing-text-link">Back to the map <span aria-hidden="true">↑</span></a></div>
                <ol class="landing-steps">
                    <li v-for="(step, index) in steps" :key="step.title"><span class="landing-step-number">0{{ index + 1 }}</span><h3>{{ step.title }}</h3><p>{{ step.text }}</p></li>
                </ol>
            </div>
        </section>

        <HomeCommunity :reviews="content.reviews || []" />
        <HomeDownload :downloads="content.downloads || {}" />

        <section id="for-providers" class="landing-section" aria-labelledby="provider-title" tabindex="-1">
            <div class="landing-provider">
                <div class="landing-provider-copy">
                    <p class="landing-kicker">For landlords, vehicle owners, and service providers</p>
                    <h2 id="provider-title">Make your offering<br /><span>a local discovery.</span></h2>
                    <p>Put your rental space, vehicle, or service on the map. Apply for the provider role that fits your offering, then manage your approved listings, photos, and rates from one workspace.</p>
                    <Link :href="hasProviderRole ? '/my-listings' : '/become-provider'" class="button primary">{{ hasProviderRole ? 'Open provider workspace' : 'Become a provider' }} <span aria-hidden="true">↗</span></Link>
                    <p class="landing-provider-note">{{ page.props.auth?.user ? 'Each provider role is reviewed separately.' : 'Log in or register to start your application.' }}</p>
                </div>
                <div class="landing-provider-aside">
                    <img src="/images/illustrations/space-cowork.svg" alt="Illustration of a workspace ready for its next occupant" width="480" height="300" loading="lazy" />
                    <p>Built around your offering.</p>
                    <ul><li>One account, multiple provider roles</li><li>Photos, rates, and map locations</li><li>Update your listings as your plans change</li></ul>
                </div>
            </div>
        </section>

        <section id="aadi-questions" class="landing-section landing-faq" aria-labelledby="questions-title">
            <div class="landing-section-heading"><div><p class="landing-kicker">Before you get started</p><h2 id="questions-title">A few things to know.</h2></div></div>
            <div class="landing-questions"><details v-for="question in questions" :key="question.title"><summary>{{ question.title }}</summary><p>{{ question.text }}</p></details></div>
        </section>
    </div>
</template>
