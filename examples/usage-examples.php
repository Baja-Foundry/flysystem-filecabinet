<?php

/**
 * NetSuite FileCabinet Flysystem Adapter Usage Examples
 */

use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use BajaFoundry\NetSuite\Flysystem\Adapter\NetSuiteFileCabinetAdapter;
use BajaFoundry\NetSuite\Flysystem\Client\NetSuiteClient;

/**
 * Example 1: Laravel Storage Facade Usage
 */
function laravelStorageExamples()
{
    // Write a file
    Storage::disk('netsuite')->put('documents/report.pdf', file_get_contents('local-report.pdf'));

    // Read a file
    $content = Storage::disk('netsuite')->get('documents/report.pdf');

    // Check if file exists
    if (Storage::disk('netsuite')->exists('documents/report.pdf')) {
        echo "File exists!";
    }

    // Get file size
    $size = Storage::disk('netsuite')->size('documents/report.pdf');

    // Get last modified timestamp
    $lastModified = Storage::disk('netsuite')->lastModified('documents/report.pdf');

    // List files in directory
    $files = Storage::disk('netsuite')->files('documents/');
    foreach ($files as $file) {
        echo "Found file: $file\n";
    }

    // List directories
    $directories = Storage::disk('netsuite')->directories('documents/');
    
    // Copy file
    Storage::disk('netsuite')->copy('documents/report.pdf', 'backups/report-backup.pdf');

    // Move/rename file
    Storage::disk('netsuite')->move('documents/old-name.pdf', 'documents/new-name.pdf');

    // Delete file
    Storage::disk('netsuite')->delete('documents/old-file.pdf');

    // Create directory
    Storage::disk('netsuite')->makeDirectory('new-folder');

    // Delete directory
    Storage::disk('netsuite')->deleteDirectory('old-folder');

    // Upload from stream
    $stream = fopen('local-file.txt', 'r');
    Storage::disk('netsuite')->writeStream('uploads/streamed-file.txt', $stream);
    fclose($stream);

    // Download as stream
    $stream = Storage::disk('netsuite')->readStream('uploads/streamed-file.txt');
    file_put_contents('downloaded-file.txt', $stream);
}

/**
 * Example 2: Direct Flysystem Usage
 */
function directFlysystemExample()
{
    // Initialize client
    $client = new NetSuiteClient([
        'base_url' => 'https://your-account.suitetalk.api.netsuite.com',
        'consumer_key' => 'your_consumer_key',
        'consumer_secret' => 'your_consumer_secret',
        'token_id' => 'your_token_id',
        'token_secret' => 'your_token_secret',
        'realm' => 'your_account_id',
        'timeout' => 30,
    ]);

    // Create adapter with specific folder as root
    $adapter = new NetSuiteFileCabinetAdapter($client, '123', 'app-files/');
    
    // Create filesystem
    $filesystem = new Filesystem($adapter);

    // Write file
    $filesystem->write('config.json', json_encode(['app' => 'settings']));

    // Read file
    $config = json_decode($filesystem->read('config.json'), true);

    // Check existence
    if ($filesystem->fileExists('config.json')) {
        echo "Config file exists";
    }

    // Get metadata
    $attributes = $filesystem->fileSize('config.json');
    echo "File size: " . $attributes->fileSize() . " bytes\n";

    // List contents
    $listing = $filesystem->listContents('', true); // recursive
    foreach ($listing as $item) {
        if ($item->isFile()) {
            echo "File: " . $item->path() . "\n";
        } else {
            echo "Directory: " . $item->path() . "\n";
        }
    }
}

/**
 * Example 3: Bulk Operations
 */
function bulkOperationsExample()
{
    $disk = Storage::disk('netsuite');
    
    // Upload multiple files
    $localFiles = [
        'document1.pdf',
        'document2.pdf',
        'document3.pdf'
    ];

    foreach ($localFiles as $file) {
        if (file_exists($file)) {
            $disk->put("bulk-upload/$file", file_get_contents($file));
            echo "Uploaded: $file\n";
        }
    }

    // Synchronize directories
    $localDir = 'local-documents/';
    $remoteDir = 'synced-documents/';
    
    $localFiles = scandir($localDir);
    foreach ($localFiles as $file) {
        if ($file !== '.' && $file !== '..') {
            $localPath = $localDir . $file;
            $remotePath = $remoteDir . $file;
            
            if (is_file($localPath)) {
                $disk->put($remotePath, file_get_contents($localPath));
                echo "Synced: $file\n";
            }
        }
    }
}

/**
 * Example 4: Error Handling
 */
function errorHandlingExample()
{
    try {
        $content = Storage::disk('netsuite')->get('non-existent-file.txt');
    } catch (\Illuminate\Contracts\Filesystem\FileNotFoundException $e) {
        echo "File not found: " . $e->getMessage() . "\n";
    } catch (\Exception $e) {
        echo "General error: " . $e->getMessage() . "\n";
    }

    // Check before operations
    if (Storage::disk('netsuite')->exists('important-file.txt')) {
        $size = Storage::disk('netsuite')->size('important-file.txt');
        if ($size > 0) {
            $content = Storage::disk('netsuite')->get('important-file.txt');
            // Process file...
        }
    }
}

/**
 * Example 5: Working with Different File Types
 */
function fileTypeExamples()
{
    $disk = Storage::disk('netsuite');

    // Upload image
    $disk->put('images/logo.png', file_get_contents('logo.png'));

    // Upload CSV
    $csvData = "Name,Email,Phone\nJohn,john@example.com,123-456-7890";
    $disk->put('data/contacts.csv', $csvData);

    // Upload JSON
    $jsonData = json_encode(['users' => [['id' => 1, 'name' => 'John']]]);
    $disk->put('api/users.json', $jsonData);

    // Upload XML
    $xmlData = '<?xml version="1.0"?><root><item>value</item></root>';
    $disk->put('exports/data.xml', $xmlData);

    // Get mime types
    echo "Image MIME: " . $disk->mimeType('images/logo.png') . "\n";
    echo "CSV MIME: " . $disk->mimeType('data/contacts.csv') . "\n";
}

/**
 * Example 6: Laravel Validation with NetSuite Storage
 */
function laravelValidationExample()
{
    // In a Laravel controller
    /*
    public function store(Request $request)
    {
        $request->validate([
            'document' => 'required|file|max:10240', // 10MB max
        ]);

        $file = $request->file('document');
        $path = $file->store('uploads', 'netsuite');

        return response()->json([
            'message' => 'File uploaded successfully',
            'path' => $path,
            'size' => Storage::disk('netsuite')->size($path),
            'exists' => Storage::disk('netsuite')->exists($path),
        ]);
    }
    */
}

/**
 * Example 7: Configuration for Different Environments
 */
function environmentConfigExample()
{
    // In config/filesystems.php
    /*
    'netsuite_production' => [
        'driver' => 'netsuite-filecabinet',
        'base_url' => env('NETSUITE_PROD_BASE_URL'),
        'consumer_key' => env('NETSUITE_PROD_CONSUMER_KEY'),
        'consumer_secret' => env('NETSUITE_PROD_CONSUMER_SECRET'),
        'token_id' => env('NETSUITE_PROD_TOKEN_ID'),
        'token_secret' => env('NETSUITE_PROD_TOKEN_SECRET'),
        'realm' => env('NETSUITE_PROD_REALM'),
        'root_folder_id' => env('NETSUITE_PROD_ROOT_FOLDER_ID'),
        'prefix' => 'production/',
    ],

    'netsuite_staging' => [
        'driver' => 'netsuite-filecabinet',
        'base_url' => env('NETSUITE_STAGING_BASE_URL'),
        'consumer_key' => env('NETSUITE_STAGING_CONSUMER_KEY'),
        'consumer_secret' => env('NETSUITE_STAGING_CONSUMER_SECRET'),
        'token_id' => env('NETSUITE_STAGING_TOKEN_ID'),
        'token_secret' => env('NETSUITE_STAGING_TOKEN_SECRET'),
        'realm' => env('NETSUITE_STAGING_REALM'),
        'root_folder_id' => env('NETSUITE_STAGING_ROOT_FOLDER_ID'),
        'prefix' => 'staging/',
    ],
    */

    // Usage based on environment
    $disk = app()->environment('production') ? 'netsuite_production' : 'netsuite_staging';
    Storage::disk($disk)->put('test.txt', 'Environment specific upload');
}