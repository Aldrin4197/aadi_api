import { defineStore } from 'pinia';

const prefix = 'aadi:draft:v1:';

export function draftKey(userId, kind, service, id = 'new') {
    if (userId == null || userId === '') throw new Error('A signed-in account is required to save a draft.');
    return prefix + [userId, kind, service, id].map(value => encodeURIComponent(String(value))).join(':');
}

function sameShape(value, example) {
    if (example && typeof example === 'object' && !Array.isArray(example)) {
        return value && typeof value === 'object' && !Array.isArray(value)
            && Object.keys(value).length === Object.keys(example).length
            && Object.keys(example).every(key => Object.hasOwn(value, key) && sameShape(value[key], example[key]));
    }
    if (typeof example === 'string' || typeof example === 'number') {
        return typeof value === 'string' || (typeof value === 'number' && Number.isFinite(value));
    }
    return typeof value === typeof example && value !== null;
}

export const useDraftStore = defineStore('drafts', {
    state: () => ({ records: {} }),
    actions: {
        read(key, example) {
            const raw = localStorage.getItem(key);
            if (raw === null) {
                delete this.records[key];
                return null;
            }
            let record;
            try {
                record = JSON.parse(raw);
            } catch {
                throw new Error('The saved browser draft is unreadable. Discard it to continue.');
            }
            if (!record || record.version !== 1 || typeof record.baseline !== 'string'
                || !sameShape(record.data, example)) {
                throw new Error('The saved browser draft is invalid. Discard it to continue.');
            }
            this.records[key] = record;
            return this.records[key];
        },
        write(key, baseline, data) {
            const record = { version: 1, baseline, data };
            localStorage.setItem(key, JSON.stringify(record));
            this.records[key] = record;
        },
        remove(key) {
            localStorage.removeItem(key);
            delete this.records[key];
        },
    },
});
