// Mirrors the category glyphs in Flutter's tenant/explore_map.dart.
const paths = {
 bedspace:'M3 18V8m0 7h18v5m-18-2h18M6 15v-5h5v5m0-4h7a3 3 0 0 1 3 3v1',
 boardingHouse:'M4 21h16M7 21V3h10v18M13 12h1',
 apartment:'M4 21V3h11v18m0-12h5v12M7 7h2m2 0h1M7 11h2m2 0h1M7 15h2m2 0h1M9 21v-3h3v3',
 house:'m3 11 9-8 9 8M5 10v11h14V10M10 21v-7h4v7',
 commercial:'M4 10v11h16V10M3 10l2-7h14l2 7M3 10a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0M9 21v-6h6v6',
 van:'M5 17V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v12H5Zm0-9h14M7 17v4m10-4v4M7 13h1m8 0h1',
 truck:'M2 5h12v12H2Zm12 5h4l4 4v3h-8M7 19a2 2 0 1 0-4 0 2 2 0 0 0 4 0m14 0a2 2 0 1 0-4 0 2 2 0 0 0 4 0',
 homeCleaning:'m5 14 9-11m-5 8 5 4-5 7-8-6 8-5ZM5 17l-2 3m5-1-2 3',
 repairService:'M14 4a6 6 0 0 0-7 8l-5 5 5 5 5-5a6 6 0 0 0 8-7l-4 4-5-5 3-5Z',
 tutoringService:'m2 9 10-5 10 5-10 5-10-5Zm4 2v6l6 3 6-3v-6m4-2v8',
 eventsStaffing:'M9 8a3 3 0 1 0 6 0 3 3 0 0 0-6 0M6 21v-3a6 6 0 0 1 12 0v3M3 6a3 3 0 0 0 0 6m18-6a3 3 0 0 1 0 6M2 21v-4m20 4v-4',
};
export function markerPath(item) {
 const type=item.type;
 return paths[type] || (['multicab','jeepney'].includes(type)?paths.van:paths[item.service==='haul'?'van':item.service==='raketeer'?'repairService':'house']);
}
