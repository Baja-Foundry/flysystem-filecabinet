# Laravel Installation and Connection Testing Guide

This guide will walk you through installing the NetSuite FileCabinet Flysystem adapter in a Laravel project and testing the connection using artisan tinker.

## Prerequisites

- Laravel 10.x or 11.x
- PHP 8.2 or higher
- NetSuite account with REST API access
- Valid NetSuite OAuth credentials

## Installation Steps

### Step 1: Install the Package

#### Option A: Install from Local Development
If you're working with the package locally:

```bash
# In your Laravel project root
composer config repositories.netsuite-flysystem path /path/to/filecabinet
composer require baja-foundry/flysystem-filecabinet:dev-main
```

#### Option B: Install from Packagist
Once published to Packagist:
```bash
composer require baja-foundry/flysystem-filecabinet:^1.0.0-beta.1
```

### Step 2: Configure Laravel Filesystem

Add the NetSuite FileCabinet disk configuration to your `config/filesystems.php`:

```php
// config/filesystems.php
'disks' => [
    // ... existing disks ...
    
    'netsuite_filecabinet' => [
        'driver' => 'netsuite_filecabinet',
        'base_url' => env('NETSUITE_BASE_URL'),
        'consumer_key' => env('NETSUITE_CONSUMER_KEY'),
        'consumer_secret' => env('NETSUITE_CONSUMER_SECRET'),
        'token_id' => env('NETSUITE_TOKEN_ID'),
        'token_secret' => env('NETSUITE_TOKEN_SECRET'),
        'realm' => env('NETSUITE_REALM'),
        'root_folder_id' => env('NETSUITE_ROOT_FOLDER_ID', ''),
        'timeout' => env('NETSUITE_TIMEOUT', 30),
    ],
],
```

### Step 3: Add Environment Variables

Add your NetSuite credentials to your `.env` file:

```env
# NetSuite FileCabinet Configuration
NETSUITE_BASE_URL=https://YOUR_ACCOUNT_ID.suitetalk.api.netsuite.com
NETSUITE_CONSUMER_KEY=your_consumer_key_here
NETSUITE_CONSUMER_SECRET=your_consumer_secret_here
NETSUITE_TOKEN_ID=your_token_id_here
NETSUITE_TOKEN_SECRET=your_token_secret_here
NETSUITE_REALM=YOUR_ACCOUNT_ID
NETSUITE_ROOT_FOLDER_ID=
NETSUITE_TIMEOUT=30
```

**Replace the placeholders with your actual NetSuite credentials:**
- `YOUR_ACCOUNT_ID`: Your NetSuite account ID
- `your_consumer_key_here`: Consumer key from your Integration record
- `your_consumer_secret_here`: Consumer secret from your Integration record  
- `your_token_id_here`: Token ID from your Access Token record
- `your_token_secret_here`: Token secret from your Access Token record

### Step 4: Clear Laravel Caches

Clear Laravel's configuration and cache to ensure the new settings are loaded:

```bash
php artisan config:clear
php artisan cache:clear
```

### Step 5: Verify Service Provider Registration

The service provider should be automatically registered via Laravel's package auto-discovery. If you encounter issues, you can manually register it in `config/app.php`:

```php
// config/app.php - Only if auto-discovery fails
'providers' => [
    // ... other providers ...
    NetSuite\Flysystem\Laravel\NetSuiteFileCabinetServiceProvider::class,
],
```

## Connection Testing

### Test Connection with Artisan Tinker

Start Laravel's interactive shell:

```bash
php artisan tinker
```

#### Basic Connection Test

```php
// In artisan tinker
use Illuminate\Support\Facades\Storage;

// Get the NetSuite disk
$disk = Storage::disk('netsuite_filecabinet');

// Get the adapter to test connection
$adapter = $disk->getAdapter();

// Test the connection
$result = $adapter->testConnection();

// Display results
dump($result);
```

#### Expected Successful Output:
```php
array:3 [
  "success" => true
  "message" => "Successfully connected to NetSuite FileCabinet"
  "data" => array:1 [
    "items" => array:1 [
      0 => array:2 [
        "id" => "-15"
        "name" => "File Cabinet"
      ]
    ]
  ]
]
```

#### Expected Failed Output:
```php
array:3 [
  "success" => false
  "message" => "Failed to connect to NetSuite FileCabinet: [error details]"
  "error" => "[detailed error message]"
]
```

### Additional Testing in Tinker

Once connection is confirmed, test basic file operations:

```php
// Test writing a file
$disk->put('test.txt', 'Hello NetSuite FileCabinet!');

// Test reading a file
$content = $disk->get('test.txt');
echo $content; // Should output: Hello NetSuite FileCabinet!

// Test file existence
$exists = $disk->exists('test.txt');
var_dump($exists); // Should output: bool(true)

// List files in root directory
$files = $disk->files();
dump($files);

// Get file metadata
$size = $disk->size('test.txt');
$lastModified = $disk->lastModified('test.txt');
echo "Size: {$size} bytes, Modified: " . date('Y-m-d H:i:s', $lastModified);

// Clean up test file
$disk->delete('test.txt');
```

### Complete Test Session

Here's a complete test session you can copy/paste into tinker:

```php
// === NetSuite FileCabinet Connection Test ===
echo "🚀 Starting NetSuite FileCabinet test...\n";

// 1. Test connection
$disk = Storage::disk('netsuite_filecabinet');
$result = $disk->getAdapter()->testConnection();
dump('Connection Test Result:', $result);

// 2. If connection successful, test file operations
if ($result['success']) {
    echo "\n✅ Connection successful! Testing file operations...\n";
    
    // Write test file
    $testContent = 'Testing from Laravel ' . now()->format('Y-m-d H:i:s');
    $disk->put('laravel-test.txt', $testContent);
    echo "✅ File written: laravel-test.txt\n";
    
    // Read test file
    $content = $disk->get('laravel-test.txt');
    echo "✅ File content: " . $content . "\n";
    
    // Check file exists
    $exists = $disk->exists('laravel-test.txt');
    echo "✅ File exists: " . ($exists ? 'Yes' : 'No') . "\n";
    
    // Get file size
    $size = $disk->size('laravel-test.txt');
    echo "✅ File size: " . $size . " bytes\n";
    
    // Get last modified
    $modified = $disk->lastModified('laravel-test.txt');
    echo "✅ Last modified: " . date('Y-m-d H:i:s', $modified) . "\n";
    
    // Test file listing
    $files = $disk->files();
    echo "✅ Files in root: " . count($files) . " files found\n";
    
    // Clean up
    $disk->delete('laravel-test.txt');
    echo "✅ Test file deleted\n";
    
    echo "\n🎉 All tests passed! NetSuite FileCabinet is working perfectly in Laravel.\n";
} else {
    echo "\n❌ Connection failed. Please check your configuration and credentials.\n";
    echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
}

echo "\n📝 Test completed.\n";
```

## Usage in Laravel Application

### Basic File Operations

```php
use Illuminate\Support\Facades\Storage;

// Get the NetSuite disk
$disk = Storage::disk('netsuite_filecabinet');

// Upload a file
$disk->put('documents/invoice.pdf', $pdfContent);

// Download a file
$content = $disk->get('documents/invoice.pdf');

// Check if file exists
if ($disk->exists('documents/invoice.pdf')) {
    // File exists
}

// Delete a file
$disk->delete('documents/invoice.pdf');

// List files in a directory
$files = $disk->files('documents');

// Create a directory
$disk->makeDirectory('new-folder');

// Copy a file
$disk->copy('source.txt', 'destination.txt');

// Move a file
$disk->move('old-location.txt', 'new-location.txt');
```

### Using in Controllers

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function upload(Request $request)
    {
        $disk = Storage::disk('netsuite_filecabinet');
        
        // Test connection first
        $connectionTest = $disk->getAdapter()->testConnection();
        if (!$connectionTest['success']) {
            return response()->json(['error' => 'NetSuite connection failed'], 500);
        }
        
        // Upload file
        $file = $request->file('document');
        $path = 'uploads/' . $file->getClientOriginalName();
        $disk->put($path, $file->getContent());
        
        return response()->json(['message' => 'File uploaded successfully', 'path' => $path]);
    }
    
    public function download(string $path)
    {
        $disk = Storage::disk('netsuite_filecabinet');
        
        if (!$disk->exists($path)) {
            abort(404, 'File not found');
        }
        
        return response($disk->get($path))
            ->header('Content-Type', $disk->mimeType($path))
            ->header('Content-Disposition', 'attachment; filename="' . basename($path) . '"');
    }
}
```

## Troubleshooting

### Service Provider Not Registered

If you get an error about unknown driver 'netsuite_filecabinet':

```bash
# Clear caches and regenerate autoloader
php artisan config:cache
php artisan clear-compiled
composer dump-autoload

# Check if package is properly installed
composer show baja-foundry/flysystem-filecabinet
```

### Authentication Errors

If connection test fails with authentication errors:

```php
// In tinker, test environment variables are loaded correctly
dump([
    'base_url' => env('NETSUITE_BASE_URL'),
    'consumer_key' => env('NETSUITE_CONSUMER_KEY') ? 'SET' : 'NOT SET',
    'consumer_secret' => env('NETSUITE_CONSUMER_SECRET') ? 'SET' : 'NOT SET',
    'token_id' => env('NETSUITE_TOKEN_ID') ? 'SET' : 'NOT SET',
    'token_secret' => env('NETSUITE_TOKEN_SECRET') ? 'SET' : 'NOT SET',
    'realm' => env('NETSUITE_REALM'),
]);

// Test the raw client directly
use NetSuite\Flysystem\Client\NetSuiteClient;

$config = [
    'base_url' => env('NETSUITE_BASE_URL'),
    'consumer_key' => env('NETSUITE_CONSUMER_KEY'),
    'consumer_secret' => env('NETSUITE_CONSUMER_SECRET'),
    'token_id' => env('NETSUITE_TOKEN_ID'),
    'token_secret' => env('NETSUITE_TOKEN_SECRET'),
    'realm' => env('NETSUITE_REALM'),
];

$client = new NetSuiteClient($config);
$result = $client->testConnection();
dump($result);
```

### Common Issues

1. **Invalid OAuth signature**: Verify all credentials are correct and properly formatted
2. **Wrong realm**: Ensure realm exactly matches your NetSuite account ID
3. **Permission denied**: Check that your NetSuite role has FileCabinet and SuiteQL permissions
4. **Network timeout**: Increase the timeout value in configuration
5. **SSL/TLS errors**: Ensure your server supports modern TLS versions

### Getting Help

- Check the [CONNECTION_TESTING.md](CONNECTION_TESTING.md) guide for detailed troubleshooting
- Review NetSuite's [SuiteTalk REST documentation](https://docs.oracle.com/en/cloud/saas/netsuite/ns-online-help/chapter_1540391670.html)
- Verify your [Token-Based Authentication](https://docs.oracle.com/en/cloud/saas/netsuite/ns-online-help/section_4247337262.html) setup

## Next Steps

Once you have confirmed the connection is working:

1. **Configure root folder**: Set `NETSUITE_ROOT_FOLDER_ID` if you want to restrict access to a specific folder
2. **Set up file paths**: Organize your NetSuite FileCabinet structure
3. **Implement error handling**: Add proper exception handling in your application
4. **Add logging**: Consider logging file operations for audit purposes
5. **Test in staging**: Thoroughly test with your staging NetSuite environment before production

## Security Considerations

- Keep your `.env` file secure and never commit credentials to version control
- Use different credentials for development, staging, and production environments
- Regularly rotate your NetSuite tokens
- Monitor API usage to detect any unauthorized access
- Consider implementing rate limiting for file operations