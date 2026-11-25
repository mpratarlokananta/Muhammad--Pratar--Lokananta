<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Credentials
    |--------------------------------------------------------------------------
    |
    | Used by AdminUserSeeder when creating/updating the admin account.
    | Read through config() instead of env() so it also works when Laravel's
    | configuration is cached during deployment (e.g. on Laravel Cloud).
    |
    */

    'username' => env('ADMIN_USERNAME', 'admin'),

    'password' => env('ADMIN_PASSWORD', 'admin123'),

];
