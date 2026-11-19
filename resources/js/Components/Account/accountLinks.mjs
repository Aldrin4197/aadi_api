export const accountIcons = {
    map: 'M9 3l6 3 6-3v15l-6 3-6-3-6 3V6zM9 3v15M15 6v15',
    saved: 'M6 3h12v18l-6-4-6 4z',
    requests: 'M9 4h6l1 2h3v15H5V6h3zM9 4a3 3 0 006 0M8 11l1 1 2-2M13 11h3M8 16l1 1 2-2M13 16h3',
    messages: 'M4 4h16v13H8l-4 3zM8 9h8M8 13h5',
    calendar: 'M4 5h16v16H4zM8 3v4M16 3v4M4 10h16M8 14h2M14 14h2M8 18h2',
    profile: 'M16 7a4 4 0 11-8 0 4 4 0 018 0M4 21v-2a8 8 0 0116 0v2',
    settings: 'M12 8a4 4 0 100 8 4 4 0 000-8zM12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42',
    roles: 'M9 6V3h6v3M3 6h18v14H3zM3 11l9 4 9-4M12 12v4',
    dashboard: 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z',
    listings: 'M3 10l9-7 9 7M5 9v12h14V9M9 21v-8h6v8',
    published: 'm5 12 4 4L19 6',
    drafts: 'M6 3h9l4 4v14H6zM14 3v5h5M9 12h6M9 16h6',
    previous: 'm15 18-6-6 6-6',
    next: 'm9 18 6-6-6-6',
    spaces: 'M3 11l9-8 9 8M5 9v12h14V9M9 21v-8h6v8',
    haul: 'M3 5h12v12H3zM15 9h4l3 4v4h-7M5 17v3h3v-3M17 17v3h3v-3',
    raketeer: 'M3 7h18v14H3zM8 7V3h8v4M3 12l9 4 9-4',
};

/**
 * Everyone gets the map, saved listings and profile. Provider tools (dashboard, listing
 * management) appear only once a provider role has been approved.
 */
export function accountLinks({ isProvider = false } = {}) {
    const links = [
        { section: 'discover', label: 'Map', href: '/explore', icon: accountIcons.map },
        { section: 'account', label: 'Saved listings', href: '/saved-listings', icon: accountIcons.saved },
        { section: 'account', label: 'Inbox', href: '/messages', activePaths: ['/messages', '/requests'], icon: accountIcons.messages },
        { section: 'account', label: 'Calendar', href: '/calendar', icon: accountIcons.calendar },
        { section: 'account', label: 'Profile', href: '/profile', icon: accountIcons.profile },
        { section: 'account', label: 'Settings', href: '/settings', icon: accountIcons.settings },
        { section: 'account', label: isProvider ? 'Provider roles' : 'Become a provider', href: '/become-provider', icon: accountIcons.roles },
    ];
    if (isProvider) {
        links.push(
            { section: 'provider', label: 'Dashboard', href: '/dashboard', icon: accountIcons.dashboard },
            { section: 'provider', label: 'My listings', href: '/my-listings', icon: accountIcons.listings },
            { section: 'provider', label: 'Rentals & jobs', href: '/provider/records', icon: accountIcons.requests },
        );
    }
    return links;
}

export function isActiveLink(path, link) {
    return (link.activePaths || [link.href]).some(href => path === href || path.startsWith(href + '/'))
        || (link.href === '/become-provider' && path.startsWith('/provider-applications/'));
}

export function mobileAccountLinks(options = {}) {
    const links = accountLinks(options);
    const isProvider = options.isProvider ?? false;
    return [
        ['/explore', 'Map'],
        ['/saved-listings', 'Saved'],
        ['/messages', 'Inbox'],
        ['/calendar', 'Calendar'],
    ].map(([href, label]) => ({ ...links.find(item => item.href === href), label })).concat({
        section: 'quick',
        label: isProvider ? 'Manage' : 'More',
        icon: isProvider ? accountIcons.roles : accountIcons.dashboard,
        isMenu: true,
    });
}

export function mobileQuickLinks(options = {}) {
    const isProvider = options.isProvider ?? false;
    const primaryHrefs = new Set(['/explore', '/saved-listings', '/messages', '/calendar']);
    return accountLinks({ isProvider })
        .filter(link => !primaryHrefs.has(link.href))
        .map(link => isProvider && link.href === '/become-provider' ? { ...link, section: 'provider' } : link);
}
