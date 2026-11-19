<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/MarketplaceLayout.vue';
import { parsePublicDocument } from './parsePublicDocument.mjs';

const props = defineProps({ document: { type: Object, required: true } });
const policy = computed(() => parsePublicDocument(props.document.content));
</script>

<template>
    <Layout>
        <Head :title="document.title" />
        <main class="policy-page">
            <header class="policy-header">
                <p class="policy-eyebrow">Aadi / Legal</p>
                <h1>{{ document.title }}</h1>
                <p class="policy-date">Effective date: {{ policy.effectiveDate }}</p>
                <p v-for="line in policy.preamble" :key="line" class="policy-date">{{ line }}</p>
                <nav class="policy-switch" aria-label="Legal documents">
                    <Link href="/privacy-policy" :aria-current="document.kind === 'privacy' ? 'page' : undefined">Privacy Policy</Link>
                    <Link href="/terms-of-service" :aria-current="document.kind === 'terms' ? 'page' : undefined">Terms of Service</Link>
                    <Link href="/data-deletion" :aria-current="document.kind === 'data-deletion' ? 'page' : undefined">Data Deletion</Link>
                </nav>
            </header>

            <div class="policy-layout">
                <aside>
                    <details class="policy-contents">
                        <summary>On this page</summary>
                        <nav aria-label="Document sections">
                            <ol>
                                <li v-for="section in policy.sections" :key="section.number">
                                    <a :href="`#section-${section.number}`">{{ section.title }}</a>
                                </li>
                            </ol>
                        </nav>
                    </details>
                </aside>
                <article aria-label="Policy content">
                    <section v-for="section in policy.sections" :id="`section-${section.number}`" :key="section.number" class="policy-section">
                        <h2><span>{{ section.number.padStart(2, '0') }}</span>{{ section.title }}</h2>
                        <template v-for="(block, index) in section.blocks" :key="index">
                            <ul v-if="block.type === 'list'">
                                <li v-for="(item, itemIndex) in block.items" :key="itemIndex">{{ item }}</li>
                            </ul>
                            <p v-else>{{ block.text }}</p>
                        </template>
                    </section>
                </article>
            </div>
        </main>
        <template #footer>
            <footer class="policy-footer">
                <Link href="/">Aadi</Link>
                <nav aria-label="Legal footer">
                    <Link href="/privacy-policy">Privacy Policy</Link>
                    <Link href="/terms-of-service">Terms of Service</Link>
                    <Link href="/data-deletion">Data Deletion</Link>
                    <a href="mailto:info@aadirentals.com">Contact</a>
                </nav>
            </footer>
        </template>
    </Layout>
</template>

<style scoped>
.policy-page { max-width:1200px; margin:0 auto; padding:64px 32px 100px; }
.policy-header { max-width:840px; margin:0 0 56px; }
.policy-eyebrow { margin:0 0 12px; color:var(--teal); font-size:12px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
.policy-header h1 { margin:0 0 14px; }
.policy-date { margin:0; color:var(--muted); font-size:14px; }
.policy-switch { display:flex; flex-wrap:wrap; gap:24px; margin-top:32px; border-bottom:1px solid var(--line); }
.policy-switch a { padding:0 0 12px; color:var(--muted); font-size:14px; font-weight:700; }
.policy-switch a[aria-current=page] { color:var(--teal); border-bottom:2px solid var(--teal); }
.policy-layout { display:grid; grid-template-columns:230px minmax(0, 720px); gap:64px; align-items:start; }
.policy-contents { position:sticky; top:24px; color:var(--muted); font-size:13px; }
.policy-contents summary { cursor:pointer; color:var(--ink); font-weight:700; }
.policy-contents ol { display:grid; gap:10px; padding:16px 0 0 18px; margin:0; }
.policy-contents a:hover { color:var(--teal); text-decoration:underline; }
.policy-section { scroll-margin-top:24px; padding:0 0 30px; margin:0 0 30px; border-bottom:1px solid var(--line); }
.policy-section h2 { display:flex; gap:14px; align-items:baseline; margin:0 0 18px; font-size:18px; line-height:1.35; text-transform:none; }
.policy-section h2 span { color:var(--teal); font-size:12px; font-variant-numeric:tabular-nums; }
.policy-section p, .policy-section li { color:#405c57; font-size:15px; line-height:1.8; overflow-wrap:anywhere; }
.policy-section p { margin:0 0 16px; white-space:pre-line; }
.policy-section ul { display:grid; gap:8px; padding-left:24px; margin:0 0 16px; list-style:disc; }
.policy-section :last-child { margin-bottom:0; }
.policy-footer { display:flex; justify-content:space-between; gap:24px; padding:26px max(4%, calc((100% - 1136px) / 2)); border-top:1px solid var(--line); font-size:13px; font-weight:700; }
.policy-footer nav { display:flex; flex-wrap:wrap; gap:20px; color:var(--muted); }
.policy-footer a:hover { color:var(--teal); text-decoration:underline; }
@media (max-width:760px) {
    .policy-page { padding:40px 20px 70px; }
    .policy-header { margin-bottom:32px; }
    .policy-layout { grid-template-columns:minmax(0, 1fr); gap:32px; }
    .policy-contents { position:static; }
    .policy-footer { flex-direction:column; padding:24px 20px 90px; }
}
</style>
