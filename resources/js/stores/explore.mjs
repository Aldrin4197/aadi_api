import { defineStore } from 'pinia';
import { compareKey } from '../Components/Marketplace/comparison.mjs';

export function searchForm(filters) {
    return {
        service: filters.service || 'spaces', locationName: filters.locationName || '',
        q: filters.q || '', type: filters.type || '', minPrice: filters.minPrice ?? '',
        maxPrice: filters.maxPrice ?? '', radiusKm: filters.radiusKm || 10,
        lat: filters.lat ?? '', lng: filters.lng ?? '',
    };
}

export const useExploreStore = defineStore('explore', {
    state: () => ({ form: searchForm({}), comparisons: [] }),
    actions: {
        syncFilters(filters) {
            this.form = searchForm(filters);
        },
        reset() {
            this.form = searchForm({});
            this.comparisons = [];
        },
        isCompared(item) {
            return this.comparisons.some(listing => compareKey(listing) === compareKey(item));
        },
        toggleCompare(item) {
            if (this.isCompared(item)) {
                this.comparisons = this.comparisons.filter(listing => compareKey(listing) !== compareKey(item));
                return true;
            }
            if (this.comparisons.length >= 3) return false;
            this.comparisons.push(item);
            return true;
        },
    },
});
