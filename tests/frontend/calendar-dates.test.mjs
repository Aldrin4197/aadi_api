import { test } from 'node:test';
import assert from 'node:assert/strict';
import { calendarDays, shiftMonth, monthLabel } from '../../resources/js/Components/calendarDates.mjs';

test('calendar covers month boundaries and leap years without timezone shifts', () => {
    assert.equal(shiftMonth('2024-12', 1), '2025-01');
    assert.equal(shiftMonth('2025-01', -1), '2024-12');
    const days = calendarDays('2024-02');
    assert.equal(days.filter(Boolean).length, 29);
    assert.equal(days[4], '2024-02-01');
    assert.equal(days.at(-1), '2024-02-29');
    assert.equal(monthLabel('2024-02'), 'February 2024');
});
