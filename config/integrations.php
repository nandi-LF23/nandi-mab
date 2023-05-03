<?php

// Foreach Integration, add a fully qualified class path to the 'classes' array below.

return [
    'classes' => [
        '\App\Integrations\JohnDeere\MyJohnDeere' => [
            'slug'       => 'MyJohnDeere',
            'base_url'   => 'https://partnerapi.deere.com/platform/',
            'debug_mode' => false
        ]
    ]
];