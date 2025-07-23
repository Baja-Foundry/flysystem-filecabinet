# Connection Testing Guide

This guide explains how to test your NetSuite FileCabinet connection to ensure proper authentication and API access.

## Quick Test Script

Use the provided `test-connection.php` script for a quick connection test:

```bash
php test-connection.php
```

**Before running the script, update the configuration values in the file:**

```php
$config = [
    'base_url' => 'https://YOUR_ACCOUNT_ID.suitetalk.api.netsuite.com',
    'consumer_key' => 'YOUR_CONSUMER_KEY',
    'consumer_secret' => 'YOUR_CONSUMER_SECRET',
    'token_id' => 'YOUR_TOKEN_ID',
    'token_secret' => 'YOUR_TOKEN_SECRET',
    'realm' => 'YOUR_ACCOUNT_ID',
    'timeout' => 30,
];
```

## Programmatic Testing

### Using the NetSuiteClient directly

```php
use BajaFoundry\NetSuite\Flysystem\Client\NetSuiteClient;

$config = [
    'base_url' => 'https://your-account.suitetalk.api.netsuite.com',
    'consumer_key' => 'your_consumer_key',
    'consumer_secret' => 'your_consumer_secret',
    'token_id' => 'your_token_id',
    'token_secret' => 'your_token_secret',
    'realm' => 'your_account_id',
];

$client = new NetSuiteClient($config);
$result = $client->testConnection();

if ($result['success']) {
    echo "Connection successful: " . $result['message'];
} else {
    echo "Connection failed: " . $result['message'];
}
```

### Using the Adapter

```php
use BajaFoundry\NetSuite\Flysystem\Adapter\NetSuiteFileCabinetAdapter;
use BajaFoundry\NetSuite\Flysystem\Client\NetSuiteClient;

$client = new NetSuiteClient($config);
$adapter = new NetSuiteFileCabinetAdapter($client);

$result = $adapter->testConnection();

if ($result['success']) {
    echo "FileCabinet connection is working!";
} else {
    echo "Connection issue: " . $result['message'];
}
```

### Laravel Integration Testing

If using Laravel, you can test the connection in artisan tinker or a controller:

```php
// In Laravel controller or tinker
$disk = Storage::disk('netsuite_filecabinet');
$adapter = $disk->getAdapter();

if (method_exists($adapter, 'testConnection')) {
    $result = $adapter->testConnection();
    dd($result);
}
```

## What the Test Does

The connection test performs a simple SuiteQL query to fetch the root folder information:

```sql
SELECT id, name FROM folder WHERE id = -15
```

This query:
- Tests OAuth 1.0 authentication
- Verifies API endpoint accessibility  
- Checks SuiteQL permissions
- Confirms FileCabinet access rights

## Expected Results

### Successful Connection
```php
[
    'success' => true,
    'message' => 'Successfully connected to NetSuite FileCabinet',
    'data' => [
        'items' => [
            [
                'id' => '-15',
                'name' => 'File Cabinet'
            ]
        ]
    ]
]
```

### Failed Connection
```php
[
    'success' => false,
    'message' => 'Failed to connect to NetSuite FileCabinet: [error details]',
    'error' => '[detailed error message]'
]
```

## Common Issues and Solutions

### Authentication Errors
- **Invalid OAuth signature**: Check consumer key/secret and token ID/secret
- **Wrong realm**: Ensure realm matches your NetSuite account ID
- **Timestamp issues**: Server time should be synchronized

### Permission Errors
- **SuiteQL access**: Ensure your role has SuiteQL permissions
- **FileCabinet access**: Verify FileCabinet permissions in your NetSuite role
- **Integration permissions**: Check your integration record permissions

### Network Issues
- **Timeout errors**: Increase timeout value in configuration
- **SSL/TLS issues**: Ensure your server supports modern TLS versions
- **Firewall blocks**: Check if your network allows HTTPS connections to NetSuite

## Getting NetSuite Credentials

To use this adapter, you need:

1. **Consumer Key & Secret**: From your NetSuite Integration record
2. **Token ID & Secret**: From your Access Token record  
3. **Account ID**: Your NetSuite account identifier
4. **Base URL**: Format: `https://[account-id].suitetalk.api.netsuite.com`

See NetSuite's documentation for detailed setup instructions:
- [SuiteTalk REST Web Services](https://docs.oracle.com/en/cloud/saas/netsuite/ns-online-help/chapter_1540391670.html)
- [Token-Based Authentication](https://docs.oracle.com/en/cloud/saas/netsuite/ns-online-help/section_4247337262.html)

## Advanced Testing

For comprehensive testing against real NetSuite environments, see the [Live Testing Guide](LIVE_TESTING.md) which provides:

- **28+ Live Tests** against actual NetSuite FileCabinet
- **Automated cleanup** of test files
- **Rate limiting** compliance
- **Performance monitoring** and benchmarking
- **Error recovery** testing
- **Complete workflow** validation

The live testing suite complements this connection testing by providing thorough validation of all adapter functionality in real-world scenarios.