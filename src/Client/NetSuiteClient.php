<?php

namespace BajaFoundry\NetSuite\Flysystem\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use BajaFoundry\NetSuite\Flysystem\Exceptions\NetSuiteException;

/**
 * NetSuite REST API Client with OAuth 1.0 Authentication
 *
 * Provides authenticated HTTP client for interacting with NetSuite's SuiteTalk REST API.
 * Handles OAuth 1.0 signature generation and all standard HTTP methods.
 *
 * @package BajaFoundry\NetSuite\Flysystem\Client
 * @author  Baja Foundry <info@baja-foundry.com>
 * @since   1.0.0-beta.1
 */
class NetSuiteClient
{
    /**
     * The HTTP client instance
     *
     * @var Client
     */
    private Client $httpClient;

    /**
     * NetSuite account base URL
     *
     * @var string
     */
    private string $baseUrl;

    /**
     * OAuth consumer key from NetSuite integration
     *
     * @var string
     */
    private string $consumerKey;

    /**
     * OAuth consumer secret from NetSuite integration
     *
     * @var string
     */
    private string $consumerSecret;

    /**
     * OAuth token ID from NetSuite access token
     *
     * @var string
     */
    private string $tokenId;

    /**
     * OAuth token secret from NetSuite access token
     *
     * @var string
     */
    private string $tokenSecret;

    /**
     * NetSuite account ID (realm)
     *
     * @var string
     */
    private string $realm;

    /**
     * Create a new NetSuite client instance
     *
     * @param array<string, mixed> $config Configuration array containing:
     *                                    - base_url: NetSuite account base URL
     *                                    - consumer_key: OAuth consumer key
     *                                    - consumer_secret: OAuth consumer secret
     *                                    - token_id: OAuth token ID
     *                                    - token_secret: OAuth token secret
     *                                    - realm: NetSuite account ID
     *                                    - timeout: Request timeout in seconds (optional, default: 30)
     *
     * @throws \InvalidArgumentException If required configuration is missing
     */
    public function __construct(array $config)
    {
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->consumerKey = $config['consumer_key'];
        $this->consumerSecret = $config['consumer_secret'];
        $this->tokenId = $config['token_id'];
        $this->tokenSecret = $config['token_secret'];
        $this->realm = $config['realm'];

        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $config['timeout'] ?? 30,
        ]);
    }

    /**
     * Perform a GET request to NetSuite API
     *
     * @param string               $endpoint API endpoint path
     * @param array<string, mixed> $params   Query parameters
     *
     * @return array<string, mixed> Decoded JSON response
     *
     * @throws NetSuiteException If the request fails
     */
    public function get(string $endpoint, array $params = []): array
    {
        try {
            $response = $this->httpClient->get($endpoint, [
                'headers' => $this->getAuthHeaders('GET', $endpoint),
                'query' => $params,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new NetSuiteException("Failed to GET {$endpoint}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Perform a POST request to NetSuite API
     *
     * @param string               $endpoint API endpoint path
     * @param array<string, mixed> $data     Request payload data
     *
     * @return array<string, mixed> Decoded JSON response
     *
     * @throws NetSuiteException If the request fails
     */
    public function post(string $endpoint, array $data = []): array
    {
        try {
            $response = $this->httpClient->post($endpoint, [
                'headers' => $this->getAuthHeaders('POST', $endpoint),
                'json' => $data,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new NetSuiteException("Failed to POST {$endpoint}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Perform a PUT request to NetSuite API
     *
     * @param string               $endpoint API endpoint path
     * @param array<string, mixed> $data     Request payload data
     *
     * @return array<string, mixed> Decoded JSON response
     *
     * @throws NetSuiteException If the request fails
     */
    public function put(string $endpoint, array $data = []): array
    {
        try {
            $response = $this->httpClient->put($endpoint, [
                'headers' => $this->getAuthHeaders('PUT', $endpoint),
                'json' => $data,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new NetSuiteException("Failed to PUT {$endpoint}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Perform a DELETE request to NetSuite API
     *
     * @param string $endpoint API endpoint path
     *
     * @return array<string, mixed> Decoded JSON response
     *
     * @throws NetSuiteException If the request fails
     */
    public function delete(string $endpoint): array
    {
        try {
            $response = $this->httpClient->delete($endpoint, [
                'headers' => $this->getAuthHeaders('DELETE', $endpoint),
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new NetSuiteException("Failed to DELETE {$endpoint}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Upload a file to NetSuite API using multipart form data
     *
     * @param string $endpoint API endpoint path
     * @param string $content  File content to upload
     * @param string $filename Name of the file
     * @param string $mimeType MIME type of the file
     *
     * @return array<string, mixed> Decoded JSON response
     *
     * @throws NetSuiteException If the upload fails
     */
    public function upload(string $endpoint, string $content, string $filename, string $mimeType = 'application/octet-stream'): array
    {
        try {
            $response = $this->httpClient->post($endpoint, [
                'headers' => $this->getAuthHeaders('POST', $endpoint),
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => $content,
                        'filename' => $filename,
                        'headers' => ['Content-Type' => $mimeType]
                    ]
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new NetSuiteException("Failed to upload to {$endpoint}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Test the connection to NetSuite FileCabinet
     *
     * Performs a simple SuiteQL query to verify authentication and API access.
     * This method queries for the root FileCabinet folder (id = -15) as a connectivity test.
     *
     * @return array<string, mixed> Connection test results containing:
     *                              - success: boolean indicating if connection succeeded
     *                              - message: human-readable status message
     *                              - data: API response data (on success)
     *                              - error: error message (on failure)
     */
    public function testConnection(): array
    {
        try {
            $response = $this->get('/services/rest/query/v1/suiteql', [
                'q' => 'SELECT id, name FROM folder WHERE id = -15'
            ]);

            return [
                'success' => true,
                'message' => 'Successfully connected to NetSuite FileCabinet',
                'data' => $response
            ];
        } catch (NetSuiteException $e) {
            return [
                'success' => false,
                'message' => 'Failed to connect to NetSuite FileCabinet: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate OAuth 1.0 authentication headers for API requests
     *
     * @param string $method   HTTP method (GET, POST, PUT, DELETE)
     * @param string $endpoint API endpoint path
     *
     * @return array<string, string> HTTP headers including Authorization
     */
    private function getAuthHeaders(string $method, string $endpoint): array
    {
        $timestamp = time();
        $nonce = bin2hex(random_bytes(16));

        $oauthParams = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_nonce' => $nonce,
            'oauth_signature_method' => 'HMAC-SHA256',
            'oauth_timestamp' => $timestamp,
            'oauth_token' => $this->tokenId,
            'oauth_version' => '1.0',
            'realm' => $this->realm,
        ];

        $signature = $this->generateSignature($method, $this->baseUrl . $endpoint, $oauthParams);
        $oauthParams['oauth_signature'] = $signature;

        $authHeader = 'OAuth ';
        $authHeader .= implode(',', array_map(function ($key, $value) {
            return $key . '="' . rawurlencode($value) . '"';
        }, array_keys($oauthParams), $oauthParams));

        return [
            'Authorization' => $authHeader,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Generate OAuth 1.0 signature using HMAC-SHA256
     *
     * Implements the OAuth 1.0 signature generation algorithm as required by NetSuite.
     * The signature base string is constructed from the HTTP method, URL, and parameters,
     * then signed with the consumer secret and token secret.
     *
     * @param string               $method HTTP method
     * @param string               $url    Complete URL for the request
     * @param array<string, mixed> $params OAuth parameters
     *
     * @return string Base64-encoded HMAC-SHA256 signature
     */
    private function generateSignature(string $method, string $url, array $params): string
    {
        $baseString = strtoupper($method) . '&' . rawurlencode($url) . '&';

        ksort($params);
        $paramString = '';
        foreach ($params as $key => $value) {
            if ($key !== 'realm') {
                $paramString .= $key . '=' . rawurlencode($value) . '&';
            }
        }
        $paramString = rtrim($paramString, '&');

        $baseString .= rawurlencode($paramString);

        $signingKey = rawurlencode($this->consumerSecret) . '&' . rawurlencode($this->tokenSecret);

        return base64_encode(hash_hmac('sha256', $baseString, $signingKey, true));
    }
}
