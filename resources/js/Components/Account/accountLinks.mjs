const icons = {
    map: 'M9 3l6 3 6-3v15l-6 3-6-3-6 3V6zM9 3v15M15 6v15',
    saved: 'M6 3h12v18l-6-4-6 4z',
    requests: 'M4 4h16v13H8l-4 3zM8 8h8M8 12h6',
    calendar: 'M4 5h16v16H4zM8 3v4M16 3v4M4 10h16M8 14h2M14 14h2M8 18h2',
    profile: 'M16 7a4 4 0 11-8 0 4 4 0 018 0M4 21v-2a8 8 0 0116 0v2',
    settings: 'M12 8a4 4 0 100 8 4 4 0 000-8zM12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42',
    roles: 'M9 6V3h6v3M3 6h18v14H3zM3 11l9 4 9-4M12 12v4',
    dashboard: 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z',
    listings: 'M3 10l9-7 9 7M5 9v12h14V9M9 21v-8h6v8',
};

/**
 * Everyone gets the map, saved listings and profile. Provider tools (dashboard, listing
 * management) appear only once a provider role has been approved.
 */
export function accountLinks({ isProvider = false } = {}) {
    const links = [
        { section: 'discover', label: 'Map', href: '/explore', icon: icons.map },
        { section: 'account', label: 'Saved listings', href: '/saved-listings', icon: icons.saved },
        { section: 'account', label: 'Requests', href: '/requests', icon: icons.requests },
        { section: 'account', label: 'Calendar', href: '/calendar', icon: icons.calendar },
        { section: 'account', label: 'Profile', href: '/profile', icon: icons.profile },
        { section: 'account', label: 'Settings', href: '/settings', icon: icons.settings },
        { section: 'account', label: isProvider ? 'Provider roles' : 'Become a provider', href: '/become-provider', icon: icons.roles },
    ];
    if (isProvider) {
        links.push(
            { section: 'provider', label: 'Dashboard', href: '/dashboard', icon: icons.dashboard },
            { section: 'provider', label: 'My listings', href: '/my-listings', icon: icons.listings },
        );
    }
    return links;
}

export function isActiveLink(path, link) {
    return path === link.href || path.startsWith(link.href + '/')
        || (link.href === '/become-provider' && path.startsWith('/provider-applications/'));
}

export function mobileAccountLinks(options) {
    const links = accountLinks(options);
    return ['/explore', '/saved-listings', '/requests', '/become-provider', '/settings'].map(href => {
        const link = links.find(item => item.href === href);
        const label = { '/explore': 'Map', '/saved-listings': 'Saved', '/requests': 'Requests', '/become-provider': 'Roles', '/settings': 'Settings' }[href];
        return { ...link, label };
    });
}
