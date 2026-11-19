export function parsePublicDocument(content) {
    const text = content.replace(/\r\n/g, '\n').trim();
    const effectiveDate = text.match(/^Effective date: (.+)$/m)?.[1];
    const preamble = [];
    const sections = [];

    for (const [index, block] of text.split(/\n\s*\n/).entries()) {
        const heading = block.match(/^(\d+)\.\s+([^\n]+)$/);
        if (heading) {
            sections.push({ number: heading[1], title: heading[2], blocks: [] });
            continue;
        }
        if (!sections.length) {
            if (index > 0) preamble.push(...block.split('\n').filter(line => !line.startsWith('Effective date: ')));
            continue;
        }

        const lines = block.split('\n');
        if (lines.every(line => line.startsWith('- '))) {
            sections.at(-1).blocks.push({ type: 'list', items: lines.map(line => line.slice(2)) });
        } else {
            sections.at(-1).blocks.push({ type: 'paragraph', text: lines.join('\n') });
        }
    }

    if (!effectiveDate || !sections.length) throw new Error('The public legal document is missing its date or sections.');
    return { effectiveDate, preamble, sections };
}
