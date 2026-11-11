import { test } from 'node:test';
import assert from 'node:assert/strict';
import { accountLinks, isActiveLink, mobileAccountLinks } from '../../resources/js/Components/Account/accountLinks.mjs';

const labels = links => links.map(link => link.label);

test('everyone gets the map, saved listings, profile and a way to become a provider', () => {
    for (const options of [undefined, {}, { isProvider: false }]) {
        const links = accountLinks(options);
        assert.deepEqual(labels(links), ['Map', 'Saved listings', 'Requests', 'Calendar', 'Profile', 'Settings', 'Become a provider']);
        assert.equal(links[0].href, '/explore');
        assert.ok(links.every(link => link.section !== 'provider'));
    }
});

test('provider tools appear only for approved providers', () => {
    const links = accountLinks({ isProvider: true });
    assert.deepEqual(labels(links), ['Map', 'Saved listings', 'Requests', 'Calendar', 'Profile', 'Settings', 'Provider roles', 'Dashboard', 'My listings']);
    assert.deepEqual(links.filter(link => link.section === 'provider').map(link => link.href), ['/dashboard', '/my-listings']);
});

test('active link matching covers nested pages without matching lookalike paths', () => {
    const [map, saved, requests, calendar, , settings, roles] = accountLinks();
    assert.equal(isActiveLink('/explore', map), true);
    assert.equal(isActiveLink('/saved-listings', saved), true);
    assert.equal(isActiveLink('/saved-listings-extra', saved), false);
    assert.equal(isActiveLink('/requests', requests), true);
    assert.equal(isActiveLink('/calendar', calendar), true);
    assert.equal(isActiveLink('/provider-applications/haul', roles), true);
    assert.equal(isActiveLink('/profile', roles), false);
    assert.equal(isActiveLink('/settings', settings), true);
});

test('mobile tabs use existing destinations for both regular users and providers', () => {
    for (const isProvider of [false, true]) {
        const links = mobileAccountLinks({ isProvider });
        assert.deepEqual(labels(links), ['Map', 'Saved', 'Requests', 'Roles', 'Settings']);
        assert.deepEqual(links.map(link => link.href), ['/explore', '/saved-listings', '/requests', '/become-provider', '/settings']);
        assert.ok(links.every(link => link.icon));
        assert.equal(isActiveLink('/provider-applications/haul', links[3]), true);
    }
});
