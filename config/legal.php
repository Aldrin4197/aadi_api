<?php
return [
    // Enable only after the legal bundle has been reviewed and marked published.
    'enabled' => env('AADI_LEGAL_ENABLED', false),
    'bundle' => resource_path('legal/aadi-v1-draft.json'),
];
