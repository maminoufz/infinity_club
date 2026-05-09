<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your Cloudinary account details here. The package supports
    | CLOUDINARY_URL environment variable for easy setup.
    |
    */

    'url' => env('CLOUDINARY_URL'),

    'cloud' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
    ],

    'secure' => true,
];
