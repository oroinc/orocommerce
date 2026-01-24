<?php

namespace Oro\Bundle\ProductBundle\Exception;

/**
 * Thrown when a requested configuration provider cannot be found.
 *
 * This exception is raised when attempting to retrieve a configuration provider by type,
 * but no provider is registered for the requested type.
 */
class ConfigProviderNotFoundException extends \UnexpectedValueException
{
    /**
     * @param string $nonExistingType
     * @return ConfigProviderNotFoundException
     */
    public static function fromString($nonExistingType)
    {
        return new static(sprintf('You have requested a non-existing config provider "%s"', $nonExistingType));
    }
}
