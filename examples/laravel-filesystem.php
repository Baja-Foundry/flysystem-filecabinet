<?php

/**
 * Laravel Filesystem Disk Configuration Example
 * 
 * Add this configuration to your config/filesystems.php file
 */

return [
    'disks' => [
        // ... other disks

        /**
         * NetSuite FileCabinet Disk Configuration
         */
        'netsuite' => [
            'driver' => 'netsuite-filecabinet',
            'base_url' => env('NETSUITE_BASE_URL'),
            'consumer_key' => env('NETSUITE_CONSUMER_KEY'),
            'consumer_secret' => env('NETSUITE_CONSUMER_SECRET'),
            'token_id' => env('NETSUITE_TOKEN_ID'),
            'token_secret' => env('NETSUITE_TOKEN_SECRET'),
            'realm' => env('NETSUITE_REALM'),
            'root_folder_id' => env('NETSUITE_ROOT_FOLDER_ID', ''),
            'prefix' => env('NETSUITE_PREFIX', ''),
            'timeout' => env('NETSUITE_TIMEOUT', 30),
            'visibility' => 'private', // NetSuite doesn't support public visibility
        ],

        /**
         * NetSuite with specific folder as root
         */
        'netsuite_documents' => [
            'driver' => 'netsuite-filecabinet',
            'base_url' => env('NETSUITE_BASE_URL'),
            'consumer_key' => env('NETSUITE_CONSUMER_KEY'),
            'consumer_secret' => env('NETSUITE_CONSUMER_SECRET'),
            'token_id' => env('NETSUITE_TOKEN_ID'),
            'token_secret' => env('NETSUITE_TOKEN_SECRET'),
            'realm' => env('NETSUITE_REALM'),
            'root_folder_id' => env('NETSUITE_DOCUMENTS_FOLDER_ID', '123'),
            'prefix' => 'app_documents/',
            'timeout' => env('NETSUITE_TIMEOUT', 30),
        ],

        // ... other disks
    ],
];