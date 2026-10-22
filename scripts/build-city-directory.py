"""Refresh the Philippine city/municipality directory from GeoNames CC BY 4.0 data."""
import io
import json
import re
import urllib.request
import zipfile
from datetime import date
from pathlib import Path

SOURCE = 'https://download.geonames.org/export/dump/PH.zip'

def normalized(name):
    return re.sub(r'[^a-z0-9]', '', re.sub(r'\b(city|of|municipality)\b', '', name.lower()))

def build(raw):
    rows = [row.split('\t') for row in raw.splitlines()]
    regions = {r[10]: r[1] for r in rows if r[7] == 'ADM1'}
    provinces = {(r[10], r[11]): re.sub(r'^Province of ', '', r[1]) for r in rows if r[7] == 'ADM2'}
    settlements = {}
    for row in rows:
        if row[6] == 'P':
            settlements.setdefault(tuple(row[10:13]), []).append(row)
    cities = []
    for row in rows:
        if row[7] != 'ADM3':
            continue
        name = re.sub(r'^(City|Municipality) of ', '', row[1])
        candidates = [r for r in settlements.get(tuple(row[10:13]), [])
                      if r[7].startswith('PPLA') or r[7] == 'PPLC' or normalized(r[1]) == normalized(name)]
        candidates.sort(key=lambda r: (r[7] == 'PPLC', r[7].startswith('PPLA'), int(r[14] or 0)), reverse=True)
        point = candidates[0] if candidates else row
        aliases = set([row[1], row[2], point[1], point[2]] + row[3].split(',') + point[3].split(','))
        aliases.discard('')
        cities.append({'id': row[0], 'name': name,
                       'province': provinces.get(tuple(row[10:12]), ''), 'region': regions.get(row[10], ''),
                       'lat': float(point[4]), 'lng': float(point[5]),
                       'aliases': sorted(aliases - {name})})
    # Manila is represented as several administrative districts; retain the national capital as a city choice.
    for row in rows:
        if row[7] == 'PPLC' and not any(normalized(c['name']) == normalized(row[1]) for c in cities):
            cities.append({'id': row[0], 'name': row[1], 'province': provinces.get(tuple(row[10:12]), ''),
                           'region': regions.get(row[10], ''), 'lat': float(row[4]), 'lng': float(row[5]),
                           'aliases': sorted(set(filter(None, row[3].split(','))))})
    return {'source': SOURCE, 'license': 'CC BY 4.0', 'updated': date.today().isoformat(),
            'cities': sorted(cities, key=lambda c: (c['name'], c['province']))}

if __name__ == '__main__':
    with urllib.request.urlopen(SOURCE, timeout=60) as response:
        archive = zipfile.ZipFile(io.BytesIO(response.read()))
    directory = build(archive.read('PH.txt').decode('utf-8'))
    target = Path(__file__).resolve().parents[1] / 'public/data/ph-cities.json'
    target.write_text(json.dumps(directory, ensure_ascii=False, separators=(',', ':')) + '\n', encoding='utf-8')
    print(f"Wrote {len(directory['cities'])} cities/municipalities to {target}")
