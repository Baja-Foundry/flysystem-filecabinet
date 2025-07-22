<?php

namespace BajaFoundry\NetSuite\Flysystem\Adapter;

use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use BajaFoundry\NetSuite\Flysystem\Client\NetSuiteClient;
use BajaFoundry\NetSuite\Flysystem\Exceptions\NetSuiteException;

/**
 * NetSuite FileCabinet Flysystem Adapter
 *
 * Provides a Flysystem v3 compatible adapter for NetSuite's FileCabinet system.
 * Enables seamless file operations through NetSuite's SuiteTalk REST API with
 * OAuth 1.0 authentication, supporting all standard Flysystem operations.
 *
 * @package BajaFoundry\NetSuite\Flysystem\Adapter
 * @author  Baja Foundry <info@baja-foundry.com>
 * @since   1.0.0-beta.1
 */
class NetSuiteFileCabinetAdapter implements FilesystemAdapter
{
    /**
     * NetSuite API client instance
     *
     * @var NetSuiteClient
     */
    private NetSuiteClient $client;

    /**
     * Path prefixer for all file operations
     *
     * @var PathPrefixer
     */
    private PathPrefixer $prefixer;

    /**
     * Root folder ID to restrict operations (optional)
     *
     * @var string
     */
    private string $rootFolderId;

    /**
     * Create a new NetSuite FileCabinet adapter instance
     *
     * @param NetSuiteClient $client       Authenticated NetSuite API client
     * @param string         $rootFolderId Optional root folder ID to restrict operations
     * @param string         $prefix       Optional path prefix for all operations
     */
    public function __construct(NetSuiteClient $client, string $rootFolderId = '', string $prefix = '')
    {
        $this->client = $client;
        $this->rootFolderId = $rootFolderId;
        $this->prefixer = new PathPrefixer($prefix);
    }

    /**
     * Check if a file exists in NetSuite FileCabinet
     *
     * @param string $path File path to check
     *
     * @return bool True if file exists, false otherwise
     */
    public function fileExists(string $path): bool
    {
        try {
            $this->getFileMetadata($path);
            return true;
        } catch (UnableToRetrieveMetadata) {
            return false;
        }
    }

    /**
     * Check if a directory exists in NetSuite FileCabinet
     *
     * @param string $path Directory path to check
     *
     * @return bool True if directory exists, false otherwise
     */
    public function directoryExists(string $path): bool
    {
        try {
            $this->getDirectoryMetadata($path);
            return true;
        } catch (UnableToCheckDirectoryExistence) {
            return false;
        }
    }

    /**
     * Write a file to NetSuite FileCabinet
     *
     * @param string $path     File path
     * @param string $contents File contents
     * @param Config $config   Write configuration
     *
     * @throws UnableToWriteFile If the file cannot be written
     */
    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $pathInfo = pathinfo($this->prefixer->prefixPath($path));
            $folderId = $this->ensureDirectoryExists($pathInfo['dirname'] ?? '');

            $response = $this->client->post('/services/rest/record/v1/file', [
                'name' => $pathInfo['basename'],
                'content' => base64_encode($contents),
                'folder' => ['internalId' => $folderId],
                'fileType' => $this->getMimeType($pathInfo['extension'] ?? ''),
            ]);

            if (!isset($response['internalId'])) {
                throw new UnableToWriteFile("Failed to write file: {$path}");
            }
        } catch (NetSuiteException $e) {
            throw new UnableToWriteFile("Failed to write file: {$path}", 0, $e);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $streamContents = stream_get_contents($contents);
        $this->write($path, $streamContents, $config);
    }

    /**
     * Read a file from NetSuite FileCabinet
     *
     * @param string $path File path to read
     *
     * @return string File contents
     *
     * @throws UnableToReadFile If the file cannot be read
     */
    public function read(string $path): string
    {
        try {
            $fileId = $this->getFileId($path);
            $response = $this->client->get("/services/rest/record/v1/file/{$fileId}");

            if (!isset($response['content'])) {
                throw new UnableToReadFile("File content not found: {$path}");
            }

            return base64_decode($response['content']);
        } catch (UnableToRetrieveMetadata $e) {
            throw new UnableToReadFile("Failed to read file: {$path}", 0, $e);
        } catch (NetSuiteException $e) {
            throw new UnableToReadFile("Failed to read file: {$path}", 0, $e);
        }
    }

    public function readStream(string $path)
    {
        $content = $this->read($path);
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        return $stream;
    }

    /**
     * Delete a file from NetSuite FileCabinet
     *
     * @param string $path File path to delete
     *
     * @throws UnableToDeleteFile If the file cannot be deleted
     */
    public function delete(string $path): void
    {
        try {
            $fileId = $this->getFileId($path);
            $this->client->delete("/services/rest/record/v1/file/{$fileId}");
        } catch (NetSuiteException $e) {
            throw new UnableToDeleteFile("Failed to delete file: {$path}", 0, $e);
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            $folderId = $this->getFolderId($path);
            $this->client->delete("/services/rest/record/v1/folder/{$folderId}");
        } catch (NetSuiteException $e) {
            throw new UnableToDeleteDirectory("Failed to delete directory: {$path}", 0, $e);
        }
    }

    /**
     * Create a directory in NetSuite FileCabinet
     *
     * @param string $path   Directory path to create
     * @param Config $config Directory configuration
     *
     * @throws UnableToCreateDirectory If the directory cannot be created
     */
    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->ensureDirectoryExists($path);
        } catch (NetSuiteException $e) {
            throw new UnableToCreateDirectory("Failed to create directory: {$path}", 0, $e);
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw new UnableToSetVisibility("NetSuite FileCabinet does not support visibility settings");
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, null, Visibility::PRIVATE);
    }

    /**
     * Get the MIME type of a file
     *
     * @param string $path File path
     *
     * @return FileAttributes File attributes with MIME type
     *
     * @throws UnableToRetrieveMetadata If metadata cannot be retrieved
     */
    public function mimeType(string $path): FileAttributes
    {
        $metadata = $this->getFileMetadata($path);
        return new FileAttributes($path, null, null, null, $metadata['mimeType'] ?? 'application/octet-stream');
    }

    /**
     * Get the last modified timestamp of a file
     *
     * @param string $path File path
     *
     * @return FileAttributes File attributes with last modified timestamp
     *
     * @throws UnableToRetrieveMetadata If metadata cannot be retrieved
     */
    public function lastModified(string $path): FileAttributes
    {
        $metadata = $this->getFileMetadata($path);
        $timestamp = isset($metadata['lastModified']) ? strtotime($metadata['lastModified']) : null;
        return new FileAttributes($path, null, null, $timestamp);
    }

    /**
     * Get the size of a file
     *
     * @param string $path File path
     *
     * @return FileAttributes File attributes with size
     *
     * @throws UnableToRetrieveMetadata If metadata cannot be retrieved
     */
    public function fileSize(string $path): FileAttributes
    {
        $metadata = $this->getFileMetadata($path);
        return new FileAttributes($path, $metadata['size'] ?? null);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        try {
            $folderId = $this->getFolderId($path);
            $items = $this->getDirectoryContents($folderId, $deep);

            foreach ($items as $item) {
                yield $item;
            }
        } catch (NetSuiteException $e) {
            return [];
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $fileId = $this->getFileId($source);
            $destinationInfo = pathinfo($this->prefixer->prefixPath($destination));
            $destinationFolderId = $this->ensureDirectoryExists($destinationInfo['dirname'] ?? '');

            $this->client->put("/services/rest/record/v1/file/{$fileId}", [
                'name' => $destinationInfo['basename'],
                'folder' => ['internalId' => $destinationFolderId],
            ]);
        } catch (NetSuiteException $e) {
            throw new UnableToMoveFile("Failed to move file from {$source} to {$destination}", 0, $e);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $content = $this->read($source);
            $this->write($destination, $content, $config);
        } catch (NetSuiteException $e) {
            throw new UnableToCopyFile("Failed to copy file from {$source} to {$destination}", 0, $e);
        }
    }

    /**
     * Get the NetSuite internal ID for a file
     *
     * @param string $path File path
     *
     * @return string File internal ID
     *
     * @throws UnableToRetrieveMetadata If file metadata cannot be retrieved
     */
    private function getFileId(string $path): string
    {
        $metadata = $this->getFileMetadata($path);
        return $metadata['internalId'];
    }

    /**
     * Get the NetSuite internal ID for a folder
     *
     * @param string $path Folder path
     *
     * @return string Folder internal ID
     *
     * @throws UnableToCheckDirectoryExistence If folder cannot be found
     */
    private function getFolderId(string $path): string
    {
        if (empty($path) || $path === '/') {
            return $this->rootFolderId;
        }

        $prefixedPath = $this->prefixer->prefixPath($path);
        $response = $this->client->get('/services/rest/query/v1/suiteql', [
            'q' => "SELECT internalId FROM MediaItemFolder WHERE name = '" . basename($prefixedPath) . "'"
        ]);

        if (empty($response['items'])) {
            throw new UnableToCheckDirectoryExistence("Directory not found: {$path}");
        }

        return $response['items'][0]['internalId'];
    }

    /**
     * Get metadata for a file from NetSuite FileCabinet
     *
     * @param string $path File path
     *
     * @return array<string, mixed> File metadata
     */
    private function getFileMetadata(string $path): array
    {
        $prefixedPath = $this->prefixer->prefixPath($path);
        $filename = basename($prefixedPath);
        $dirname = dirname($prefixedPath);

        try {
            $folderId = $this->getFolderId($dirname === '.' ? '' : $dirname);
            $response = $this->client->get('/services/rest/query/v1/suiteql', [
                'q' => "SELECT * FROM File WHERE name = '{$filename}' AND folder = {$folderId}"
            ]);

            if (empty($response['items'])) {
                throw new UnableToRetrieveMetadata("File not found: {$path}");
            }

            return $response['items'][0];
        } catch (NetSuiteException $e) {
            throw new UnableToRetrieveMetadata("Failed to get file metadata: {$path}", 0, $e);
        }
    }

    /**
     * Get metadata for a directory from NetSuite FileCabinet
     *
     * @param string $path Directory path
     *
     * @return array<string, mixed> Directory metadata
     */
    private function getDirectoryMetadata(string $path): array
    {
        try {
            $folderId = $this->getFolderId($path);
            $response = $this->client->get("/services/rest/record/v1/folder/{$folderId}");
            return $response;
        } catch (NetSuiteException $e) {
            throw new UnableToCheckDirectoryExistence("Failed to get directory metadata: {$path}", 0, $e);
        }
    }

    private function ensureDirectoryExists(string $path): string
    {
        if (empty($path) || $path === '/' || $path === '.') {
            return $this->rootFolderId;
        }

        try {
            return $this->getFolderId($path);
        } catch (UnableToCheckDirectoryExistence) {
            $pathParts = explode('/', trim($path, '/'));
            $currentPath = '';
            $currentFolderId = $this->rootFolderId;

            foreach ($pathParts as $part) {
                $currentPath .= '/' . $part;
                try {
                    $currentFolderId = $this->getFolderId(trim($currentPath, '/'));
                } catch (UnableToCheckDirectoryExistence) {
                    $response = $this->client->post('/services/rest/record/v1/folder', [
                        'name' => $part,
                        'parent' => ['internalId' => $currentFolderId],
                    ]);
                    $currentFolderId = $response['internalId'];
                }
            }

            return $currentFolderId;
        }
    }

    /**
     * Get contents of a directory from NetSuite FileCabinet
     *
     * @param string $folderId Directory folder ID
     * @param bool   $deep     Whether to include subdirectories
     *
     * @return array<string, mixed> Directory contents
     */
    private function getDirectoryContents(string $folderId, bool $deep): array
    {
        $items = [];

        $filesResponse = $this->client->get('/services/rest/query/v1/suiteql', [
            'q' => "SELECT * FROM File WHERE folder = {$folderId}"
        ]);

        foreach ($filesResponse['items'] ?? [] as $file) {
            $path = $this->prefixer->stripPrefix($file['name']);
            $items[] = new FileAttributes(
                $path,
                $file['fileSize'] ?? null,
                null,
                isset($file['dateCreated']) ? strtotime($file['dateCreated']) : null,
                $file['fileType'] ?? 'application/octet-stream'
            );
        }

        $foldersResponse = $this->client->get('/services/rest/query/v1/suiteql', [
            'q' => "SELECT * FROM MediaItemFolder WHERE parent = {$folderId}"
        ]);

        foreach ($foldersResponse['items'] ?? [] as $folder) {
            $path = $this->prefixer->stripPrefix($folder['name']);
            $items[] = new DirectoryAttributes($path);

            if ($deep) {
                $subItems = $this->getDirectoryContents($folder['internalId'], true);
                foreach ($subItems as $subItem) {
                    if ($subItem instanceof FileAttributes) {
                        $items[] = new FileAttributes(
                            $path . '/' . $subItem->path(),
                            $subItem->fileSize(),
                            $subItem->visibility(),
                            $subItem->lastModified(),
                            $subItem->mimeType()
                        );
                    } elseif ($subItem instanceof DirectoryAttributes) {
                        $items[] = new DirectoryAttributes($path . '/' . $subItem->path());
                    }
                }
            }
        }

        return $items;
    }

    /**
     * Get MIME type for a file extension
     *
     * @param string $extension File extension
     *
     * @return string MIME type
     */
    private function getMimeType(string $extension): string
    {
        $mimeTypes = [
            'txt' => 'text/plain',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'zip' => 'application/zip',
            'csv' => 'text/csv',
            'json' => 'application/json',
            'xml' => 'application/xml',
        ];

        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }

    /**
     * Test the connection to NetSuite FileCabinet
     *
     * Delegates to the underlying NetSuite client to perform connectivity testing.
     * This method verifies OAuth authentication and API access.
     *
     * @return array<string, mixed> Connection test results
     *
     * @see NetSuiteClient::testConnection()
     */
    public function testConnection(): array
    {
        return $this->client->testConnection();
    }
}
