<?php

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;

require dirname(__DIR__).'/vendor/autoload.php';

new Dotenv()->bootEnv(dirname(__DIR__).'/.env');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

putenv('APP_ENV=test');

// Clear test cache directory to avoid stale container.
$filesystem = new Filesystem();
$cacheDir = dirname(__DIR__).'/var/cache/test';
if ($filesystem->exists($cacheDir)) {
    $filesystem->remove($cacheDir);
}
