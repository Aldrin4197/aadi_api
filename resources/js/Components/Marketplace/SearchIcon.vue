<script setup>
defineProps({name:String});
const paths={spaces:'M3 10 12 3l9 7M5 9v12h5v-7h4v7h5V9',haul:'m5 6-2 7v6h3v-3h12v3h3v-6l-2-7ZM3 12h18M7 6h10M6 14h1m10 0h1',raketeer:'M8 7V4h8v3M3 7h18v13H3ZM3 12l9 3 9-3M12 12v4',search:'m21 21-5-5M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0',filters:'M4 7h16M4 17h16M8 4v6M16 14v6',location:'M12 2v3m0 14v3M2 12h3m14 0h3M19 12a7 7 0 1 1-14 0 7 7 0 0 1 14 0M14 12a2 2 0 1 1-4 0 2 2 0 0 1 4 0',list:'M8 6h13M8 12h13M8 18h13M3 6h1m-1 6h1m-1 6h1',reset:'M3 4v6h6M3 10a9 9 0 1 1 1 8',close:'m6 6 12 12M6 18 18 6',pin:'M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0ZM15 10a3 3 0 1 1-6 0 3 3 0 0 1 6 0'};
</script>
<template><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path :d="paths[name] || paths.search" /></svg></template>
