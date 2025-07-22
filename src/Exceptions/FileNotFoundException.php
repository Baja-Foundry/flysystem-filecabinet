<?php

namespace BajaFoundry\NetSuite\Flysystem\Exceptions;

use League\Flysystem\UnableToReadFile;

/**
 * Exception thrown when a file is not found in NetSuite FileCabinet
 *
 * Extends Flysystem's UnableToReadFile exception to provide NetSuite-specific
 * file not found errors. This exception maintains compatibility with Flysystem's
 * exception hierarchy while providing convenient factory methods.
 *
 * @package BajaFoundry\NetSuite\Flysystem\Exceptions
 * @author  Baja Foundry <info@baja-foundry.com>
 * @since   1.0.0-beta.1
 */
class FileNotFoundException extends UnableToReadFile
{
    /**
     * Create a new file not found exception for the given path
     *
     * @param string $path The file path that was not found
     *
     * @return self New instance of the exception
     */
    public static function atPath(string $path): self
    {
        return new self("File not found at path: {$path}");
    }
}
