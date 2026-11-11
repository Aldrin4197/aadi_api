import { readFile } from 'node:fs/promises';
import { test } from 'node:test';
import assert from 'node:assert/strict';

test('phone entry uses the numeric keyboard and all contact forms share it', async () => {
    const component = await readFile(new URL('../../resources/js/Components/PhoneInput.vue', import.meta.url), 'utf8');
    assert.match(component, /type="tel"/);
    assert.match(component, /inputmode="numeric"/);
    assert.match(component, /setCustomValidity/);
    assert.match(component, /@input="inputNumber"/);
    assert.match(component, /digitsOnly\(raw\)/);
    for (const path of [
        '../../resources/js/Pages/Profile/Partials/UpdateProfileInformationForm.vue',
        '../../resources/js/Pages/Onboarding/ProviderApplication.vue',
        '../../resources/js/Pages/Marketplace/ProviderApply.vue',
    ]) {
        const form = await readFile(new URL(path, import.meta.url), 'utf8');
        assert.match(form, /<PhoneInput\b/);
    }
});
