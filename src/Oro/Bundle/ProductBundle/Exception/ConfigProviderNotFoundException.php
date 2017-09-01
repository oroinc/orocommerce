<?php

namespace Oro\Bundle\ProductBundle\Exception;

class ConfigProviderNotFoundException extends \UnexpectedValueException
{
    /**
     * @param string $nonExistingType
     * @return ConfigProviderNotFoundException
     */
    static public function fromString($nonExistingType)
    {
        return new static(sprintf('You have requested a non-existing config provider "%s"', $nonExistingType));
    }
}
