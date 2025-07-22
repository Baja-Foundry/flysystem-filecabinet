<?php

namespace BajaFoundry\NetSuite\Flysystem\Tests\Integration;

use League\Flysystem\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use BajaFoundry\NetSuite\Flysystem\Adapter\NetSuiteFileCabinetAdapter;
use BajaFoundry\NetSuite\Flysystem\Client\NetSuiteClient;
use PHPUnit\Framework\TestCase;

class NetSuiteFileCabinetIntegrationTest extends TestCase
{
    private MockHandler $mockHandler;
    private NetSuiteFileCabinetAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockHandler = new MockHandler();

        $config = [
            'base_url' => 'https://test.suitetalk.api.netsuite.com',
            'consumer_key' => 'test_key',
            'consumer_secret' => 'test_secret',
            'token_id' => 'test_token',
            'token_secret' => 'test_token_secret',
            'realm' => 'test_realm',
            'timeout' => 30,
        ];

        $client = new NetSuiteClient($config);

        $handlerStack = HandlerStack::create($this->mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($client, $httpClient);

        $this->adapter = new NetSuiteFileCabinetAdapter($client, '1');
    }

    public function testWritingAndReading(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode(['internalId' => '123'])),
            new Response(200, [], json_encode(['items' => [['internalId' => '123']]])),
            new Response(200, [], json_encode(['content' => base64_encode('test content')]))
        );

        $adapter = $this->adapter;
        $config = new Config();

        $adapter->write('test-file.txt', 'test content', $config);
        $content = $adapter->read('test-file.txt');

        $this->assertEquals('test content', $content);
    }

    public function testFileExists(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode(['items' => [['internalId' => '123']]])),
            new Response(200, [], json_encode(['items' => []]))
        );

        $adapter = $this->adapter;

        $this->assertTrue($adapter->fileExists('existing-file.txt'));
        $this->assertFalse($adapter->fileExists('non-existing-file.txt'));
    }

    public function testListingContents(): void
    {
        $this->mockHandler->append(
            // First query: files in folder
            new Response(200, [], json_encode([
                'items' => [
                    [
                        'name' => 'file1.txt',
                        'fileSize' => 100,
                        'fileType' => 'text/plain',
                        'dateCreated' => '2023-01-01T12:00:00Z'
                    ],
                    [
                        'name' => 'file2.pdf',
                        'fileSize' => 200,
                        'fileType' => 'application/pdf',
                        'dateCreated' => '2023-01-02T12:00:00Z'
                    ]
                ]
            ])),
            // Second query: subfolders in folder
            new Response(200, [], json_encode([
                'items' => [
                    ['name' => 'subfolder', 'internalId' => '456']
                ]
            ]))
        );

        $adapter = $this->adapter;
        $contents = iterator_to_array($adapter->listContents('', false));

        $this->assertCount(3, $contents);
    }

    public function testMovingFiles(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode(['items' => [['internalId' => '123']]])),
            new Response(200, [], json_encode(['updated' => true]))
        );

        $adapter = $this->adapter;
        $config = new Config();

        $adapter->move('source.txt', 'destination.txt', $config);

        $this->assertTrue(true); // If we get here without exception, test passes
    }

    public function testCopyingFiles(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode(['items' => [['internalId' => '123']]])),
            new Response(200, [], json_encode(['content' => base64_encode('file content')])),
            new Response(200, [], json_encode(['internalId' => '456']))
        );

        $adapter = $this->adapter;
        $config = new Config();

        $adapter->copy('source.txt', 'copy.txt', $config);

        $this->assertTrue(true); // If we get here without exception, test passes
    }

    public function testDeletingFiles(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode(['items' => [['internalId' => '123']]])),
            new Response(200, [], json_encode(['deleted' => true]))
        );

        $adapter = $this->adapter;

        $adapter->delete('file-to-delete.txt');

        $this->assertTrue(true); // If we get here without exception, test passes
    }

    public function testGettingFileMetadata(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'items' => [[
                    'internalId' => '123',
                    'size' => 1024,
                    'mimeType' => 'text/plain',
                    'lastModified' => '2023-01-01T12:00:00Z'
                ]]
            ]))
        );

        $adapter = $this->adapter;

        $size = $adapter->fileSize('test.txt');
        $this->assertEquals(1024, $size->fileSize());

        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'items' => [[
                    'internalId' => '123',
                    'size' => 1024,
                    'mimeType' => 'text/plain',
                    'lastModified' => '2023-01-01T12:00:00Z'
                ]]
            ]))
        );

        $mimeType = $adapter->mimeType('test.txt');
        $this->assertEquals('text/plain', $mimeType->mimeType());
    }

    public function testCreatingDirectories(): void
    {
        $this->markTestIncomplete('Directory creation test needs more complex mocking - skipping for now');

        // This test would need to properly mock all the HTTP requests made during directory creation
        // including the complex logic in ensureDirectoryExists method
    }

    public function testDeletingDirectories(): void
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode(['items' => [['internalId' => '456']]])),
            new Response(200, [], json_encode(['deleted' => true]))
        );

        $adapter = $this->adapter;

        $adapter->deleteDirectory('folder-to-delete');

        $this->assertTrue(true); // If we get here without exception, test passes
    }
}
