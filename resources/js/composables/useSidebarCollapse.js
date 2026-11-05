import { ref } from 'vue';

/**
 * Remembers whether a sidebar is collapsed. Inertia pages each render their own layout, so the
 * layout remounts on every visit: the saved state must be read before the first render or the
 * sidebar paints expanded and then animates shut. `animate` is only true after a manual toggle.
 */
export function useSidebarCollapse(storageKey) {
    let saved = false;
    try { saved = localStorage.getItem(storageKey) === 'true'; } catch { /* storage unavailable */ }

    const compact = ref(saved);
    const animate = ref(false);

    function toggle() {
        animate.value = true;
        compact.value = !compact.value;
        try { localStorage.setItem(storageKey, String(compact.value)); } catch { /* storage unavailable */ }
    }

    return { compact, animate, toggle };
}
