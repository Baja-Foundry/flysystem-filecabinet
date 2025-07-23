<?php

namespace BajaFoundry\NetSuite\Flysystem\Tests\Live;

use League\Flysystem\Config;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;

/**
 * Live tests for NetSuiteFileCabinetAdapter against real NetSuite environment
 *
 * These tests perform actual file operations in NetSuite FileCabinet.
 * Tests will be skipped if credentials are not available.
 */
class LiveNetSuiteAdapterTest extends LiveTestCase
{
    public function testWriteAndReadFile(): void
    {
        $filename = $this->generateTestFileName();
        $content = 'Test content for live NetSuite test - ' . date('Y-m-d H:i:s');
        
        // Write file
        $this->adapter->write($filename, $content, new Config());
        
        $this->respectRateLimit();
        
        // Verify file exists
        $this->assertTrue($this->adapter->fileExists($filename));
        
        // Read file content
        $readContent = $this->adapter->read($filename);
        $this->assertEquals($content, $readContent);
    }

    public function testFileExists(): void
    {
        $filename = $this->generateTestFileName();
        
        // File should not exist initially
        $this->assertFalse($this->adapter->fileExists($filename));
        
        // Create file
        $this->adapter->write($filename, 'test content', new Config());
        
        $this->respectRateLimit();
        
        // File should now exist
        $this->assertTrue($this->adapter->fileExists($filename));
    }

    public function testDeleteFile(): void
    {
        $filename = $this->generateTestFileName();
        $content = 'Content to be deleted';
        
        // Create file
        $this->adapter->write($filename, $content, new Config());
        
        $this->respectRateLimit();
        
        // Verify file exists
        $this->assertTrue($this->adapter->fileExists($filename));
        
        // Delete file
        $this->adapter->delete($filename);
        
        $this->respectRateLimit();
        
        // File should no longer exist
        $this->assertFalse($this->adapter->fileExists($filename));
        
        // Remove from cleanup list since we already deleted it
        $this->testFiles = array_diff($this->testFiles, [$filename]);
    }

    public function testFileMetadata(): void
    {
        $filename = $this->generateTestFileName();
        $content = 'Test content for metadata validation';
        
        // Write file
        $this->adapter->write($filename, $content, new Config());
        
        $this->respectRateLimit();
        
        // Test file size
        $sizeAttributes = $this->adapter->fileSize($filename);
        $this->assertEquals(strlen($content), $sizeAttributes->fileSize());
        
        // Test MIME type (should detect text file)
        $mimeAttributes = $this->adapter->mimeType($filename);
        $mimeType = $mimeAttributes->mimeType();
        $this->assertIsString($mimeType);
        $this->assertNotEmpty($mimeType);
        
        // Test last modified
        $modifiedAttributes = $this->adapter->lastModified($filename);
        $lastModified = $modifiedAttributes->lastModified();
        $this->assertIsInt($lastModified);
        $this->assertGreaterThan(time() - 300, $lastModified); // Within last 5 minutes
    }

    public function testCopyFile(): void
    {
        $sourceFile = $this->generateTestFileName('.txt');
        $targetFile = $this->generateTestFileName('.txt');
        $content = 'Content to be copied';
        
        // Create source file
        $this->adapter->write($sourceFile, $content, new Config());
        
        $this->respectRateLimit();
        
        // Copy file
        $this->adapter->copy($sourceFile, $targetFile, new Config());
        
        $this->respectRateLimit();
        
        // Both files should exist
        $this->assertTrue($this->adapter->fileExists($sourceFile));
        $this->assertTrue($this->adapter->fileExists($targetFile));
        
        // Both should have same content
        $this->assertEquals($content, $this->adapter->read($sourceFile));
        $this->assertEquals($content, $this->adapter->read($targetFile));
    }

    public function testMoveFile(): void
    {
        $sourceFile = $this->generateTestFileName('.txt');
        $targetFile = $this->generateTestFileName('.txt');
        $content = 'Content to be moved';
        
        // Create source file
        $this->adapter->write($sourceFile, $content, new Config());
        
        $this->respectRateLimit();
        
        // Move file
        $this->adapter->move($sourceFile, $targetFile, new Config());
        
        $this->respectRateLimit();
        
        // Source file should not exist, target should exist
        $this->assertFalse($this->adapter->fileExists($sourceFile));
        $this->assertTrue($this->adapter->fileExists($targetFile));
        
        // Target should have original content
        $this->assertEquals($content, $this->adapter->read($targetFile));
        
        // Remove source from cleanup list since it was moved
        $this->testFiles = array_diff($this->testFiles, [$sourceFile]);
    }

    public function testDirectoryOperations(): void
    {
        $this->markTestSkipped('Directory operations may require special permissions in NetSuite');
        
        // Uncomment to test directory operations if permissions allow
        /*
        $folderName = $this->generateTestFolderName();
        
        // Directory should not exist initially
        $this->assertFalse($this->adapter->directoryExists($folderName));
        
        // Create directory
        $this->adapter->createDirectory($folderName, new Config());
        
        $this->respectRateLimit();
        
        // Directory should now exist
        $this->assertTrue($this->adapter->directoryExists($folderName));
        
        // Create a file in the directory
        $filename = $folderName . '/test-file.txt';
        $this->testFiles[] = $filename; // Add to cleanup
        
        $this->adapter->write($filename, 'File in subdirectory', new Config());
        
        $this->respectRateLimit();
        
        // File should exist in subdirectory
        $this->assertTrue($this->adapter->fileExists($filename));
        
        // List directory contents
        $contents = iterator_to_array($this->adapter->listContents($folderName, false));
        $this->assertNotEmpty($contents);
        
        // Clean up file first
        $this->adapter->delete($filename);
        
        $this->respectRateLimit();
        
        // Delete directory
        $this->adapter->deleteDirectory($folderName);
        
        $this->respectRateLimit();
        
        // Directory should no longer exist
        $this->assertFalse($this->adapter->directoryExists($folderName));
        
        // Remove from cleanup lists
        $this->testFiles = array_diff($this->testFiles, [$filename]);
        $this->testFolders = array_diff($this->testFolders, [$folderName]);
        */
    }

    public function testListContents(): void
    {
        $filename1 = $this->generateTestFileName('.txt');
        $filename2 = $this->generateTestFileName('.txt');
        
        // Create test files
        $this->adapter->write($filename1, 'Content 1', new Config());
        $this->adapter->write($filename2, 'Content 2', new Config());
        
        $this->respectRateLimit();
        
        // List contents of root directory (with test prefix)
        $contents = iterator_to_array($this->adapter->listContents('', false));
        
        // Should find our test files
        $filenames = array_map(function($item) {
            return $item->path();
        }, $contents);
        
        $this->assertContains($filename1, $filenames);
        $this->assertContains($filename2, $filenames);
    }

    public function testErrorHandling(): void
    {
        $nonExistentFile = 'non-existent-file-' . uniqid() . '.txt';
        
        // Reading non-existent file should throw exception
        $this->expectException(UnableToReadFile::class);
        $this->adapter->read($nonExistentFile);
    }

    public function testLargeFileHandling(): void
    {
        $filename = $this->generateTestFileName('.txt');
        
        // Create a moderately large content (1MB)
        $largeContent = str_repeat('Large file content for testing. ', 32768); // ~1MB
        
        // This might take a while, so increase timeout expectations
        $this->adapter->write($filename, $largeContent, new Config());
        
        // Give NetSuite time to process
        sleep(2);
        
        // Verify file was written correctly
        $this->assertTrue($this->adapter->fileExists($filename));
        
        // Test file size
        $sizeAttributes = $this->adapter->fileSize($filename);
        $this->assertEquals(strlen($largeContent), $sizeAttributes->fileSize());
        
        // Read back content (this might take time)
        $readContent = $this->adapter->read($filename);
        $this->assertEquals($largeContent, $readContent);
    }

    public function testSpecialCharactersInFilename(): void
    {
        // Test filename with special characters (that are valid in NetSuite)
        $filename = $this->testFolderPrefix . 'special-chars_test-file(1).txt';
        $this->testFiles[] = $filename;
        
        $content = 'Content with special filename';
        
        // Write file with special characters
        $this->adapter->write($filename, $content, new Config());
        
        $this->respectRateLimit();
        
        // Should be able to read it back
        $this->assertTrue($this->adapter->fileExists($filename));
        $this->assertEquals($content, $this->adapter->read($filename));
    }

    public function testUnicodeContent(): void
    {
        $filename = $this->generateTestFileName('.txt');
        $unicodeContent = 'Unicode test: 你好世界 🌍 café naïve résumé';
        
        // Write file with Unicode content
        $this->adapter->write($filename, $unicodeContent, new Config());
        
        $this->respectRateLimit();
        
        // Should be able to read Unicode content back correctly
        $readContent = $this->adapter->read($filename);
        $this->assertEquals($unicodeContent, $readContent);
    }

    public function testBinaryContent(): void
    {
        $filename = $this->generateTestFileName('.bin');
        
        // Create binary content
        $binaryContent = '';
        for ($i = 0; $i < 256; $i++) {
            $binaryContent .= chr($i);
        }
        
        // Write binary file
        $this->adapter->write($filename, $binaryContent, new Config());
        
        $this->respectRateLimit();
        
        // Read back and verify
        $readContent = $this->adapter->read($filename);
        $this->assertEquals($binaryContent, $readContent);
        $this->assertEquals(256, strlen($readContent));
    }
}