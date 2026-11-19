import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { accountLinks, isActiveLink, mobileAccountLinks, mobileQuickLinks } from '../../resources/js/Components/Account/accountLinks.mjs';

const labels = links => links.map(link => link.label);

test('everyone gets the map, saved listings, profile and a way to become a provider', () => {
    for (const options of [undefined, {}, { isProvider: false }]) {
        const links = accountLinks(options);
        assert.deepEqual(labels(links), ['Map', 'Saved listings', 'Inbox', 'Calendar', 'Profile', 'Settings', 'Become a provider']);
        assert.equal(links[0].href, '/explore');
        assert.ok(links.every(link => link.section !== 'provider'));
    }
});

test('provider tools appear only for approved providers', () => {
    const links = accountLinks({ isProvider: true });
    assert.deepEqual(labels(links), ['Map', 'Saved listings', 'Inbox', 'Calendar', 'Profile', 'Settings', 'Provider roles', 'Dashboard', 'My listings', 'Rentals & jobs']);
    assert.deepEqual(links.filter(link => link.section === 'provider').map(link => link.href), ['/dashboard', '/my-listings', '/provider/records']);
});

test('active link matching covers nested pages without matching lookalike paths', () => {
    const [map, saved, inbox, calendar, , settings, roles] = accountLinks();
    assert.equal(isActiveLink('/explore', map), true);
    assert.equal(isActiveLink('/saved-listings', saved), true);
    assert.equal(isActiveLink('/saved-listings-extra', saved), false);
    assert.equal(isActiveLink('/requests', inbox), true);
    assert.equal(isActiveLink('/messages/123', inbox), true);
    assert.equal(isActiveLink('/requests-extra', inbox), false);
    assert.equal(isActiveLink('/messages-extra', inbox), false);
    assert.equal(isActiveLink('/calendar', calendar), true);
    assert.equal(isActiveLink('/provider-applications/haul', roles), true);
    assert.equal(isActiveLink('/profile', roles), false);
    assert.equal(isActiveLink('/settings', settings), true);
});

test('mobile tabs keep core destinations visible and expose role-aware quick navigation', () => {
    for (const isProvider of [false, true]) {
        const links = mobileAccountLinks({ isProvider });
        assert.deepEqual(labels(links), ['Map', 'Saved', 'Inbox', 'Calendar', isProvider ? 'Manage' : 'More']);
        assert.deepEqual(links.slice(0, 4).map(link => link.href), ['/explore', '/saved-listings', '/messages', '/calendar']);
        assert.equal(isActiveLink('/requests', links[2]), true);
        assert.equal(isActiveLink('/messages/123', links[2]), true);
        assert.ok(links.every(link => link.icon));
        assert.equal(links[4].isMenu, true);

        const quickLinks = mobileQuickLinks({ isProvider });
        assert.deepEqual(quickLinks.filter(link => link.section === 'account').map(link => link.href), isProvider
            ? ['/profile', '/settings']
            : ['/profile', '/settings', '/become-provider']);
        assert.deepEqual(quickLinks.filter(link => link.section === 'provider').map(link => link.href), isProvider
            ? ['/become-provider', '/dashboard', '/my-listings', '/provider/records']
            : []);
        if (isProvider) {
            assert.equal(quickLinks.some(link => isActiveLink('/my-listings/spaces', link)), true);
            assert.equal(quickLinks.some(link => isActiveLink('/provider-applications/haul', link)), true);
        }
    }
});

test('inbox sections remain available on requests, messages, and message threads', async () => {
    const root = new URL('../../resources/js/', import.meta.url);
    const [navigation, requests, messages, thread] = await Promise.all([
        readFile(new URL('Components/Account/InboxNavigation.vue', root), 'utf8'),
        readFile(new URL('Pages/Account/Requests.vue', root), 'utf8'),
        readFile(new URL('Pages/Account/Messages.vue', root), 'utf8'),
        readFile(new URL('Pages/Account/MessageThread.vue', root), 'utf8'),
    ]);

    assert.match(navigation, /href="\/messages".*active === 'messages'/);
    assert.match(navigation, /href="\/requests".*active === 'requests'/);
    assert.match(requests, /<InboxNavigation active="requests"/);
    assert.match(messages, /<InboxNavigation active="messages"/);
    assert.match(thread, /<InboxNavigation active="messages"/);
});
