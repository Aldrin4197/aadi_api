import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { resolveHeroCategory } from '../../resources/js/Components/Marketplace/aadiHeroScene.js';

test('hero categories map to the supplied scene poses', () => {
    assert.equal(resolveHeroCategory('spaces'), 'spaces');
    assert.equal(resolveHeroCategory('haul'), 'vehicles');
    assert.equal(resolveHeroCategory('raketeer'), 'rakets');
});

test('hero preserves search and uses a lazy scene with fallback and cleanup', async () => {
    const hero = await readFile(new URL('../../resources/js/Components/Marketplace/HomeHero.vue', import.meta.url), 'utf8');
    const scene = await readFile(new URL('../../resources/js/Components/Marketplace/AadiHeroScene.vue', import.meta.url), 'utf8');
    const engine = await readFile(new URL('../../resources/js/Components/Marketplace/aadiHeroScene.js', import.meta.url), 'utf8');
    const styles = await readFile(new URL('../../resources/css/home-landing.css', import.meta.url), 'utf8');
    assert.match(hero, /:category="active"/);
    assert.match(hero, /emit\('search'/);
    assert.match(hero, /emit\('locate'/);
    assert.match(scene, /await import\('\.\/aadiHeroScene\.js'\)/);
    assert.match(scene, /v-if="!ready"/);
    assert.match(scene, /onBeforeUnmount\(\(\) => \{ disposed = true; controller\?\.dispose\(\)/);
    assert.match(engine, /visibilitychange.*syncRendering/);
    assert.match(engine, /prefers-reduced-motion: reduce/);
    assert.match(engine, /animateActivity\(dt,!ambientPaused&&!reduceMotion\.matches&&!orbit\.dragging,currentCategory\)/);
    assert.match(engine, /Paint roller/);
    assert.match(engine, /Computer monitor/);
    assert.match(engine, /Television frame/);
    const figureStyles = styles.match(/\.hero-scene\s*\{([^}]*)\}/)?.[1];
    assert.ok(figureStyles);
    assert.doesNotMatch(figureStyles, /background|border|box-shadow|overflow/);
    assert.doesNotMatch(styles, /max-height:210px/);
});
