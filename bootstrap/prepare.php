<?php

/*
 * Runs automatically from Composer (pre-autoload-dump).
 * Creates the folders Laravel needs to write to and a local .env file,
 * so a fresh clone works right after `composer install`.
 */

$root = dirname(__DIR__);

$directories = [
    'bootstrap/cache',
    'storage/app/private',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'storage/app/public',
    'database',
];

foreach ($directories as $directory) {
    $path = $root.'/'.$directory;

    if (! is_dir($path)) {
        mkdir($path, 0775, true);
    }
}

if (! file_exists($root.'/database/database.sqlite')) {
    touch($root.'/database/database.sqlite');
}

if (! file_exists($root.'/.env') && file_exists($root.'/.env.example')) {
    copy($root.'/.env.example', $root.'/.env');
}
