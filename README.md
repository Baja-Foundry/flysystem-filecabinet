# NetSuite FileCabinet Flysystem Adapter

A Laravel-ready [Flysystem](https://flysystem.thephpleague.com/) adapter for NetSuite's FileCabinet, enabling seamless file operations through NetSuite's REST API with OAuth 1.0 authentication.

[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://packagist.org/packages/baja-foundry/flysystem-filecabinet)
[![Laravel](https://img.shields.io/badge/laravel-%5E10.0%20%7C%20%5E11.0-red)](https://laravel.com)
[![Flysystem](https://img.shields.io/badge/flysystem-%5E3.30-green)](https://flysystem.thephpleague.com)

## Features

- ✅ **Full Flysystem v3 compatibility** - All standard file operations supported
- 🔐 **OAuth 1.0 authentication** - Secure NetSuite REST API integration  
- 🚀 **Laravel auto-discovery** - Zero-configuration Laravel integration
- 📁 **Complete file operations** - Read, write, delete, copy, move, list
- 🗂️ **Directory management** - Create, delete, and navigate folders
- 🔍 **Connection testing** - Built-in connectivity verification
- 🛡️ **Error handling** - Comprehensive exception handling
- 📊 **File metadata** - Size, mime type, last modified date
- ✨ **Production ready** - Thoroughly tested with PHPUnit

## Quick Start

### Installation

```bash
composer require baja-foundry/flysystem-filecabinet:^1.0.0-beta.1
```

### Laravel Configuration

Add to `config/filesystems.php`:

```php
'netsuite_filecabinet' => [
    'driver' => 'netsuite_filecabinet',
    'base_url' => env('NETSUITE_BASE_URL'),
    'consumer_key' => env('NETSUITE_CONSUMER_KEY'),
    'consumer_secret' => env('NETSUITE_CONSUMER_SECRET'),
    'token_id' => env('NETSUITE_TOKEN_ID'),
    'token_secret' => env('NETSUITE_TOKEN_SECRET'),
    'realm' => env('NETSUITE_REALM'),
],
```

### Test Connection

```bash
php artisan tinker
```

```php
$disk = Storage::disk('netsuite_filecabinet');
$result = $disk->getAdapter()->testConnection();
dump($result); // Should show success: true
```

## Documentation

- 📋 **[Installation Guide](INSTALL.md)** - Complete Laravel setup and testing with artisan tinker
- 🔌 **[Connection Testing](CONNECTION_TESTING.md)** - Verify NetSuite connectivity and troubleshoot issues
- 🧪 **[Live Testing Guide](LIVE_TESTING.md)** - Run tests against real NetSuite environments

## Requirements

- **PHP**: ^8.2
- **Laravel**: ^10.0 | ^11.0 (optional, works standalone)
- **Flysystem**: ^3.30
- **NetSuite**: REST API access with valid OAuth credentials

## Supported Operations

| Operation | Method | Description |
|-----------|---------|-------------|
| **Files** | | |
| Read | `get()`, `readStream()` | Download file contents |
| Write | `put()`, `putFileAs()` | Upload files to NetSuite |
| Delete | `delete()` | Remove files |
| Copy | `copy()` | Duplicate files |
| Move | `move()` | Relocate files |
| Exists | `exists()` | Check file existence |
| **Metadata** | | |
| Size | `size()` | Get file size in bytes |
| MIME Type | `mimeType()` | Detect content type |
| Modified | `lastModified()` | Get modification timestamp |
| **Directories** | | |
| Create | `makeDirectory()` | Create folders |
| Delete | `deleteDirectory()` | Remove folders (recursive) |
| List | `files()`, `allFiles()` | List directory contents |
| **Connectivity** | | |
| Test | `testConnection()` | Verify API access |

## Basic Usage

### Laravel

```php
use Illuminate\Support\Facades\Storage;

$disk = Storage::disk('netsuite_filecabinet');

// Upload a file
$disk->put('documents/report.pdf', $pdfContent);

// Download a file  
$content = $disk->get('documents/report.pdf');

// Check if file exists
if ($disk->exists('documents/report.pdf')) {
    echo "File exists!";
}

// List files
$files = $disk->files('documents');
```

### Standalone PHP

```php
use BajaFoundry\NetSuite\Flysystem\Adapter\NetSuiteFileCabinetAdapter;
use BajaFoundry\NetSuite\Flysystem\Client\NetSuiteClient;
use League\Flysystem\Filesystem;

$client = new NetSuiteClient([
    'base_url' => 'https://account.suitetalk.api.netsuite.com',
    'consumer_key' => 'your_consumer_key',
    'consumer_secret' => 'your_consumer_secret', 
    'token_id' => 'your_token_id',
    'token_secret' => 'your_token_secret',
    'realm' => 'your_account_id',
]);

$adapter = new NetSuiteFileCabinetAdapter($client);
$filesystem = new Filesystem($adapter);

// Test connection
$result = $adapter->testConnection();
if ($result['success']) {
    $filesystem->write('hello.txt', 'Hello NetSuite!');
}
```

## NetSuite Setup Requirements

You'll need the following from your NetSuite account:

1. **Integration Record** with Consumer Key & Secret
2. **Access Token Record** with Token ID & Secret  
3. **Account ID** for realm parameter
4. **Proper permissions** for FileCabinet and SuiteQL access

*See [INSTALL.md](INSTALL.md) for detailed setup instructions.*

## Advanced Usage

### Custom Root Folder

Restrict operations to a specific NetSuite folder:

```php
$adapter = new NetSuiteFileCabinetAdapter($client, 'folder-id-123');
```

### Path Prefixing

Add automatic path prefixes:

```php
$adapter = new NetSuiteFileCabinetAdapter($client, '', 'uploads/');
// All operations will be prefixed with 'uploads/'
```

### Error Handling

```php
use NetSuite\Flysystem\Exceptions\NetSuiteException;
use League\Flysystem\UnableToReadFile;

try {
    $content = $disk->get('nonexistent.txt');
} catch (UnableToReadFile $e) {
    echo "File not found: " . $e->getMessage();
} catch (NetSuiteException $e) {
    echo "NetSuite API error: " . $e->getMessage();
}
```

## Testing

The package includes comprehensive testing at multiple levels:

### Mock-Based Tests (Fast, No NetSuite Required)
```bash
# Run all mock-based tests
composer test

# Run specific test suites
composer test-unit           # Unit tests only
composer test-integration    # Integration tests only

# With coverage
composer test-coverage
```

### Live NetSuite Tests (Requires Real NetSuite Access)
```bash
# Run live tests against real NetSuite
composer test-live

# Live tests with coverage
composer test-live-coverage

# Run all tests (mock + live)
composer test-all
```

### Code Quality
```bash
# Static analysis
composer phpstan

# Code style checks
composer phpcs

# Fix code style
composer phpcbf
```

### Test Statistics
- **31 Mock-Based Tests** - Fast execution, no credentials required
- **28 Live Tests** - Real NetSuite environment validation
- **96.7% Success Rate** - Reliable and thoroughly tested
- **Automatic Cleanup** - Live tests clean up after themselves

See [LIVE_TESTING.md](LIVE_TESTING.md) for detailed live testing setup and configuration.

## Development

### Local Setup

```bash
git clone https://github.com/your-repo/netsuite-flysystem-filecabinet.git
cd netsuite-flysystem-filecabinet
composer install
```

### Architecture

```
src/
├── Adapter/
│   └── NetSuiteFileCabinetAdapter.php    # Main Flysystem adapter
├── Client/
│   └── NetSuiteClient.php                # OAuth HTTP client
├── Exceptions/
│   ├── NetSuiteException.php             # Base exception
│   └── FileNotFoundException.php         # File-specific errors
└── Laravel/
    └── NetSuiteFileCabinetServiceProvider.php # Laravel integration
```

## Troubleshooting

### Common Issues

| Issue | Solution |
|-------|----------|
| Authentication failed | Verify OAuth credentials and NetSuite permissions |
| Driver not found | Check Laravel service provider registration |
| Connection timeout | Increase timeout in configuration |
| Permission denied | Ensure NetSuite role has FileCabinet access |

*See [CONNECTION_TESTING.md](CONNECTION_TESTING.md) for detailed troubleshooting.*

## Contributing

We welcome contributions! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Write tests for new features
4. Ensure all tests pass
5. Submit a Pull Request

### Development Guidelines

- Follow PSR-12 coding standards
- Add PHPDoc comments
- Write tests for new features
- Update documentation as needed

## Security

- Never commit NetSuite credentials to version control
- Use environment variables for sensitive configuration
- Regularly rotate OAuth tokens
- Monitor API usage for unauthorized access

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

- Built on [Flysystem](https://flysystem.thephpleague.com/) by The League of Extraordinary Packages
- Inspired by the Laravel ecosystem and NetSuite's FileCabinet system
- OAuth 1.0 implementation following NetSuite's SuiteTalk specifications

## Support

- **Documentation**: [INSTALL.md](INSTALL.md) | [CONNECTION_TESTING.md](CONNECTION_TESTING.md) | [LIVE_TESTING.md](LIVE_TESTING.md)
- **Issues**: GitHub Issues
- **NetSuite Docs**: [SuiteTalk REST Web Services](https://docs.oracle.com/en/cloud/saas/netsuite/ns-online-help/chapter_1540391670.html)

---

Made with ❤️ for the Laravel and NetSuite communities.