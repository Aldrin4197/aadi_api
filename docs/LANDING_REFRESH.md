# Landing structure and provider badges

The hero now introduces spaces, vehicle rentals and rakets with a category illustration switcher and a primary nearby-search CTA. The existing full-width live map remains in place. Below it: benefits/problem framing, category discovery, three-step guidance, featured inventory, configured community reviews, available downloads, provider CTA and FAQ. Existing footer links remain intact. The download section has an explicit dark-teal background to preserve contrast when theme variables are absent.

ProviderBadge is shared across listing details, previews, saved items, provider profiles and admin inspections/users. Approved roles alone receive the icon + short Landlord / Vehicle Owner / Raketeer label and shield-check. Accessible labels and tooltips explain approval without long badge copy. Application roles have short labels but no approval badge before approval.

No backend, permissions or database changes. No invented testimonials, partners, ratings or store links. Existing configured review and download sources remain authoritative. Hero visuals are labeled illustrations, not product screenshots. Brand colors and Quicksand are preserved; Vue 3 and Tailwind 4 remain in use.

Validation: production build and nine frontend tests passed. Browser component fixtures at 1440px/390px checked the visual switcher, category action, FAQ, section anchors, badge labels/approval gating and overflow. The live map was mocked for these component checks; map behavior was not changed. PHP tests not applicable to this UI-only change.
