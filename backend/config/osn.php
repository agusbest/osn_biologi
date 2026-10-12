<?php

// Nilai khusus aplikasi. Dibaca lewat config() agar tetap bekerja setelah `php artisan config:cache`.
return [
    'seed_sample_content' => filter_var(env('SEED_SAMPLE_CONTENT', true), FILTER_VALIDATE_BOOLEAN),
    'admin' => [
        'name' => env('ADMIN_NAME', 'Admin'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
