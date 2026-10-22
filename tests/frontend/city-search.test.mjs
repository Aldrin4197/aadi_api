import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { searchCities, cityLabel } from '../../resources/js/Components/Marketplace/citySearch.mjs';
const { cities } = JSON.parse(readFileSync(new URL('../../public/data/ph-cities.json', import.meta.url)));

test('real Philippine cities can be found without any listing records', () => {
  for (const term of ['Catbalogan', 'Calbayog', 'Manila', 'Cebu', 'Tacloban', 'Davao', 'Quezon City']) {
    const found = searchCities(cities, term);
    assert.ok(found.length, term);
    assert.ok(found.some(c => c.name.toLowerCase().includes(term.toLowerCase())), term);
  }
  assert.ok(cities.length > 1600);
  for (const city of cities) {
    assert.ok(Number.isFinite(city.lat) && city.lat >= -90 && city.lat <= 90);
    assert.ok(Number.isFinite(city.lng) && city.lng >= -180 && city.lng <= 180);
    assert.ok(city.id && city.name && city.region);
  }
  assert.equal(new Set(cities.map(c=>c.id)).size, cities.length);
});

test('city search handles accents, aliases, province qualifiers and no matches', () => {
  assert.ok(searchCities(cities, 'Paranaque').some(c=>c.name.includes('Parañaque')));
  assert.ok(searchCities(cities, 'City of Calbayog').some(c=>c.name.includes('Calbayog')));
  const results = searchCities(cities, 'San Jose');
  assert.ok(new Set(results.map(cityLabel)).size > 1);
  assert.ok(searchCities(cities, 'San Jose Antique').every(c => c.province.includes('Antique')));
  assert.ok(searchCities(cities, 'San Jose Antique').length);
  assert.deepEqual(searchCities(cities, ''), []);
  assert.deepEqual(searchCities(cities, 'X'), []);
  assert.deepEqual(searchCities(cities, 'not-a-real-city-xyz'), []);
  assert.ok(searchCities(cities, 'San').length <= 20);
});
