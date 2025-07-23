<?php

namespace BajaFoundry\NetSuite\Flysystem\Tests\Live;

use BajaFoundry\NetSuite\Flysystem\Exceptions\NetSuiteException;

/**
 * Live tests for NetSuiteClient against real NetSuite environment
 *
 * These tests require valid NetSuite credentials and will make actual API calls.
 * Tests will be skipped if credentials are not available.
 */
class LiveNetSuiteClientTest extends LiveTestCase
{
    public function testConnectionTest(): void
    {
        $result = $this->client->testConnection();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertTrue($result['success'], 'Connection test should succeed: ' . ($result['message'] ?? 'Unknown error'));
        
        if ($result['success']) {
            $this->assertArrayHasKey('data', $result);
            $this->assertIsArray($result['data']);
        }
    }

    public function testSuiteQLQuery(): void
    {
        // Test a simple SuiteQL query to verify API access
        $response = $this->client->get('/services/rest/query/v1/suiteql', [
            'q' => 'SELECT id, name FROM folder WHERE id = -15'
        ]);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('items', $response);
        $this->assertIsArray($response['items']);
        
        // Should have at least the root folder
        $this->assertNotEmpty($response['items']);
        
        // Check first item structure
        $firstItem = $response['items'][0];
        $this->assertArrayHasKey('id', $firstItem);
        $this->assertArrayHasKey('name', $firstItem);
        $this->assertEquals('-15', $firstItem['id']);
    }

    public function testInvalidEndpoint(): void
    {
        $this->expectException(NetSuiteException::class);
        
        $this->client->get('/services/rest/invalid/endpoint');
    }

    public function testRateLimiting(): void
    {
        $this->markTestSkipped('Rate limiting test - uncomment to run manually');
        
        // Uncomment to test rate limiting behavior
        /*
        $startTime = microtime(true);
        
        // Make multiple requests quickly
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->client->get('/services/rest/query/v1/suiteql', [
                    'q' => 'SELECT id FROM folder LIMIT 1'
                ]);
            } catch (NetSuiteException $e) {
                // Check if this is a rate limiting error
                if (strpos($e->getMessage(), 'rate') !== false || 
                    strpos($e->getMessage(), '429') !== false) {
                    $this->addToAssertionCount(1); // Rate limiting detected
                    break;
                }
                throw $e;
            }
        }
        
        $duration = microtime(true) - $startTime;
        $this->assertLessThan(30, $duration, 'Test should complete within 30 seconds');
        */
    }

    public function testOAuthSignatureGeneration(): void
    {
        // Test that OAuth signatures are generated correctly by making a successful request
        $response = $this->client->get('/services/rest/query/v1/suiteql', [
            'q' => 'SELECT COUNT(*) as total FROM folder'
        ]);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('items', $response);
        
        // If we get here, OAuth signature was valid
        $this->addToAssertionCount(1);
    }

    public function testPostRequest(): void
    {
        // Note: This test does not actually create a folder, it just tests POST functionality
        // We expect this to fail with permission/validation error, but not authentication error
        
        try {
            $this->client->post('/services/rest/record/v1/folder', [
                'name' => 'test-folder-' . uniqid(),
                'parent' => ['internalId' => '-15'] // Root folder
            ]);
            
            // If successful, we should clean up, but this is unlikely in test environment
            $this->addToAssertionCount(1);
            
        } catch (NetSuiteException $e) {
            $message = $e->getMessage();
            
            // We expect permission or validation errors, not authentication errors
            $this->assertStringNotContainsString('401', $message, 'Should not get authentication error');
            $this->assertStringNotContainsString('Unauthorized', $message, 'Should not get unauthorized error');
            $this->assertStringNotContainsString('Invalid login', $message, 'Should not get login error');
            
            // Common expected errors in test environments
            $expectedErrors = [
                '403', // Forbidden - insufficient permissions
                '400', // Bad request - validation error  
                'INSUFFICIENT_PERMISSION',
                'INVALID_KEY_OR_REF'
            ];
            
            $foundExpectedError = false;
            foreach ($expectedErrors as $expectedError) {
                if (strpos($message, $expectedError) !== false) {
                    $foundExpectedError = true;
                    break;
                }
            }
            
            $this->assertTrue(
                $foundExpectedError, 
                "Expected permission/validation error, got: {$message}"
            );
        }
    }

    public function testTimeout(): void
    {
        // Create client with very short timeout
        $shortTimeoutClient = new \BajaFoundry\NetSuite\Flysystem\Client\NetSuiteClient([
            'base_url' => $this->getEnvVar('NETSUITE_BASE_URL'),
            'consumer_key' => $this->getEnvVar('NETSUITE_CONSUMER_KEY'),
            'consumer_secret' => $this->getEnvVar('NETSUITE_CONSUMER_SECRET'),
            'token_id' => $this->getEnvVar('NETSUITE_TOKEN_ID'),
            'token_secret' => $this->getEnvVar('NETSUITE_TOKEN_SECRET'),
            'realm' => $this->getEnvVar('NETSUITE_REALM'),
            'timeout' => 1, // Very short timeout
        ]);

        try {
            $shortTimeoutClient->get('/services/rest/query/v1/suiteql', [
                'q' => 'SELECT id FROM folder LIMIT 1000' // Potentially slow query
            ]);
            
            // If it succeeds, the query was fast enough
            $this->addToAssertionCount(1);
            
        } catch (NetSuiteException $e) {
            // Should get timeout error
            $message = $e->getMessage();
            $this->assertTrue(
                strpos($message, 'timeout') !== false ||
                strpos($message, 'timed out') !== false ||
                strpos($message, 'Connection timeout') !== false,
                "Expected timeout error, got: {$message}"
            );
        }
    }

    public function testErrorHandling(): void
    {
        // Test malformed SuiteQL query
        try {
            $this->client->get('/services/rest/query/v1/suiteql', [
                'q' => 'INVALID SQL QUERY SYNTAX'
            ]);
            
            $this->fail('Expected NetSuiteException for invalid SQL');
            
        } catch (NetSuiteException $e) {
            $this->assertStringContainsString('Failed to GET', $e->getMessage());
            $this->assertInstanceOf(\GuzzleHttp\Exception\GuzzleException::class, $e->getPrevious());
        }
    }
}