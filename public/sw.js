/* Bump this version whenever the offline page or its assets change. */
const CACHE = 'aadi-offline-v1';
const ASSETS = ['/offline.html', '/pwa/icon-192.png', '/pwa/icon-512.png', '/pwa/maskable-512.png', '/pwa/apple-touch-icon.png'];
self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE).then(cache => cache.addAll(ASSETS)));
    // Updates wait for the user's consent so open forms are not reloaded.
});
self.addEventListener('activate', event => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.filter(key => key.startsWith('aadi-offline-') && key !== CACHE).map(key => caches.delete(key)));
        await self.clients.claim();
    })());
});
self.addEventListener('message', event => {
    if (event.data?.type === 'ACTIVATE_UPDATE') self.skipWaiting();
});
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);
    if (request.method !== 'GET' || url.origin !== self.location.origin) return;
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
        return;
    }
    // Explicit static allowlist only. Never cache HTML responses, API calls,
    // Inertia payloads, uploads, map tiles, login, account or admin data.
    if (ASSETS.includes(url.pathname) && !url.search) {
        event.respondWith(caches.match(request).then(cached => cached || fetch(request)));
    }
});
