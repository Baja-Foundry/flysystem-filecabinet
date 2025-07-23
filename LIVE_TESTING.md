# Live NetSuite Testing Guide

This guide explains how to set up and run live integration tests against a real NetSuite environment.

## Overview

The live testing suite provides comprehensive testing against actual NetSuite FileCabinet systems, complementing the existing mock-based unit and integration tests. Live tests verify real-world functionality, OAuth authentication, and NetSuite API compatibility.

## Test Structure

```
tests/
├── Unit/                    # Mock-based unit tests (fast, no NetSuite connection)
├── Integration/             # Mock-based integration tests (fast, no NetSuite connection)
└── Live/                    # Live NetSuite environment tests (slow, requires credentials)
    ├── LiveTestCase.php                 # Base class with common utilities
    ├── LiveNetSuiteClientTest.php       # OAuth and API client tests
    ├── LiveNetSuiteAdapterTest.php      # File operation tests
    └── LiveNetSuiteEndToEndTest.php     # Complete workflow tests
```

## Prerequisites

### NetSuite Requirements
- NetSuite account with SuiteTalk REST API access
- **Sandbox environment recommended** for testing
- Valid OAuth integration and access token
- Proper permissions for FileCabinet operations

### Required Permissions
Your NetSuite role must have:
- **SuiteQL** - Query permissions
- **Documents and Files** - Full access to FileCabinet
- **Lists > Documents and Files** - View, Edit permissions
- **Integration Management** - If managing tokens

## Setup Instructions

### 1. Install Dependencies
```bash
composer install
```

### 2. Configure Environment
Copy the example environment file:
```bash
cp .env.testing.example .env.testing
```

Edit `.env.testing` with your NetSuite credentials:
```env
# NetSuite Account Information
NETSUITE_BASE_URL=https://YOUR_ACCOUNT_ID.suitetalk.api.netsuite.com
NETSUITE_REALM=YOUR_ACCOUNT_ID

# OAuth Credentials
NETSUITE_CONSUMER_KEY=your_consumer_key_here
NETSUITE_CONSUMER_SECRET=your_consumer_secret_here

# Access Token Credentials
NETSUITE_TOKEN_ID=your_token_id_here
NETSUITE_TOKEN_SECRET=your_token_secret_here

# Optional Configuration
NETSUITE_TIMEOUT=30
NETSUITE_RATE_LIMIT_DELAY=1
NETSUITE_TEST_ROOT_FOLDER_ID=
NETSUITE_TEST_PREFIX=test-automation/
```

### 3. Verify Credentials
Test your connection:
```bash
php test-connection.php
```

## Running Live Tests

### Basic Commands

```bash
# Run all live tests
composer test-live

# Run specific test classes
./vendor/bin/phpunit --configuration=phpunit-live.xml tests/Live/LiveNetSuiteClientTest.php

# Run with coverage
composer test-live-coverage

# Run all tests (mock + live)
composer test-all
```

### Test Suites

```bash
# Unit tests only (fast, no NetSuite)
composer test-unit

# Integration tests only (fast, no NetSuite)
composer test-integration

# Live tests only (slow, requires NetSuite)
composer test-live

# All tests
composer test
```

## Test Categories

### LiveNetSuiteClientTest
Tests the NetSuite REST API client functionality:
- OAuth signature generation and validation
- Connection testing and error handling
- Rate limiting behavior
- HTTP method operations (GET, POST, PUT, DELETE)
- Timeout handling
- Error response processing

### LiveNetSuiteAdapterTest
Tests Flysystem adapter operations:
- File read/write operations
- File existence checking
- File metadata retrieval (size, MIME type, last modified)
- File copy and move operations
- Directory operations (if permissions allow)
- Content listing
- Large file handling
- Unicode and binary content support
- Error handling and edge cases

### LiveNetSuiteEndToEndTest
Tests complete workflows:
- Multi-step file operations
- Performance benchmarking
- Concurrent operations
- Error recovery scenarios
- Rate limit compliance
- Full system integration

## Safety Features

### Automatic Cleanup
- Test files are automatically deleted after each test
- Unique file names prevent conflicts
- Cleanup runs even if tests fail

### Test Isolation
- Optional root folder restriction (`NETSUITE_TEST_ROOT_FOLDER_ID`)
- Path prefixing (`NETSUITE_TEST_PREFIX`) for organization
- Test files use unique identifiers

### Rate Limiting
- Configurable delays between operations
- Respect for NetSuite API limits
- Performance monitoring

## Configuration Options

### Environment Variables

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `NETSUITE_BASE_URL` | NetSuite API base URL | - | Yes |
| `NETSUITE_REALM` | NetSuite account ID | - | Yes |
| `NETSUITE_CONSUMER_KEY` | OAuth consumer key | - | Yes |
| `NETSUITE_CONSUMER_SECRET` | OAuth consumer secret | - | Yes |
| `NETSUITE_TOKEN_ID` | Access token ID | - | Yes |
| `NETSUITE_TOKEN_SECRET` | Access token secret | - | Yes |
| `NETSUITE_TIMEOUT` | HTTP request timeout (seconds) | 30 | No |
| `NETSUITE_RATE_LIMIT_DELAY` | Delay between operations (seconds) | 1 | No |
| `NETSUITE_TEST_ROOT_FOLDER_ID` | Restrict tests to specific folder | - | No |
| `NETSUITE_TEST_PREFIX` | Path prefix for test files | test-automation/ | No |

### PHPUnit Configuration
Live tests use `phpunit-live.xml` with:
- Increased memory limit (256M)
- Extended execution time (300s)
- Separate coverage directory

## CI/CD Integration

### GitHub Actions Example
```yaml
name: Live Tests

on:
  schedule:
    - cron: '0 2 * * *'  # Daily at 2 AM
  workflow_dispatch:     # Manual trigger

jobs:
  live-tests:
    runs-on: ubuntu-latest
    if: ${{ github.repository == 'baja-foundry/flysystem-filecabinet' }}
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          
      - name: Install dependencies
        run: composer install --no-dev --optimize-autoloader
        
      - name: Run live tests
        run: composer test-live
        env:
          NETSUITE_BASE_URL: ${{ secrets.NETSUITE_BASE_URL }}
          NETSUITE_REALM: ${{ secrets.NETSUITE_REALM }}
          NETSUITE_CONSUMER_KEY: ${{ secrets.NETSUITE_CONSUMER_KEY }}
          NETSUITE_CONSUMER_SECRET: ${{ secrets.NETSUITE_CONSUMER_SECRET }}
          NETSUITE_TOKEN_ID: ${{ secrets.NETSUITE_TOKEN_ID }}
          NETSUITE_TOKEN_SECRET: ${{ secrets.NETSUITE_TOKEN_SECRET }}
```

## Troubleshooting

### Tests are Skipped
**Problem**: All live tests show as skipped  
**Cause**: Missing or invalid NetSuite credentials  
**Solution**: 
1. Verify `.env.testing` file exists and has correct values
2. Run `php test-connection.php` to verify credentials
3. Check NetSuite permissions and integration status

### Authentication Errors
**Problem**: 401 Unauthorized or invalid signature errors  
**Cause**: Incorrect OAuth credentials or expired tokens  
**Solution**:
1. Verify consumer key/secret from Integration record
2. Regenerate access token if expired
3. Ensure account ID matches realm parameter
4. Check integration is enabled in NetSuite

### Rate Limiting Issues
**Problem**: 429 Too Many Requests errors  
**Cause**: Exceeding NetSuite API rate limits  
**Solution**:
1. Increase `NETSUITE_RATE_LIMIT_DELAY`
2. Run tests during off-peak hours
3. Reduce test parallelism
4. Contact NetSuite to review rate limits

### Permission Errors
**Problem**: 403 Forbidden or insufficient permission errors  
**Cause**: NetSuite role lacks required permissions  
**Solution**:
1. Add SuiteQL permissions to role
2. Grant FileCabinet full access
3. Ensure integration has proper permissions
4. Contact NetSuite administrator

### Test Failures
**Problem**: Individual tests failing inconsistently  
**Cause**: NetSuite environment variability  
**Solution**:
1. Run tests multiple times to identify patterns
2. Check NetSuite system status
3. Verify test environment stability
4. Review NetSuite audit logs

## Best Practices

### Development
- **Always use Sandbox** environments for live testing
- **Run mock tests first** before live tests
- **Monitor rate limits** and adjust delays accordingly
- **Clean up test data** regularly

### Security
- **Never commit credentials** to version control
- **Use separate tokens** for testing vs production
- **Rotate credentials** regularly
- **Monitor API usage** for unauthorized access

### Performance
- **Run live tests sparingly** due to rate limits
- **Use appropriate delays** between operations
- **Monitor test execution time** and optimize as needed
- **Parallelize carefully** to avoid rate limit issues

### Maintenance
- **Keep credentials updated** as tokens expire
- **Monitor NetSuite API changes** that might affect tests
- **Update test data** when NetSuite configurations change
- **Review test results** regularly for patterns

## Limitations

### NetSuite API Constraints
- Rate limiting based on license type
- File size limits (typically 10MB for REST API)
- Concurrent request limitations
- Sandbox vs Production differences

### Test Environment
- Tests require real NetSuite account
- Network connectivity dependency
- Potential for test data accumulation
- Timing sensitivity due to rate limits

### Permissions
- Some operations may require elevated permissions
- Directory operations might be restricted
- Integration permissions vary by NetSuite edition

## Getting Help

### Resources
- [NetSuite SuiteTalk REST Documentation](https://docs.oracle.com/en/cloud/saas/netsuite/ns-online-help/chapter_1540391670.html)
- [Token-Based Authentication Guide](https://docs.oracle.com/en/cloud/saas/netsuite/ns-online-help/section_4247337262.html)
- [Package Documentation](README.md)
- [Connection Testing Guide](CONNECTION_TESTING.md)

### Support
1. **Check test output** for specific error messages
2. **Review NetSuite logs** in Setup > Integration > Web Services Usage Log
3. **Verify permissions** in Setup > Users/Roles > Manage Roles
4. **Contact NetSuite support** for API-specific issues
5. **Open GitHub issue** for package-related problems

---

**Important**: Live tests are designed to be safe and non-destructive, but always use a dedicated test environment and monitor your NetSuite usage to avoid unexpected charges or data issues.