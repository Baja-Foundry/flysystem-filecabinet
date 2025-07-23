<?php

namespace BajaFoundry\NetSuite\Flysystem\Tests\Live;

use League\Flysystem\Filesystem;
use League\Flysystem\Config;

/**
 * End-to-end live tests for complete NetSuite FileCabinet workflows
 *
 * These tests simulate real-world usage scenarios against actual NetSuite environment.
 * Tests will be skipped if credentials are not available.
 */
class LiveNetSuiteEndToEndTest extends LiveTestCase
{
    private ?Filesystem $filesystem = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        if ($this->adapter) {
            $this->filesystem = new Filesystem($this->adapter);
        }
    }

    public function testCompleteFileWorkflow(): void
    {
        $filename = $this->generateTestFileName('.txt');
        $originalContent = 'Original content for workflow test - ' . date('Y-m-d H:i:s');
        $updatedContent = 'Updated content for workflow test - ' . date('Y-m-d H:i:s');
        
        // Step 1: Create file
        $this->filesystem->write($filename, $originalContent);
        
        $this->respectRateLimit();
        
        // Step 2: Verify file exists and has correct content
        $this->assertTrue($this->filesystem->fileExists($filename));
        $this->assertEquals($originalContent, $this->filesystem->read($filename));
        
        // Step 3: Update file content
        $this->filesystem->write($filename, $updatedContent);
        
        $this->respectRateLimit();
        
        // Step 4: Verify updated content
        $this->assertEquals($updatedContent, $this->filesystem->read($filename));
        
        // Step 5: Get file metadata
        $fileSize = $this->filesystem->fileSize($filename);
        $mimeType = $this->filesystem->mimeType($filename);
        $lastModified = $this->filesystem->lastModified($filename);
        
        $this->assertEquals(strlen($updatedContent), $fileSize);
        $this->assertIsString($mimeType);
        $this->assertIsInt($lastModified);
        
        // Step 6: Copy file
        $copyFilename = $this->generateTestFileName('.txt');
        $this->filesystem->copy($filename, $copyFilename);
        
        $this->respectRateLimit();
        
        // Step 7: Verify both files exist with same content
        $this->assertTrue($this->filesystem->fileExists($filename));
        $this->assertTrue($this->filesystem->fileExists($copyFilename));
        $this->assertEquals($updatedContent, $this->filesystem->read($copyFilename));
        
        // Step 8: Move original file
        $movedFilename = $this->generateTestFileName('.txt');
        $this->filesystem->move($filename, $movedFilename);
        
        $this->respectRateLimit();
        
        // Step 9: Verify move operation
        $this->assertFalse($this->filesystem->fileExists($filename));
        $this->assertTrue($this->filesystem->fileExists($movedFilename));
        $this->assertEquals($updatedContent, $this->filesystem->read($movedFilename));
        
        // Step 10: Clean up
        $this->filesystem->delete($copyFilename);
        $this->filesystem->delete($movedFilename);
        
        $this->respectRateLimit();
        
        // Step 11: Verify cleanup
        $this->assertFalse($this->filesystem->fileExists($copyFilename));
        $this->assertFalse($this->filesystem->fileExists($movedFilename));
        
        // Remove from cleanup lists since we already deleted them
        $this->testFiles = array_diff($this->testFiles, [$filename, $copyFilename, $movedFilename]);
    }

    public function testMultipleFileOperations(): void
    {
        $fileCount = 5;
        $filenames = [];
        $contents = [];
        
        // Create multiple files
        for ($i = 0; $i < $fileCount; $i++) {
            $filename = $this->generateTestFileName(".txt");
            $content = "Multi-file test content {$i} - " . date('Y-m-d H:i:s');
            
            $filenames[] = $filename;
            $contents[] = $content;
            
            $this->filesystem->write($filename, $content);
            
            // Small delay to avoid overwhelming NetSuite
            usleep(500000); // 0.5 seconds
        }
        
        $this->respectRateLimit();
        
        // Verify all files exist and have correct content
        for ($i = 0; $i < $fileCount; $i++) {
            $this->assertTrue($this->filesystem->fileExists($filenames[$i]));
            $this->assertEquals($contents[$i], $this->filesystem->read($filenames[$i]));
        }
        
        // Test listing contents - should find all our files
        $listedFiles = $this->filesystem->listContents('', false);
        $listedPaths = [];
        foreach ($listedFiles as $file) {
            $listedPaths[] = $file->path();
        }
        
        foreach ($filenames as $filename) {
            $this->assertContains($filename, $listedPaths, "File {$filename} should appear in directory listing");
        }
        
        // Clean up all files
        foreach ($filenames as $filename) {
            $this->filesystem->delete($filename);
            usleep(200000); // 0.2 seconds between deletions
        }
        
        $this->respectRateLimit();
        
        // Verify all files are deleted
        foreach ($filenames as $filename) {
            $this->assertFalse($this->filesystem->fileExists($filename));
        }
        
        // Remove from cleanup list
        $this->testFiles = array_diff($this->testFiles, $filenames);
    }

    public function testPerformanceBenchmark(): void
    {
        $this->markTestSkipped('Performance test - uncomment to run manually');
        
        // Uncomment to run performance benchmarks
        /*
        $filename = $this->generateTestFileName('.txt');
        $content = 'Performance test content';
        
        // Test write performance
        $startTime = microtime(true);
        $this->filesystem->write($filename, $content);
        $writeTime = microtime(true) - $startTime;
        
        $this->respectRateLimit();
        
        // Test read performance
        $startTime = microtime(true);
        $readContent = $this->filesystem->read($filename);
        $readTime = microtime(true) - $startTime;
        
        // Test existence check performance
        $startTime = microtime(true);
        $exists = $this->filesystem->fileExists($filename);
        $existsTime = microtime(true) - $startTime;
        
        // Verify operations succeeded
        $this->assertEquals($content, $readContent);
        $this->assertTrue($exists);
        
        // Log performance metrics (adjust thresholds based on your NetSuite instance)
        $this->assertLessThan(30, $writeTime, "Write operation took too long: {$writeTime}s");
        $this->assertLessThan(10, $readTime, "Read operation took too long: {$readTime}s");
        $this->assertLessThan(5, $existsTime, "Exists check took too long: {$existsTime}s");
        
        echo "\nPerformance Results:\n";
        echo "Write: " . number_format($writeTime, 3) . "s\n";
        echo "Read: " . number_format($readTime, 3) . "s\n";
        echo "Exists: " . number_format($existsTime, 3) . "s\n";
        */
    }

    public function testErrorRecovery(): void
    {
        $validFilename = $this->generateTestFileName('.txt');
        $invalidFilename = 'invalid/file/path/that/should/fail.txt';
        
        // Create a valid file first
        $this->filesystem->write($validFilename, 'Valid content');
        
        $this->respectRateLimit();
        
        // Verify valid file exists
        $this->assertTrue($this->filesystem->fileExists($validFilename));
        
        // Try to create invalid file (should fail)
        try {
            $this->filesystem->write($invalidFilename, 'Invalid content');
            $this->fail('Expected exception for invalid file path');
        } catch (\Exception $e) {
            // Expected to fail
            $this->addToAssertionCount(1);
        }
        
        // Verify the valid file still exists and works after the error
        $this->assertTrue($this->filesystem->fileExists($validFilename));
        $this->assertEquals('Valid content', $this->filesystem->read($validFilename));
        
        // Test invalid operations on non-existent files
        $nonExistentFile = 'definitely-does-not-exist-' . uniqid() . '.txt';
        
        try {
            $this->filesystem->read($nonExistentFile);
            $this->fail('Expected exception for non-existent file');
        } catch (\Exception $e) {
            $this->addToAssertionCount(1);
        }
        
        // Verify our valid file is still accessible
        $this->assertEquals('Valid content', $this->filesystem->read($validFilename));
    }

    public function testConcurrentOperations(): void
    {
        $this->markTestSkipped('Concurrent operations test - requires careful rate limit management');
        
        // This test would simulate concurrent file operations
        // Uncomment and modify based on your NetSuite rate limits
        /*
        $filenames = [];
        $processes = [];
        
        // Create multiple files in quick succession
        for ($i = 0; $i < 3; $i++) {
            $filename = $this->generateTestFileName("_concurrent_{$i}.txt");
            $filenames[] = $filename;
            
            // In a real concurrent test, you might use async operations or multiple processes
            $this->filesystem->write($filename, "Concurrent content {$i}");
            usleep(100000); // 0.1 second delay
        }
        
        $this->respectRateLimit();
        
        // Verify all files were created successfully
        foreach ($filenames as $i => $filename) {
            $this->assertTrue($this->filesystem->fileExists($filename));
            $this->assertEquals("Concurrent content {$i}", $this->filesystem->read($filename));
        }
        */
    }

    public function testConnectionRecovery(): void
    {
        // Test that connection can recover from temporary issues
        $filename = $this->generateTestFileName('.txt');
        
        // Perform initial operation
        $this->filesystem->write($filename, 'Before potential connection issue');
        
        $this->respectRateLimit();
        
        // Verify file was created
        $this->assertTrue($this->filesystem->fileExists($filename));
        
        // Simulate some time passing (during which connection might be reset)
        sleep(1);
        
        // Perform another operation - should work even if connection was reset
        $this->filesystem->write($filename, 'After potential connection reset');
        
        $this->respectRateLimit();
        
        // Verify the operation succeeded
        $this->assertEquals('After potential connection reset', $this->filesystem->read($filename));
    }

    public function testRateLimitCompliance(): void
    {
        $operationCount = 10;
        $startTime = microtime(true);
        
        // Perform multiple operations in sequence
        for ($i = 0; $i < $operationCount; $i++) {
            $filename = $this->generateTestFileName("_rate_limit_{$i}.txt");
            $this->filesystem->write($filename, "Rate limit test {$i}");
            
            // Respect rate limits
            $this->respectRateLimit();
        }
        
        $totalTime = microtime(true) - $startTime;
        
        // Should take reasonable time (not too fast, indicating rate limiting is working)
        $expectedMinTime = $operationCount * 0.5; // At least 0.5 seconds per operation
        $this->assertGreaterThan($expectedMinTime, $totalTime, 
            "Operations completed too quickly - rate limiting may not be working");
        
        // But not too slow either
        $expectedMaxTime = $operationCount * 60; // No more than 60 seconds per operation
        $this->assertLessThan($expectedMaxTime, $totalTime, 
            "Operations took too long - there may be an issue");
    }

    public function testFullSystemIntegration(): void
    {
        // Test complete integration including connection test
        $connectionResult = $this->adapter->testConnection();
        $this->assertTrue($connectionResult['success'], 'Initial connection test failed');
        
        // Test file operations through the adapter
        $filename = $this->generateTestFileName('.txt');
        $content = 'Full system integration test';
        
        // Write through adapter
        $this->adapter->write($filename, $content, new Config());
        
        $this->respectRateLimit();
        
        // Read through filesystem
        $readContent = $this->filesystem->read($filename);
        $this->assertEquals($content, $readContent);
        
        // Test metadata through adapter
        $fileSize = $this->adapter->fileSize($filename);
        $this->assertEquals(strlen($content), $fileSize->fileSize());
        
        // Test existence through filesystem
        $this->assertTrue($this->filesystem->fileExists($filename));
        
        // Final connection test
        $finalConnectionResult = $this->adapter->testConnection();
        $this->assertTrue($finalConnectionResult['success'], 'Final connection test failed');
    }
}