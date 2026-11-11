import { readFile } from 'node:fs/promises';
import { test } from 'node:test';
import assert from 'node:assert/strict';

test('verification forms share type-first document selection', async () => {
    const component = await readFile(new URL('../../resources/js/Components/ProviderDocuments.vue', import.meta.url), 'utf8');
    assert.match(component, /v-if="pending"/);
    assert.match(component, /v-if="type" class="document-upload"/);
    assert.match(component, /meta: \{ type: type\.value/);
    assert.match(component, /keptCount\(\) \+ props\.files\.length >= 10/);
    for (const path of ['../../resources/js/Pages/Onboarding/ProviderApplication.vue', '../../resources/js/Pages/Marketplace/ProviderApply.vue']) {
        const form = await readFile(new URL(path, import.meta.url), 'utf8');
        assert.match(form, /<ProviderDocuments\b/);
        assert.match(form, /Finish adding the document or cancel it first/);
    }
});
