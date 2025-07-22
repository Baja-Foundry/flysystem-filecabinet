<?php

namespace BajaFoundry\NetSuite\Flysystem\Exceptions;

use Exception;

/**
 * Base exception for NetSuite API errors
 *
 * Thrown when NetSuite API operations fail, including authentication errors,
 * network issues, or invalid API responses. This exception wraps underlying
 * HTTP client exceptions and provides NetSuite-specific error context.
 *
 * @package BajaFoundry\NetSuite\Flysystem\Exceptions
 * @author  Baja Foundry <info@baja-foundry.com>
 * @since   1.0.0-beta.1
 */
class NetSuiteException extends Exception
{
    //
}
