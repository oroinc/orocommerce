<?php

namespace Oro\Bundle\ProductBundle\Exception;

/**
 * Thrown when a default product unit cannot be found.
 *
 * This exception is raised when attempting to retrieve a default unit for a product or the system,
 * but no default unit has been configured or is available.
 */
class DefaultUnitNotFoundException extends \Exception
{
}
