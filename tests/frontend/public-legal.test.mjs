import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import { parsePublicDocument } from '../../resources/js/Pages/Legal/parsePublicDocument.mjs';

for (const [file, count] of [['public-privacy.txt', 17], ['public-terms.txt', 19]]) {
    test(`${file} preserves all numbered sections and their content`, async () => {
        const source = await readFile(new URL(`../../resources/legal/${file}`, import.meta.url), 'utf8');
        const { effectiveDate, preamble, sections } = parsePublicDocument(source);
        assert.equal(effectiveDate, 'November 30, 2026');
        assert.equal(sections.length, count);
        assert.deepEqual(sections.map(section => Number(section.number)), Array.from({ length: count }, (_, index) => index + 1));

        const rendered = [`Effective date: ${effectiveDate}`, ...preamble, ...sections.flatMap(section => [
            `${section.number}. ${section.title}`,
            ...section.blocks.flatMap(block => block.type === 'list' ? block.items.map(item => `- ${item}`) : [block.text]),
        ])].join('\n');
        const normalizedSource = source.replace(/\r\n/g, '\n');
        const original = normalizedSource.slice(normalizedSource.indexOf('\n') + 1);
        assert.equal(rendered.replace(/\s+/g, ' ').trim(), original.replace(/\s+/g, ' ').trim());
        assert.ok(sections.at(-1).blocks[0].text.includes('\nEmail: '));
    });
}

test('rejects a document without a date or sections', () => {
    assert.throws(() => parsePublicDocument('Unfinished policy'), /missing its date or sections/);
});
