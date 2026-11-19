<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import AccountAccessControls from '@/Components/Admin/AccountAccessControls.vue';
import AccountDeletionControls from '@/Components/Admin/AccountDeletionControls.vue';

const props = defineProps({
    open: Boolean,
    owner: { type: Object, required: true },
    restrictionReasons: { type: Object, required: true },
    deletionReasons: { type: Object, required: true },
    history: { type: Array, required: true },
});
const emit = defineEmits(['close']);
const tab = ref('access');
const panel = ref(null);
const tabs = computed(() => [
    { key: 'access', label: 'Account access' },
    { key: 'plus', label: 'Aadi Plus' },
    ...(props.owner.is_admin ? [] : [{ key: 'deletion', label: 'Deletion', danger: !!props.owner.deletion_scheduled_for }]),
]);

watch(() => props.open, async (value) => {
    if (value) { await nextTick(); panel.value?.focus(); }
});
</script>

<template>
    <Transition name="drawer">
        <div v-if="open" class="drawer-backdrop" @click.self="emit('close')" @keydown.esc="emit('close')">
            <aside ref="panel" class="drawer" role="dialog" aria-modal="true" aria-labelledby="settings-title" tabindex="-1">
                <header>
                    <div><h2 id="settings-title">Account settings</h2><p>{{ owner.name }} · {{ owner.email }}</p></div>
                    <button type="button" class="close" aria-label="Close settings" @click="emit('close')">✕</button>
                </header>
                <div class="tabs" role="tablist">
                    <button v-for="item in tabs" :id="`tab-${item.key}`" :key="item.key" type="button" role="tab" :aria-selected="tab === item.key" :aria-controls="`pane-${item.key}`" :class="{ danger: item.danger }" @click="tab = item.key">{{ item.label }}</button>
                </div>
                <div :id="`pane-${tab}`" class="pane" role="tabpanel" :aria-labelledby="`tab-${tab}`">
                    <AccountAccessControls v-if="tab === 'access'" :owner="owner" :reasons="restrictionReasons" :history="history" />
                    <slot v-else-if="tab === 'plus'" name="plus" />
                    <AccountDeletionControls v-else :owner="owner" :reasons="deletionReasons" />
                </div>
            </aside>
        </div>
    </Transition>
</template>

<style scoped>
.drawer-backdrop { position: fixed; inset: 0; z-index: 60; display: flex; justify-content: flex-end; background: rgba(16, 63, 59, .35); }
.drawer { width: min(520px, 100%); height: 100dvh; overflow-y: auto; overscroll-behavior: contain; background: #f7fbfa; box-shadow: -12px 0 32px rgba(16, 63, 59, .18); outline: none; }
header { display: flex; justify-content: space-between; gap: 12px; padding: 20px 20px 12px; }
header h2 { margin: 0; font-size: 18px; font-weight: 700; }
header p { margin: 4px 0 0; font-size: 12px; color: #637b76; overflow-wrap: anywhere; }
.close { flex: none; width: 40px; height: 40px; border-radius: 10px; color: #42665e; cursor: pointer; }
.close:hover { background: #e3f6f4; }
.tabs { display: flex; gap: 4px; padding: 0 20px; border-bottom: 1px solid #dce7e4; flex-wrap: wrap; }
.tabs button { min-height: 44px; padding: 0 12px; margin-bottom: -1px; border-bottom: 2px solid transparent; font-size: 13px; font-weight: 700; color: #637b76; white-space: nowrap; cursor: pointer; }
.tabs button[aria-selected=true] { color: #12675f; border-color: #137671; }
.tabs button.danger { color: #a12626; }
.pane { padding: 4px 20px 32px; }
.pane :deep(section) { margin: 12px 0 0; padding: 0; border: 0; background: transparent; }
.pane.pane :deep(button:not(.close)), .pane.pane :deep(input:not([type=checkbox])), .pane.pane :deep(select) { box-sizing: border-box; min-height: 44px; font-size: 14px; border-radius: 8px; }
.pane.pane :deep(button:not(.close)) { padding: 0 16px; font-weight: 700; line-height: 1.2; }
.drawer-enter-active, .drawer-leave-active { transition: opacity .2s ease-out; }
.drawer-enter-active .drawer, .drawer-leave-active .drawer { transition: transform .22s cubic-bezier(.22, 1, .36, 1); }
.drawer-enter-from, .drawer-leave-to { opacity: 0; }
.drawer-enter-from .drawer, .drawer-leave-to .drawer { transform: translateX(100%); }
@media (prefers-reduced-motion: reduce) { .drawer-enter-active .drawer, .drawer-leave-active .drawer { transition: none; } }
</style>
