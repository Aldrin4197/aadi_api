# Landing structure and provider badges

The hero now introduces spaces, vehicle rentals and rakets with a category illustration switcher and a primary nearby-search CTA. The existing full-width live map remains in place. Below it: benefits/problem framing, category discovery, three-step guidance, featured inventory, configured community reviews, available downloads, provider CTA and FAQ. Existing footer links remain intact. The download section has an explicit dark-teal background to preserve contrast when theme variables are absent.

ProviderBadge is shared across listing details, previews, saved items, provider profiles and admin inspections. Approved roles alone receive the supplied outline role icon and filled verification check; email verification stays a separate user icon. Status, personalized recommendation and Aadi Plus icons use the matching supplied badge components. Accessible labels and tooltips explain each icon without visible badge copy.

No backend, permissions or database changes. No invented testimonials, partners, ratings or store links. Existing configured review and download sources remain authoritative. Hero visuals are labeled illustrations, not product screenshots. Brand colors and Quicksand are preserved; Vue 3 and Tailwind 4 remain in use.

Validation: production build and nine frontend tests passed. Browser component fixtures at 1440px/390px checked the visual switcher, category action, FAQ, section anchors, badge labels/approval gating and overflow. The live map was mocked for these component checks; map behavior was not changed. PHP tests not applicable to this UI-only change.

## Search visibility

The public home and Explore pages provide server-delivered metadata, canonical URLs and social preview tags. Filtered searches are marked `noindex`; user profiles and authenticated pages are also `noindex`. Full listing details remain behind login and are not included in the sitemap. The dynamic `/robots.txt` links to `/sitemap.xml`, which contains only the public home and Explore URLs.

Set `APP_URL` to the canonical production origin, including `https://`, and refresh Laravel's configuration cache after changing it. The sitemap and metadata use this value, not the incoming host. The site still renders its main content in JavaScript; a no-JavaScript summary is provided, but full server-side rendering would require a separate deployment change. Submit the sitemap in search-engine webmaster tools after deployment; indexing is not guaranteed.
