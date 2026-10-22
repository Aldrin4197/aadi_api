# Philippine city search directory

`ph-cities.json` is derived from the [GeoNames Philippine country extract](https://download.geonames.org/export/dump/PH.zip), retrieved 2026-10-22. GeoNames data is licensed under [CC BY 4.0](https://creativecommons.org/licenses/by/4.0/). Attribution: [GeoNames](https://www.geonames.org/). Source documentation: https://download.geonames.org/export/dump/readme.txt.

The directory includes ADM3 city/municipality records and the national capital. City names, aliases, province/district and region are retained. Coordinates use a matching administrative seat or named settlement when available, otherwise the administrative point. They are search centers, not boundaries or verified street addresses. Coverage and administrative labels reflect the source snapshot; this is not an official PSGC register.

Regenerate with `python3 scripts/build-city-directory.py`. No user search queries are sent to GeoNames. The browser downloads this directory only when the city picker is opened, then searches locally. Normal hosting caching applies; the directory is not a database seeder and requires no API key.
