import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, DefineComponent, Fragment, h } from 'vue';
import PersonalOnboarding from '@/Components/Onboarding/PersonalOnboarding.vue';
import PwaNotice from '@/Components/Pwa/PwaNotice.vue';
import EmailFeedback from '@/Components/Account/EmailFeedback.vue';
import { ZiggyVue } from 'ziggy-js';

// Keep browser branding independent of legacy Laravel environment defaults.
const appName = 'Aadi';

createInertiaApp({
    title: (title) => title && title !== appName ? `${title} — ${appName}` : appName,
    // Keep provider URLs and server props stable while presenting the guided modal.
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name === 'Marketplace/ProviderApply' ? 'Onboarding/ProviderApplication' : name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(Fragment, null, [h(PwaNotice), h(App, props), h(PersonalOnboarding), h(EmailFeedback)]) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
