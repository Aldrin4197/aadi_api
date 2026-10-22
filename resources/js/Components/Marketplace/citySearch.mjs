export function normalizeCity(value) {
  return String(value || '').normalize('NFD').replace(/\p{M}/gu, '').toLowerCase().replace(/[^\p{L}\p{N}]+/gu, ' ').trim();
}
export function cityLabel(city) {
  return [...new Set([city.name, city.province, city.region].filter(Boolean))].join(', ');
}
export function searchCities(cities, query, limit = 20) {
  const text = normalizeCity(query);
  if (text.length < 2) return [];
  const tokens = text.split(/\s+/).filter(t => !['city', 'of', 'municipality'].includes(t));
  if (!tokens.length) return [];
  return cities.map(city => {
    const names = [city.name, ...(city.aliases || [])].map(normalizeCity);
    const area = normalizeCity(cityLabel(city));
    if (!tokens.every(token => area.includes(token) || names.some(n => n.includes(token)))) return null;
    const score = names.includes(text) ? 0 : names.some(n => n.startsWith(text)) ? 1 : 2;
    return {city, score};
  }).filter(Boolean).sort((a,b) => a.score-b.score || cityLabel(a.city).localeCompare(cityLabel(b.city)))
    .slice(0, limit).map(({city}) => city);
}
