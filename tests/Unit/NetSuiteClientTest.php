<?php

namespace BajaFoundry\NetSuite\Flysystem\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use BajaFoundry\NetSuite\Flysystem\Client\NetSuiteClient;
use BajaFoundry\NetSuite\Flysystem\Exceptions\NetSuiteException;
use PHPUnit\Framework\TestCase;

class NetSuiteClientTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        $this->config = [
            'base_url' => 'https://example.suitetalk.api.netsuite.com',
            'consumer_key' => 'test_consumer_key',
            'consumer_secret' => 'test_consumer_secret',
            'token_id' => 'test_token_id',
            'token_secret' => 'test_token_secret',
            'realm' => 'test_realm',
            'timeout' => 30,
        ];
    }

    public function testGetRequestSuccess(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['success' => true, 'data' => 'test']))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new NetSuiteClient($this->config);
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($client, $httpClient);

        $result = $client->get('/test-endpoint');

        $this->assertEquals(['success' => true, 'data' => 'test'], $result);
    }

    public function testGetRequestWithException(): void
    {
        $mock = new MockHandler([
            new RequestException('Error Communicating with Server', new Request('GET', 'test'))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new NetSuiteClient($this->config);
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($client, $httpClient);

        $this->expectException(NetSuiteException::class);
        $this->expectExceptionMessage('Failed to GET /test-endpoint: Error Communicating with Server');

        $client->get('/test-endpoint');
    }

    public function testPostRequestSuccess(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['internalId' => '123']))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new NetSuiteClient($this->config);
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($client, $httpClient);

        $result = $client->post('/test-endpoint', ['name' => 'test']);

        $this->assertEquals(['internalId' => '123'], $result);
    }

    public function testPutRequestSuccess(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['updated' => true]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new NetSuiteClient($this->config);
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($client, $httpClient);

        $result = $client->put('/test-endpoint', ['name' => 'updated']);

        $this->assertEquals(['updated' => true], $result);
    }

    public function testDeleteRequestSuccess(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['deleted' => true]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new NetSuiteClient($this->config);
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($client, $httpClient);

        $result = $client->delete('/test-endpoint');

        $this->assertEquals(['deleted' => true], $result);
    }

    public function testUploadRequestSuccess(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['internalId' => '456']))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new NetSuiteClient($this->config);
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($client, $httpClient);

        $result = $client->upload('/upload-endpoint', 'file content', 'test.txt', 'text/plain');

        $this->assertEquals(['internalId' => '456'], $result);
    }

    public function testAuthHeadersGeneration(): void
    {
        $client = new NetSuiteClient($this->config);
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('getAuthHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($client, 'GET', '/test');

        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertStringStartsWith('OAuth ', $headers['Authorization']);
        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertEquals('application/json', $headers['Accept']);
    }

    public function testSignatureGeneration(): void
    {
        $client = new NetSuiteClient($this->config);
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('generateSignature');
        $method->setAccessible(true);

        $params = [
            'oauth_consumer_key' => 'test_key',
            'oauth_nonce' => 'test_nonce',
            'oauth_signature_method' => 'HMAC-SHA256',
            'oauth_timestamp' => '1234567890',
            'oauth_token' => 'test_token',
            'oauth_version' => '1.0',
        ];

        $signature = $method->invoke($client, 'GET', 'https://example.com/test', $params);

        $this->assertIsString($signature);
        $this->assertNotEmpty($signature);
    }
}
