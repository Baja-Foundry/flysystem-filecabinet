<?php

return [
    'base_url' => env('NETSUITE_BASE_URL'),
    'consumer_key' => env('NETSUITE_CONSUMER_KEY'),
    'consumer_secret' => env('NETSUITE_CONSUMER_SECRET'),
    'token_id' => env('NETSUITE_TOKEN_ID'),
    'token_secret' => env('NETSUITE_TOKEN_SECRET'),
    'realm' => env('NETSUITE_REALM'),
    'root_folder_id' => env('NETSUITE_ROOT_FOLDER_ID', ''),
    'prefix' => env('NETSUITE_PREFIX', ''),
    'timeout' => env('NETSUITE_TIMEOUT', 30),
];