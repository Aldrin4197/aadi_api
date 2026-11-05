import { onBeforeUnmount, reactive } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Keeps an admin table's filters and sort in the URL query string.
 * Empty values are dropped, and any change returns to page one.
 */
export function useAdminTable(path, initial, { only, defaults = {} } = {}) {
    const state = reactive({ ...initial });
    let timer;

    function query(overrides = {}) {
        // Rows-per-page lives in the URL rather than the table state, so filters and sorts keep it.
        const perPage = new URLSearchParams(window.location.search).get('per_page');
        return Object.fromEntries(
            Object.entries({ ...state, ...(perPage ? { per_page: perPage } : {}), ...overrides }).filter(([key, value]) => value !== '' && value !== null && value !== undefined && value !== defaults[key]),
        );
    }
    function visit(overrides = {}) {
        clearTimeout(timer);
        router.get(path, query(overrides), { preserveState: true, preserveScroll: true, replace: true, only });
    }
    function search() {
        clearTimeout(timer);
        timer = setTimeout(visit, 350);
    }
    function sortBy(column, firstDirection = 'asc') {
        state.dir = state.sort === column ? (state.dir === 'asc' ? 'desc' : 'asc') : firstDirection;
        state.sort = column;
        visit();
    }
    function reset(keys) {
        for (const key of keys) state[key] = defaults[key] ?? '';
        visit();
    }
    onBeforeUnmount(() => clearTimeout(timer));

    return { state, visit, search, sortBy, reset };
}
