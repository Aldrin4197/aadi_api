<?php

return [
    'google_play_url' => env('AADI_GOOGLE_PLAY_URL'),
    'app_store_url' => env('AADI_APP_STORE_URL'),

    // Publish only authentic quotes with permission and a public source.
    // Each entry: ['published' => true, 'quote' => '...', 'name' => '...',
    //              'context' => '...', 'source_url' => 'https://...'].
    // Do not add demonstration testimonials here: this is public homepage content.
    'reviews' => [],
];
