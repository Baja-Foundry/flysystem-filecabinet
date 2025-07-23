# Release Notes

## v1.0.0-beta.1 - 2025-07-22

### 🎉 Initial Beta Release

We're excited to announce the first beta release of the NetSuite FileCabinet Flysystem Adapter! This package provides a production-ready adapter for integrating NetSuite's FileCabinet with Laravel and standalone PHP applications using the Flysystem filesystem abstraction.

### ✨ Features

#### **Core Functionality**
- **Full Flysystem v3 Compatibility** - Complete implementation of all FilesystemAdapter methods
- **OAuth 1.0 Authentication** - Secure NetSuite REST API integration with proper signature generation
- **File Operations** - Read, write, delete, copy, move operations with NetSuite FileCabinet
- **Directory Management** - Create, delete, and navigate folder structures
- **Metadata Support** - File size, MIME type detection, and last modified timestamps
- **Connection Testing** - Built-in connectivity verification with detailed error reporting

#### **Laravel Integration**
- **Auto-Discovery** - Zero-configuration setup with Laravel package auto-discovery
- **Service Provider** - Seamless integration with Laravel's Storage facade
- **Configuration Publishing** - Publishable configuration files for easy customization
- **Environment Variables** - Full support for `.env` configuration

#### **Developer Experience**
- **Comprehensive Documentation** - Installation guide, connection testing, and usage examples
- **Production Ready** - Thoroughly tested with 31 unit and integration tests
- **Error Handling** - Proper exception handling with Flysystem-compatible exceptions
- **PSR-12 Compliant** - Professional code standards and formatting
- **PHPDoc Documentation** - Complete API documentation for IDE support

### 🔧 Technical Specifications

- **PHP Version**: ^8.2
- **Laravel Support**: ^10.0 | ^11.0 (optional)
- **Flysystem Version**: ^3.30
- **HTTP Client**: Guzzle ^7.9

### 📦 Package Details

- **Package Name**: `baja-foundry/flysystem-filecabinet`
- **Namespace**: `BajaFoundry\NetSuite\Flysystem\`
- **License**: MIT
- **Author**: Baja Foundry

### 🚀 Installation

```bash
composer require baja-foundry/flysystem-filecabinet:^1.0.0-beta.1
```

### ⚙️ Laravel Configuration

```php
// config/filesystems.php
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

### 💻 Quick Usage

#### Laravel Storage Facade
```php
use Illuminate\Support\Facades\Storage;

$disk = Storage::disk('netsuite_filecabinet');

// Test connection
$result = $disk->getAdapter()->testConnection();

// Upload a file
$disk->put('documents/report.pdf', $pdfContent);

// Download a file
$content = $disk->get('documents/report.pdf');
```

#### Standalone PHP
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

// Test connection and upload file
if ($adapter->testConnection()['success']) {
    $filesystem->write('hello.txt', 'Hello NetSuite!');
}
```

### 🧪 Testing

The package includes comprehensive testing:

- **31 Test Cases** - Unit and integration tests
- **96.7% Success Rate** - 30 passing tests, 1 intentionally skipped
- **Mock-Based Testing** - No real NetSuite account required for testing
- **PHPUnit 10.5** - Latest testing framework
- **PHPStan Level 8** - Maximum static analysis
- **PSR-12 Code Style** - Professional coding standards

### 📚 Documentation

- **[Installation Guide](INSTALL.md)** - Complete Laravel setup with artisan tinker testing
- **[Connection Testing](CONNECTION_TESTING.md)** - Verify NetSuite connectivity and troubleshooting
- **README.md** - Comprehensive usage guide with examples
- **Test Connection Script** - `test-connection.php` for quick connectivity verification

### 🔍 Connection Testing

Built-in connection testing with detailed diagnostics:

```php
// Test connection
$result = $adapter->testConnection();

if ($result['success']) {
    echo "✅ Connected successfully!";
    // Result includes NetSuite root folder data
} else {
    echo "❌ Connection failed: " . $result['message'];
    // Detailed error information provided
}
```

### 🏗️ Architecture

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

### 🔐 Security Features

- **OAuth 1.0 Signature Generation** - Secure HMAC-SHA256 signatures
- **Credential Validation** - Connection testing before operations
- **Error Sanitization** - Safe error messages without credential exposure
- **Environment Variable Support** - Secure credential storage

### 🌟 Supported Operations

| Category | Operations | Methods |
|----------|------------|---------|
| **Files** | Read, Write, Delete, Copy, Move, Exists | `get()`, `put()`, `delete()`, `copy()`, `move()`, `exists()` |
| **Directories** | Create, Delete, List | `makeDirectory()`, `deleteDirectory()`, `files()`, `directories()` |
| **Metadata** | Size, MIME Type, Modified Date | `size()`, `mimeType()`, `lastModified()` |
| **Connectivity** | Connection Testing | `testConnection()` |

### 🚧 Known Limitations

- **NetSuite REST API Limitations** - FileCabinet operations use SuiteQL queries and file endpoints
- **Rate Limiting** - Subject to NetSuite API rate limits based on license
- **File Size Limits** - Dependent on NetSuite account configuration
- **Directory Creation Test** - One integration test intentionally skipped (requires complex mocking)

### 🤝 Contributing

We welcome contributions! Please see our development guidelines:

- Follow PSR-12 coding standards
- Write comprehensive tests for new features
- Update documentation as needed
- Ensure all existing tests pass

### 🔗 Links

- **GitHub Repository**: [https://github.com/baja-foundry/flysystem-filecabinet](https://github.com/baja-foundry/flysystem-filecabinet)
- **Packagist Package**: [https://packagist.org/packages/baja-foundry/flysystem-filecabinet](https://packagist.org/packages/baja-foundry/flysystem-filecabinet)
- **NetSuite Documentation**: [SuiteTalk REST Web Services](https://docs.oracle.com/en/cloud/saas/netsuite/ns-online-help/chapter_1540391670.html)

### 📝 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

### 🙏 Acknowledgments

- Built on [Flysystem](https://flysystem.thephpleague.com/) by The League of Extraordinary Packages
- Inspired by the Laravel ecosystem and NetSuite's powerful FileCabinet system
- OAuth 1.0 implementation following NetSuite's SuiteTalk specifications

---

**Made with ❤️ by Baja Foundry for the Laravel and NetSuite communities.**

For support, documentation, and issues, visit our [GitHub repository](https://github.com/baja-foundry/flysystem-filecabinet).