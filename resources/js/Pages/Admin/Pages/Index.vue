<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({ pages: { type: Array, required: true } });
const saving = ref('');
const error = ref('');

function toggle(page) {
    saving.value = page.key;
    error.value = '';
    router.patch(`/admin/pages/${page.key}`, { enabled: !page.enabled }, {
        preserveScroll: true,
        onError: () => { error.value = 'Could not update the page. Please try again.'; },
        onFinish: () => { saving.value = ''; },
    });
}
</script>

<template>
    <Head title="Page management — Admin" />
    <AdminLayout>
        <template #header><h1 class="page-title">Page management</h1></template>
        <section class="page-panel" aria-labelledby="pages-heading">
            <header>
                <h2 id="pages-heading">Public pages</h2>
                <p>Disabled pages show a temporary-unavailability message. Account, admin and legal pages remain available. API access is not affected.</p>
            </header>
            <p v-if="error" class="page-error" role="alert">{{ error }}</p>
            <div v-for="page in pages" :key="page.key" class="page-row">
                <div>
                    <h3>{{ page.name }} <span class="page-status" :class="{ active: page.enabled }">{{ page.enabled ? 'Active' : 'Disabled' }}</span></h3>
                    <p>{{ page.description }}</p>
                    <code>{{ page.path }}</code>
                </div>
                <button type="button" class="page-toggle" role="switch" :aria-checked="page.enabled" :aria-label="`${page.name} enabled`" :disabled="!!saving" @click="toggle(page)">
                    <span />
                </button>
            </div>
        </section>
    </AdminLayout>
</template>

<style scoped>
.page-title { font-size: 22px; letter-spacing: -.02em; }
.page-panel { background: #fff; border: 1px solid #dce7e4; border-radius: 12px; padding: 20px; }
.page-panel header { margin-bottom: 16px; }
.page-panel h2 { margin: 0; font-size: 16px; }
.page-panel header p, .page-row p { margin: 4px 0 0; color: #637b76; font-size: 13px; line-height: 1.5; }
.page-row { display: flex; justify-content: space-between; align-items: center; gap: 20px; padding: 18px 0; border-top: 1px solid #e5edeb; }
.page-row h3 { margin: 0; font-size: 14px; }
.page-row code { display: block; margin-top: 6px; color: #637b76; font-size: 12px; overflow-wrap: anywhere; }
.page-status { margin-left: 8px; color: #805c16; font-size: 12px; font-weight: 600; }
.page-status.active { color: #167c71; }
.page-toggle { flex: 0 0 auto; width: 44px; height: 26px; padding: 3px; border: 0; border-radius: 999px; background: #c9d6d2; cursor: pointer; }
.page-toggle[aria-checked='true'] { background: #167c71; }
.page-toggle span { display: block; width: 20px; height: 20px; border-radius: 50%; background: #fff; }
.page-toggle[aria-checked='true'] span { transform: translateX(18px); }
.page-toggle:disabled { opacity: .5; cursor: wait; }
.page-toggle:focus-visible { outline: 3px solid #d88b32; outline-offset: 3px; }
.page-error { color: #b42318; font-size: 13px; }
</style>
