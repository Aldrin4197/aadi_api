import assert from 'node:assert/strict';
import { mkdtemp, rm, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { build } from 'vite';

const projectRoot = fileURLToPath(new URL('../../', import.meta.url));

test('compiles Vue and Tailwind without loading a stale PostCSS config', async () => {
    const fixture = await mkdtemp(path.join(projectRoot, 'tests/frontend/.build-fixture-'));
    try {
        await writeFile(path.join(fixture, 'postcss.config.cjs'),
            'throw new Error("Stale PostCSS config must not be loaded");\n');
        await writeFile(path.join(fixture, 'entry.js'),
            'import Demo from "./Demo.vue"; import "../../../resources/css/app.css"; console.log(Demo);\n');
        await writeFile(path.join(fixture, 'Demo.vue'), `
<script setup lang="ts">
const label: string = 'Aadi build check';
</script>
<template><button class="bg-aadi-teal">{{ label }}</button></template>
<style scoped>button { padding: 1rem; }</style>
`);

        const result = await build({
            configFile: path.join(projectRoot, 'vite.config.js'),
            root: fixture,
            publicDir: false,
            logLevel: 'silent',
            build: {
                write: false,
                manifest: false,
                rolldownOptions: { input: path.join(fixture, 'entry.js') },
            },
        });
        const output = (Array.isArray(result) ? result : [result]).flatMap(bundle => bundle.output);
        const javascript = output.filter(item => item.type === 'chunk').map(item => item.code).join('\n');
        const css = output.filter(item => item.type === 'asset' && item.fileName.endsWith('.css'))
            .map(item => String(item.source)).join('\n');
        assert.ok(javascript.includes('Aadi build check'), 'Vue template is compiled into the bundle');
        assert.ok(!javascript.includes('<script setup'), 'raw Vue SFC syntax is not shipped');
        assert.ok(css.includes('.bg-aadi-teal{'), 'Aadi Tailwind utilities are generated');
        assert.ok(css.includes('[data-v-'), 'Vue scoped styles are compiled');
        assert.ok(!/@(?:import\s+["']tailwindcss|theme|source)\b/.test(css), 'Tailwind directives are resolved');
    } finally {
        await rm(fixture, { recursive: true, force: true });
    }
});
