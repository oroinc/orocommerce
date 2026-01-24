<?php

namespace Oro\Bundle\RedirectBundle\Model\Exception;

/**
 * Exception thrown when invalid arguments are provided to redirect or slug operations.
 *
 * This exception extends the standard {@see \InvalidArgumentException} to provide domain-specific
 * error handling for the RedirectBundle. It is thrown when operations receive invalid input such as
 * malformed URLs, invalid slug prototypes, incorrect redirect types, or other argument validation failures
 * in slug generation, redirect management, or URL routing operations.
 * Developers implementing custom slug generators, redirect handlers, or URL validators should throw this exception
 * to maintain consistent error handling across the redirect and slug management subsystems.
 */
class InvalidArgumentException extends \InvalidArgumentException
{
}
