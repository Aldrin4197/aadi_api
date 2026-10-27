# Aadi PWA

Deploy `public/sw.js`, `public/manifest.webmanifest`, `public/offline.html` and
`public/pwa/` alongside the normal production Vite build. Serve the app at the
origin root over HTTPS (localhost is allowed for testing). Serve sw.js as
JavaScript with Cache-Control: no-cache, and the manifest as
application/manifest+json. Do not rewrite these static URLs to Laravel HTML.

Registration runs only in production builds and secure contexts. Chrome/Edge
can offer installation through the browser menu or the Aadi install notice when
the browser emits its install event. On iOS Safari use Share → Add to Home Screen.
Installation availability depends on the device/browser; it remains a web app.

Only a generic offline page and its icons are precached. No session HTML,
Inertia responses, APIs, listings, uploads or map tiles are cached. Online
navigation is always network-first with no response storage. When navigation
fails, the generic reconnect page is shown. Existing open pages show an offline
notice; search, authentication and changes require connectivity. This does not
add offline CRUD, background sync, push notifications or offline maps.

Updates wait until existing tabs close or the user selects Reload to update.
The notice tells users to save their work first. Increment the cache version in
sw.js when offline assets change. Activation removes only old aadi-offline caches.
The existing Aadi logo is used for 192/512 PNG, maskable and Apple touch icons.

Verify using a production build: inspect the manifest and service worker in
browser developer tools, install, reopen in standalone mode, load once online,
then navigate offline and retry after reconnecting. Confirm Cache Storage contains
only the five allowlisted files. Test on a real iPhone/Android device before release.
Run `npm run test:frontend` for manifest and service-worker policy checks.
