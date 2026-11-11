import { test } from 'node:test';
import assert from 'node:assert/strict';
import { currentToast, dismissToast, showToast } from '../../resources/js/Components/toast.mjs';

test('toast notifications can be replaced and dismissed', () => {
    showToast('Saved');
    assert.deepEqual(currentToast.value, { message: 'Saved', kind: 'success' });
    showToast('Try again', 'error');
    assert.deepEqual(currentToast.value, { message: 'Try again', kind: 'error' });
    dismissToast();
    assert.equal(currentToast.value, null);
});
