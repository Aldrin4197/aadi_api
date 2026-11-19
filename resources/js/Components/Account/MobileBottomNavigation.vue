<script setup>
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { isActiveLink, mobileAccountLinks, mobileQuickLinks } from './accountLinks.mjs';
import '../../../css/mobile-navigation.css';

const page = usePage();
const isProvider = computed(() => page.props.auth?.isProvider === true);
const links = computed(() => mobileAccountLinks({ isProvider: isProvider.value }));
const quickLinks = computed(() => mobileQuickLinks({ isProvider: isProvider.value }));
const path = computed(() => page.url.split(/[?#]/)[0]);
const quickMenu = ref(null);
const menuOpen = ref(false);
const menuActive = computed(() => quickLinks.value.some(link => isActiveLink(path.value, link)));
const quickSections = computed(() => [
    { key: 'provider', label: 'Provider tools' },
    { key: 'account', label: 'Account' },
].map(section => ({
    ...section,
    links: quickLinks.value.filter(link => link.section === section.key),
})).filter(section => section.links.length));

function openQuickMenu() {
    if (quickMenu.value && !quickMenu.value.open) {
        quickMenu.value.showModal();
        menuOpen.value = true;
    }
}

function closeQuickMenu() {
    if (quickMenu.value?.open) quickMenu.value.close();
    menuOpen.value = false;
}

function closeOnBackdrop(event) {
    if (event.target === quickMenu.value) closeQuickMenu();
}

watch(() => page.url, closeQuickMenu);
</script>

<template>
    <nav class="mobile-bottom-nav" aria-label="Mobile navigation">
        <template v-for="link in links" :key="link.href || link.label">
            <Link v-if="!link.isMenu" :href="link.href" class="mobile-bottom-nav-link"
                :aria-current="isActiveLink(path, link) ? 'page' : undefined">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path :d="link.icon" /></svg>
                <span>{{ link.label }}</span>
            </Link>
            <button v-else type="button" class="mobile-bottom-nav-link"
                aria-controls="mobile-quick-navigation" aria-haspopup="dialog"
                :aria-expanded="menuOpen" :aria-current="menuActive ? 'page' : undefined"
                @click="openQuickMenu">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path :d="link.icon" /></svg>
                <span>{{ link.label }}</span>
            </button>
        </template>
    </nav>
    <dialog id="mobile-quick-navigation" ref="quickMenu" class="mobile-quick-dialog"
        :aria-label="isProvider ? 'Manage provider tools' : 'More account navigation'"
        @click="closeOnBackdrop" @close="menuOpen = false">
        <div class="mobile-quick-sheet">
            <header class="mobile-quick-header">
                <h2>{{ isProvider ? 'Manage' : 'More' }}</h2>
                <button type="button" class="mobile-quick-close" @click="closeQuickMenu">Close</button>
            </header>
            <section v-for="section in quickSections" :key="section.key" class="mobile-quick-section">
                <h3>{{ section.label }}</h3>
                <nav :aria-label="section.label">
                    <Link v-for="item in section.links" :key="item.href" :href="item.href"
                        class="mobile-quick-link" :aria-current="isActiveLink(path, item) ? 'page' : undefined"
                        @click="closeQuickMenu">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path :d="item.icon" /></svg>
                        <span>{{ item.label }}</span>
                    </Link>
                </nav>
            </section>
        </div>
    </dialog>
</template>
