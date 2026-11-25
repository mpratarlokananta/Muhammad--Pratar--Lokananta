<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
 * Support two layouts with the same front controller:
 *
 * 1. Local / Laravel Cloud / standard Laravel:
 *      backend/public/index.php -> backend/vendor, backend/bootstrap
 * 2. Shared hosting (HostData DirectAdmin):
 *      domain/public_html/index.php -> domain/laravel/vendor, domain/laravel/bootstrap
 */
$maintenance = __DIR__.'/../storage/framework/maintenance.php';
if (! file_exists($maintenance)) {
    $maintenance = __DIR__.'/../laravel/storage/framework/maintenance.php';
}

if (file_exists($maintenance)) {
    require $maintenance;
}

$autoload = __DIR__.'/../vendor/autoload.php';
if (! file_exists($autoload)) {
    $autoload = __DIR__.'/../laravel/vendor/autoload.php';
}

require $autoload;

$bootstrap = __DIR__.'/../bootstrap/app.php';
if (! file_exists($bootstrap)) {
    $bootstrap = __DIR__.'/../laravel/bootstrap/app.php';
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $bootstrap;

$app->handleRequest(Request::capture());
