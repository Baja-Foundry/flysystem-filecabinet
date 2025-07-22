<?php

namespace BajaFoundry\NetSuite\Flysystem\Tests\Unit;

use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use Mockery;
use BajaFoundry\NetSuite\Flysystem\Adapter\NetSuiteFileCabinetAdapter;
use BajaFoundry\NetSuite\Flysystem\Client\NetSuiteClient;
use BajaFoundry\NetSuite\Flysystem\Exceptions\NetSuiteException;
use PHPUnit\Framework\TestCase;

class NetSuiteFileCabinetAdapterTest extends TestCase
{
    private $mockClient;
    private NetSuiteFileCabinetAdapter $adapter;

    protected function setUp(): void
    {
        $this->mockClient = Mockery::mock(NetSuiteClient::class);
        $this->adapter = new NetSuiteFileCabinetAdapter($this->mockClient, '1', '');
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testFileExistsReturnsTrueWhenFileExists(): void
    {
        // Mock for getFileMetadata (getFolderId not called for '.' dirname)
        $this->mockClient
            ->shouldReceive('get')
            ->with('/services/rest/query/v1/suiteql', Mockery::type('array'))
            ->andReturn(['items' => [['internalId' => '123', 'name' => 'test.txt']]])
            ->once();

        $this->assertTrue($this->adapter->fileExists('test.txt'));
    }

    public function testFileExistsReturnsFalseWhenFileDoesNotExist(): void
    {
        // Mock for getFileMetadata - file not found (getFolderId not called for '.' dirname)
        $this->mockClient
            ->shouldReceive('get')
            ->with('/services/rest/query/v1/suiteql', Mockery::type('array'))
            ->andReturn(['items' => []])
            ->once();

        $this->assertFalse($this->adapter->fileExists('test.txt'));
    }

    public function testWriteFileSuccess(): void
    {
        // With no prefix, pathinfo('test.txt')['dirname'] is '.' which should return root folder ID
        // No mocks needed for ensureDirectoryExists since '.' should return root directly

        // Mock for file creation
        $this->mockClient
            ->shouldReceive('post')
            ->with('/services/rest/record/v1/file', Mockery::type('array'))
            ->andReturn(['internalId' => '123'])
            ->once();

        $config = new Config();

        $this->adapter->write('test.txt', 'file content', $config);

        $this->assertTrue(true); // If we get here without exception, test passes
    }

    public function testWriteFileFailure(): void
    {
        // With no prefix, pathinfo('test.txt')['dirname'] is '.' which should return root folder ID
        // No mocks needed for ensureDirectoryExists since '.' should return root directly

        // Mock for file creation failure
        $this->mockClient
            ->shouldReceive('post')
            ->with('/services/rest/record/v1/file', Mockery::type('array'))
            ->andThrow(new NetSuiteException('API Error'))
            ->once();

        $this->expectException(UnableToWriteFile::class);

        $config = new Config();
        $this->adapter->write('test.txt', 'file content', $config);
    }

    public function testReadFileSuccess(): void
    {
        // Mock for getFileMetadata (getFolderId is not called for '.' dirname - returns root directly)
        $this->mockClient
            ->shouldReceive('get')
            ->with('/services/rest/query/v1/suiteql', Mockery::type('array'))
            ->andReturn(['items' => [['internalId' => '123']]])
            ->once();

        // Mock for file content retrieval
        $this->mockClient
            ->shouldReceive('get')
            ->with('/services/rest/record/v1/file/123')
            ->andReturn(['content' => base64_encode('file content')])
            ->once();

        $content = $this->adapter->read('test.txt');

        $this->assertEquals('file content', $content);
    }

    public function testReadFileNotFound(): void
    {
        // Mock for getFileMetadata - file not found (getFolderId not called for '.' dirname)
        $this->mockClient
            ->shouldReceive('get')
            ->with('/services/rest/query/v1/suiteql', Mockery::type('array'))
            ->andReturn(['items' => []])
            ->once();

        $this->expectException(UnableToReadFile::class);

        $this->adapter->read('nonexistent.txt');
    }

    public function testDeleteFileSuccess(): void
    {
        $this->mockClient
            ->shouldReceive('get')
            ->with('/services/rest/query/v1/suiteql', Mockery::type('array'))
            ->andReturn(['items' => [['internalId' => '123']]]);

        $this->mockClient
            ->shouldReceive('delete')
            ->with('/services/rest/record/v1/file/123')
            ->andReturn(['deleted' => true]);

        $this->adapter->delete('test.txt');

        $this->assertTrue(true); // If we get here without exception, test passes
    }

    public function testListContentsReturnsFiles(): void
    {
        // First query: files in folder
        $this->mockClient
            ->shouldReceive('get')
            ->with('/services/rest/query/v1/suiteql', Mockery::type('array'))
            ->andReturn([
                'items' => [
                    ['name' => 'file1.txt', 'fileSize' => 100, 'fileType' => 'text/plain'],
                    ['name' => 'file2.pdf', 'fileSize' => 200, 'fileType' => 'application/pdf']
                ]
            ])
            ->once();

        // Second query: subfolders in folder
        $this->mockClient
            ->shouldReceive('get')
            ->with('/services/rest/query/v1/suiteql', Mockery::type('array'))
            ->andReturn(['items' => []])
            ->once();

        $contents = iterator_to_array($this->adapter->listContents('', false));

        $this->assertCount(2, $contents);
        $this->assertInstanceOf(FileAttributes::class, $contents[0]);
        $this->assertInstanceOf(FileAttributes::class, $contents[1]);
    }

    public function testListContentsReturnsDirectories(): void
    {
        // First query: files in folder (empty)
        $this->mockClient
            ->shouldReceive('get')
            ->with('/services/rest/query/v1/suiteql', Mockery::type('array'))
            ->andReturn(['items' => []])
            ->once();

        // Second query: subfolders in folder
        $this->mockClient
            ->shouldReceive('get')
            ->with('/services/rest/query/v1/suiteql', Mockery::type('array'))
            ->andReturn([
                'items' => [
                    ['name' => 'subfolder', 'internalId' => '456']
                ]
            ])
            ->once();

        $contents = iterator_to_array($this->adapter->listContents('', false));

        $this->assertCount(1, $contents);
        $this->assertInstanceOf(DirectoryAttributes::class, $contents[0]);
    }

    public function testMoveFileSuccess(): void
    {
        $this->mockClient
            ->shouldReceive('get')
            ->with('/services/rest/query/v1/suiteql', Mockery::type('array'))
            ->andReturn(['items' => [['internalId' => '123']]]);

        $this->mockClient
            ->shouldReceive('put')
            ->with('/services/rest/record/v1/file/123', Mockery::type('array'))
            ->andReturn(['updated' => true]);

        $config = new Config();
        $this->adapter->move('old.txt', 'new.txt', $config);

        $this->assertTrue(true); // If we get here without exception, test passes
    }

    public function testCopyFileSuccess(): void
    {
        $this->mockClient
            ->shouldReceive('get')
            ->with('/services/rest/query/v1/suiteql', Mockery::type('array'))
            ->andReturn(['items' => [['internalId' => '123']]]);

        $this->mockClient
            ->shouldReceive('get')
            ->with('/services/rest/record/v1/file/123')
            ->andReturn(['content' => base64_encode('file content')]);

        $this->mockClient
            ->shouldReceive('post')
            ->with('/services/rest/record/v1/file', Mockery::type('array'))
            ->andReturn(['internalId' => '456']);

        $config = new Config();
        $this->adapter->copy('source.txt', 'destination.txt', $config);

        $this->assertTrue(true); // If we get here without exception, test passes
    }

    public function testFileSizeReturnsCorrectSize(): void
    {
        $this->mockClient
            ->shouldReceive('get')
            ->with('/services/rest/query/v1/suiteql', Mockery::type('array'))
            ->andReturn(['items' => [['size' => 1024, 'internalId' => '123']]]);

        $attributes = $this->adapter->fileSize('test.txt');

        $this->assertInstanceOf(FileAttributes::class, $attributes);
        $this->assertEquals(1024, $attributes->fileSize());
    }

    public function testMimeTypeReturnsCorrectType(): void
    {
        $this->mockClient
            ->shouldReceive('get')
            ->with('/services/rest/query/v1/suiteql', Mockery::type('array'))
            ->andReturn(['items' => [['mimeType' => 'text/plain', 'internalId' => '123']]]);

        $attributes = $this->adapter->mimeType('test.txt');

        $this->assertInstanceOf(FileAttributes::class, $attributes);
        $this->assertEquals('text/plain', $attributes->mimeType());
    }

    public function testLastModifiedReturnsCorrectTimestamp(): void
    {
        $this->mockClient
            ->shouldReceive('get')
            ->with('/services/rest/query/v1/suiteql', Mockery::type('array'))
            ->andReturn(['items' => [['lastModified' => '2023-01-01T12:00:00Z', 'internalId' => '123']]]);

        $attributes = $this->adapter->lastModified('test.txt');

        $this->assertInstanceOf(FileAttributes::class, $attributes);
        $this->assertIsInt($attributes->lastModified());
    }
}
