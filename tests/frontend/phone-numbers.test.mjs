import { test } from 'node:test';
import assert from 'node:assert/strict';
import { digitsOnly, joinPhone, splitPhone } from '../../resources/js/Components/phoneNumbers.mjs';

test('phone entry keeps only digits and preserves leading zeroes', () => {
    assert.equal(digitsOnly('09a1 7-12.3'), '0917123');
    assert.equal(digitsOnly('letters'), '');
    assert.equal(digitsOnly('00123'), '00123');
});

test('phone numbers use the selected international dialing code', () => {
    assert.equal(joinPhone('PH', '09171234567'), '+639171234567');
    assert.equal(joinPhone('GB', '020 7946 0018'), '+442079460018');
    assert.equal(joinPhone('US', ''), '');
    assert.equal(joinPhone('PH', '+442079460018'), '+442079460018');
});

test('existing national numbers are preserved until edited and international numbers reopen correctly', () => {
    assert.deepEqual(splitPhone('09171234567'), { country: 'PH', number: '09171234567' });
    assert.deepEqual(splitPhone('+639171234567'), { country: 'PH', number: '9171234567' });
    assert.deepEqual(splitPhone('+442079460018'), { country: 'GB', number: '2079460018' });
});
