import { getCountryCallingCode, parsePhoneNumberFromString } from 'libphonenumber-js';

export function digitsOnly(value) {
    return value.replace(/[^0-9]/g, '');
}

export function splitPhone(value) {
    const parsed = value?.startsWith('+') ? parsePhoneNumberFromString(value) : null;
    return parsed?.country
        ? { country: parsed.country, number: parsed.nationalNumber }
        : { country: 'PH', number: value || '' };
}

export function joinPhone(country, number) {
    const raw = number.trim();
    if (!raw) return '';
    if (raw.startsWith('+')) return raw;
    return parsePhoneNumberFromString(raw, country)?.number || `+${getCountryCallingCode(country)} ${raw}`;
}
