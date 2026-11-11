import { test, beforeEach } from 'node:test';
import assert from 'node:assert/strict';
import { createPinia, setActivePinia } from 'pinia';
import { effectScope, reactive, watch } from 'vue';
import { useBrowserDraft } from '../../resources/js/composables/useBrowserDraft.mjs';
import { draftKey, useDraftStore } from '../../resources/js/stores/drafts.mjs';
import { useExploreStore } from '../../resources/js/stores/explore.mjs';

let storage;
beforeEach(() => {
    setActivePinia(createPinia());
    storage = new Map();
    globalThis.localStorage = {
        getItem: key => storage.get(key) ?? null,
        setItem: (key, value) => storage.set(key, value),
        removeItem: key => storage.delete(key),
    };
});

function draft(state) {
    const scope = effectScope();
    const result = scope.run(() => useBrowserDraft({
        userId: () => state.user, kind: 'listing', service: () => state.service,
        id: () => state.id, revision: () => state.revision,
        select: () => ({ title: state.title }),
        apply: data => { state.title = data.title; },
    }));
    return { ...result, stop: () => scope.stop() };
}

test('drafts survive reload, require explicit restore, and clear after success', () => {
    const state = reactive({ user: 1, service: 'spaces', id: 'new', revision: 1, title: '', phone: 'private' });
    const first = draft(state);
    state.title = 'My listing';
    const key = draftKey(1, 'listing', 'spaces', 'new');
    assert.equal(JSON.parse(storage.get(key)).data.title, 'My listing');
    assert.equal(useDraftStore().records[key].data.title, 'My listing');
    assert.doesNotMatch(storage.get(key), /private|phone/);
    first.stop();

    state.title = '';
    const second = draft(state);
    assert.equal(second.pending.value.data.title, 'My listing');
    assert.equal(state.title, '');
    assert.equal(second.changedOnServer.value, false);
    second.restore();
    assert.equal(state.title, 'My listing');
    second.complete(() => { state.title = ''; });
    assert.equal(storage.has(key), false);
    assert.equal(useDraftStore().records[key], undefined);
    second.stop();
});

test('server changes warn on restore, while discard preserves server values', () => {
    const state = reactive({ user: 1, service: 'spaces', id: '42', revision: 1, title: 'Original' });
    const first = draft(state);
    state.title = 'Draft';
    first.stop();
    state.title = 'Updated on server';
    state.revision = 2;
    const second = draft(state);
    assert.equal(second.changedOnServer.value, true);
    second.discard();
    assert.equal(state.title, 'Updated on server');
    assert.equal(storage.size, 0);
    second.stop();
});

test('drafts are isolated by user, service, and listing, and account switches require a reload', () => {
    const state = reactive({ user: 1, service: 'spaces', id: '1', revision: null, title: '' });
    const form = draft(state);
    state.title = 'First';
    const firstKey = draftKey(1, 'listing', 'spaces', '1');
    state.user = 2;
    state.title = 'Second';
    assert.equal(JSON.parse(storage.get(firstKey)).data.title, 'First');
    assert.equal(form.blocked.value, true);
    assert.equal(form.reloadRequired.value, true);
    assert.equal(storage.has(draftKey(2, 'listing', 'spaces', '1')), false);
    assert.notEqual(firstKey, draftKey(2, 'listing', 'spaces', '1'));
    assert.notEqual(firstKey, draftKey(1, 'listing', 'haul', '1'));
    assert.notEqual(firstKey, draftKey(1, 'listing', 'spaces', '2'));
    assert.throws(() => draftKey(null, 'listing', 'spaces'), /signed-in/);
    form.stop();
});

test('switching services does not overwrite the previous service draft during form reset', () => {
    const state = reactive({ user: 1, service: 'spaces', id: 'new', revision: null, title: '' });
    const scope = effectScope();
    scope.run(() => {
        watch(() => state.service, () => { state.title = ''; }, { flush: 'sync' });
        const form = draft(state);
        state.title = 'First service draft';
        state.service = 'haul';
        assert.equal(JSON.parse(storage.get(draftKey(1, 'listing', 'spaces', 'new'))).data.title, 'First service draft');
        state.title = 'Second service draft';
        state.service = 'spaces';
        assert.equal(form.pending.value.data.title, 'First service draft');
        assert.equal(JSON.parse(storage.get(draftKey(1, 'listing', 'haul', 'new'))).data.title, 'Second service draft');
        form.stop();
    });
    scope.stop();
});

test('invalid drafts and unavailable storage are visible and can be bypassed', () => {
    const state = reactive({ user: 1, service: 'spaces', id: 'new', revision: null, title: '' });
    const key = draftKey(1, 'listing', 'spaces', 'new');
    storage.set(key, '{"version":1,"baseline":"x","data":{"title":"a","phone":"secret"}}');
    const invalid = draft(state);
    assert.equal(invalid.blocked.value, true);
    assert.match(invalid.error.value, /invalid/);
    invalid.discard();
    assert.equal(storage.has(key), false);
    invalid.stop();

    globalThis.localStorage.getItem = () => { throw new Error('Storage disabled'); };
    const unavailable = draft(state);
    assert.equal(unavailable.blocked.value, true);
    assert.match(unavailable.error.value, /Storage disabled/);
    unavailable.continueWithoutDrafts();
    assert.equal(unavailable.blocked.value, false);
    assert.match(unavailable.error.value, /will not survive/);
    unavailable.stop();
});

test('draft writes and cleanup surface storage errors', () => {
    const state = reactive({ user: 1, service: 'spaces', id: 'new', revision: null, title: '' });
    const form = draft(state);
    globalThis.localStorage.setItem = () => { throw new Error('quota'); };
    state.title = 'Changed';
    assert.match(form.error.value, /Could not save/);
    globalThis.localStorage.removeItem = () => { throw new Error('denied'); };
    form.complete();
    assert.match(form.error.value, /could not be removed/);
    form.stop();
});

test('draft store rejects malformed JSON', () => {
    const key = draftKey(1, 'provider', 'haul', 'application');
    storage.set(key, '{');
    assert.throws(() => useDraftStore().read(key, { area: '' }), /unreadable/);
});

test('explore store syncs filters and limits comparisons across services', () => {
    const explore = useExploreStore();
    explore.syncFilters({ service: 'haul', q: 'van', lat: 0 });
    assert.equal(explore.form.lat, 0);
    assert.equal(explore.form.q, 'van');
    const items = ['spaces', 'haul', 'raketeer', 'spaces'].map((service, index) => ({ service, id: index === 1 ? 0 : index }));
    for (const item of items.slice(0, 3)) assert.equal(explore.toggleCompare(item), true);
    assert.equal(explore.toggleCompare(items[3]), false);
    assert.equal(explore.isCompared(items[1]), true);
    explore.toggleCompare(items[1]);
    assert.equal(explore.comparisons.length, 2);
    explore.reset();
    assert.equal(explore.comparisons.length, 0);
    assert.equal(explore.form.service, 'spaces');
});
