<?php

/**
 * PHPUnit bootstrap file.
 *
 * Locates the Composer autoloader by checking:
 * 1. The package's own vendor directory (standalone install)
 * 2. The parent project's vendor directory (when installed as a dependency)
 */

$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
];

$autoloaderFound = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloaderFound = true;
        break;
    }
}

if (!$autoloaderFound) {
    fwrite(STDERR, "Composer autoloader not found. Run 'composer install' first.\n");
    exit(1);
}
