import { computed, ref, watch } from 'vue';
import { draftKey, useDraftStore } from '../stores/drafts.mjs';

export function useBrowserDraft({ userId, kind, service, id, select, apply, revision = () => null }) {
    const store = useDraftStore();
    const pending = ref(null);
    const error = ref('');
    const blocked = ref(false);
    const reloadRequired = ref(false);
    let key;
    let baseline;
    let paused = false;

    const currentBaseline = () => JSON.stringify({ data: select(), revision: revision() });
    const changedOnServer = computed(() => pending.value && pending.value.baseline !== baseline);

    function open() {
        pending.value = null;
        error.value = '';
        blocked.value = false;
        key = null;
        baseline = currentBaseline();
        try {
            key = draftKey(userId(), kind, service(), id());
            pending.value = store.read(key, select());
        } catch (exception) {
            blocked.value = true;
            error.value = exception.message || 'Browser draft storage is unavailable.';
        }
    }

    function discard() {
        try {
            if (!key) throw new Error('A signed-in account is required to manage a draft.');
            store.remove(key);
            pending.value = null;
            blocked.value = false;
            error.value = '';
        } catch {
            error.value = 'Could not discard the browser draft. Check browser storage settings.';
        }
    }

    function continueWithoutDrafts() {
        key = null;
        pending.value = null;
        blocked.value = false;
        error.value = 'Browser drafts are disabled for this form. Your changes will not survive a reload.';
    }

    function restore() {
        if (!pending.value) return;
        paused = true;
        apply(pending.value.data);
        paused = false;
        pending.value = null;
        persist();
    }

    function persist() {
        if (paused || pending.value || blocked.value || !key) return;
        try {
            if (draftKey(userId(), kind, service(), id()) !== key) return;
            if (currentBaseline() === baseline) store.remove(key);
            else store.write(key, baseline, select());
            error.value = '';
        } catch {
            error.value = 'Could not save your browser draft. Your changes are still in this form.';
        }
    }

    function complete(callback = () => {}) {
        paused = true;
        callback();
        baseline = currentBaseline();
        pending.value = null;
        try {
            if (key) store.remove(key);
            error.value = '';
        } catch {
            error.value = 'Saved successfully, but the browser draft could not be removed.';
        }
        paused = false;
    }

    watch(select, persist, { deep: true, flush: 'sync' });
    watch(() => [userId(), service(), id()], (current, previous) => {
        if (previous && String(current[0]) !== String(previous[0])) {
            key = null;
            store.$reset();
            pending.value = null;
            blocked.value = true;
            reloadRequired.value = true;
            error.value = 'The signed-in account changed. Reload this page before continuing.';
            return;
        }
        if (!reloadRequired.value) open();
    }, { immediate: true, flush: 'sync' });

    return { pending, error, blocked, reloadRequired, changedOnServer, restore, discard, continueWithoutDrafts, complete };
}
