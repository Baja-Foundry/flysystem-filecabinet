<?php

namespace BajaFoundry\NetSuite\Flysystem\Tests\Live;

use BajaFoundry\NetSuite\Flysystem\Adapter\NetSuiteFileCabinetAdapter;
use BajaFoundry\NetSuite\Flysystem\Client\NetSuiteClient;
use PHPUnit\Framework\TestCase;

/**
 * Base class for live NetSuite integration tests
 *
 * Provides common functionality for tests that connect to real NetSuite environments.
 * Tests will be skipped if NetSuite credentials are not available.
 */
abstract class LiveTestCase extends TestCase
{
    protected ?NetSuiteClient $client = null;
    protected ?NetSuiteFileCabinetAdapter $adapter = null;
    protected array $testFiles = [];
    protected array $testFolders = [];
    protected string $testFolderPrefix = 'phpunit-test-';

    protected function setUp(): void
    {
        parent::setUp();

        if (!$this->hasNetSuiteCredentials()) {
            $this->markTestSkipped('NetSuite credentials not available. Set NETSUITE_* environment variables to run live tests.');
        }

        $this->client = $this->createNetSuiteClient();
        $this->adapter = $this->createNetSuiteAdapter();

        // Test connection before proceeding
        $connectionTest = $this->client->testConnection();
        if (!$connectionTest['success']) {
            $this->markTestSkipped('Cannot connect to NetSuite: ' . $connectionTest['message']);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files and folders
        $this->cleanupTestFiles();
        $this->cleanupTestFolders();

        parent::tearDown();
    }

    /**
     * Check if NetSuite credentials are available in environment
     */
    protected function hasNetSuiteCredentials(): bool
    {
        $requiredVars = [
            'NETSUITE_BASE_URL',
            'NETSUITE_CONSUMER_KEY',
            'NETSUITE_CONSUMER_SECRET',
            'NETSUITE_TOKEN_ID',
            'NETSUITE_TOKEN_SECRET',
            'NETSUITE_REALM'
        ];

        foreach ($requiredVars as $var) {
            if (empty($_ENV[$var]) && empty(getenv($var))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create NetSuite client with environment credentials
     */
    protected function createNetSuiteClient(): NetSuiteClient
    {
        return new NetSuiteClient([
            'base_url' => $this->getEnvVar('NETSUITE_BASE_URL'),
            'consumer_key' => $this->getEnvVar('NETSUITE_CONSUMER_KEY'),
            'consumer_secret' => $this->getEnvVar('NETSUITE_CONSUMER_SECRET'),
            'token_id' => $this->getEnvVar('NETSUITE_TOKEN_ID'),
            'token_secret' => $this->getEnvVar('NETSUITE_TOKEN_SECRET'),
            'realm' => $this->getEnvVar('NETSUITE_REALM'),
            'timeout' => (int) $this->getEnvVar('NETSUITE_TIMEOUT', '30'),
        ]);
    }

    /**
     * Create NetSuite adapter with optional test folder isolation
     */
    protected function createNetSuiteAdapter(): NetSuiteFileCabinetAdapter
    {
        $rootFolderId = $this->getEnvVar('NETSUITE_TEST_ROOT_FOLDER_ID', '');
        $prefix = $this->getEnvVar('NETSUITE_TEST_PREFIX', 'test-automation/');

        return new NetSuiteFileCabinetAdapter($this->client, $rootFolderId, $prefix);
    }

    /**
     * Generate a unique test file name
     */
    protected function generateTestFileName(string $suffix = '.txt'): string
    {
        $filename = $this->testFolderPrefix . uniqid() . $suffix;
        $this->testFiles[] = $filename;
        return $filename;
    }

    /**
     * Generate a unique test folder name
     */
    protected function generateTestFolderName(): string
    {
        $foldername = $this->testFolderPrefix . uniqid();
        $this->testFolders[] = $foldername;
        return $foldername;
    }

    /**
     * Clean up test files created during test
     */
    protected function cleanupTestFiles(): void
    {
        if (!$this->adapter) {
            return;
        }

        foreach ($this->testFiles as $filename) {
            try {
                if ($this->adapter->fileExists($filename)) {
                    $this->adapter->delete($filename);
                }
            } catch (\Exception $e) {
                // Log but don't fail test on cleanup errors
                error_log("Failed to cleanup test file {$filename}: " . $e->getMessage());
            }
        }

        $this->testFiles = [];
    }

    /**
     * Clean up test folders created during test
     */
    protected function cleanupTestFolders(): void
    {
        if (!$this->adapter) {
            return;
        }

        foreach ($this->testFolders as $foldername) {
            try {
                if ($this->adapter->directoryExists($foldername)) {
                    $this->adapter->deleteDirectory($foldername);
                }
            } catch (\Exception $e) {
                // Log but don't fail test on cleanup errors
                error_log("Failed to cleanup test folder {$foldername}: " . $e->getMessage());
            }
        }

        $this->testFolders = [];
    }

    /**
     * Get environment variable with optional default
     */
    protected function getEnvVar(string $name, string $default = ''): string
    {
        return $_ENV[$name] ?? getenv($name) ?: $default;
    }

    /**
     * Sleep to respect NetSuite rate limits
     */
    protected function respectRateLimit(): void
    {
        $rateLimitDelay = (int) $this->getEnvVar('NETSUITE_RATE_LIMIT_DELAY', '1');
        if ($rateLimitDelay > 0) {
            sleep($rateLimitDelay);
        }
    }

    /**
     * Assert that a file exists and has expected properties
     */
    protected function assertFileExistsInNetSuite(string $path, ?string $expectedContent = null): void
    {
        $this->assertTrue($this->adapter->fileExists($path), "File does not exist in NetSuite: {$path}");

        if ($expectedContent !== null) {
            $actualContent = $this->adapter->read($path);
            $this->assertEquals($expectedContent, $actualContent, "File content does not match expected content");
        }
    }

    /**
     * Assert that a directory exists in NetSuite
     */
    protected function assertDirectoryExistsInNetSuite(string $path): void
    {
        $this->assertTrue($this->adapter->directoryExists($path), "Directory does not exist in NetSuite: {$path}");
    }
}