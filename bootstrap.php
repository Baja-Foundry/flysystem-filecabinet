<?php

// Bootstrap file for tests to suppress external library deprecation warnings

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Custom error handler to suppress deprecation notices from external libraries
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // List of paths/patterns to suppress deprecation warnings from
    $suppressPaths = [
        'phar:///usr/local/bin/composer',
        '/vendor/symfony/',
        '/vendor/composer/',
        '/vendor/justinrainbow/',
        '/vendor/react/',
        '/vendor/guzzlehttp/',
    ];
    
    // Only suppress deprecation notices from external libraries
    if ($errno === E_USER_DEPRECATED || $errno === E_DEPRECATED) {
        foreach ($suppressPaths as $path) {
            if (strpos($errfile, $path) !== false) {
                return true; // Suppress this deprecation
            }
        }
    }
    
    // Let other errors through
    return false;
});

// Set up error reporting to still show our own deprecations
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);