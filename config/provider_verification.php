<?php
// Product verification requirements, not a statement of legal requirements.
// Administrators can adjust required document types for each role here.
return [
    'spaces' => [
        'identity' => ['label'=>'Government-issued ID','required'=>true],
        'property_authority' => ['label'=>'Proof of ownership or authority to lease','required'=>true],
        'bir' => ['label'=>'BIR registration (if applicable)','required'=>false],
        'business_registration' => ['label'=>'Business registration / permit (if applicable)','required'=>false],
    ],
    'haul' => [
        'identity' => ['label'=>'Government-issued ID','required'=>true],
        'vehicle_or' => ['label'=>'Vehicle Official Receipt (OR)','required'=>true],
        'vehicle_cr' => ['label'=>'Vehicle Certificate of Registration (CR)','required'=>true],
        'authorization' => ['label'=>'Registered owner authorization (if applicable)','required'=>false],
        'bir' => ['label'=>'BIR registration (if applicable)','required'=>false],
        'business_registration' => ['label'=>'Business registration / permit (if applicable)','required'=>false],
    ],
    'raketeer' => [
        'identity' => ['label'=>'Government-issued ID','required'=>true],
        'certification' => ['label'=>'Skills / training certification','required'=>false],
        'professional_license' => ['label'=>'Professional license (if applicable)','required'=>false],
        'portfolio' => ['label'=>'Portfolio / work samples','required'=>false],
        'bir' => ['label'=>'BIR registration (if applicable)','required'=>false],
        'business_registration' => ['label'=>'Business registration / permit (if applicable)','required'=>false],
    ],
];
