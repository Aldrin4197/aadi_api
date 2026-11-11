export function shiftMonth(month, offset) {
    const [year, value] = month.split('-').map(Number);
    const date = new Date(Date.UTC(year, value - 1 + offset, 1));
    return `${date.getUTCFullYear()}-${String(date.getUTCMonth() + 1).padStart(2, '0')}`;
}

export function calendarDays(month) {
    const [year, value] = month.split('-').map(Number);
    const first = new Date(Date.UTC(year, value - 1, 1));
    const length = new Date(Date.UTC(year, value, 0)).getUTCDate();
    return [
        ...Array(first.getUTCDay()).fill(null),
        ...Array.from({ length }, (_, index) => `${month}-${String(index + 1).padStart(2, '0')}`),
    ];
}

export function monthLabel(month) {
    const [year, value] = month.split('-').map(Number);
    return new Intl.DateTimeFormat('en-PH', { month: 'long', year: 'numeric', timeZone: 'UTC' }).format(new Date(Date.UTC(year, value - 1, 1)));
}

export function todayInTimezone(timeZone) {
    const parts = new Intl.DateTimeFormat('en-CA', { timeZone, year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(new Date());
    const value = type => parts.find(part => part.type === type).value;
    return `${value('year')}-${value('month')}-${value('day')}`;
}
