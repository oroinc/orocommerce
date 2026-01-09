<?php

namespace Oro\Bundle\SaleBundle\Provider;

/**
 * Defines the contract for objects that provide a default option.
 *
 * Implementations return a default option value that can be used when no explicit option selection has been made.
 */
interface DefaultOptionAwareInterface
{
    /**
     * @return string
     */
    public function getDefaultOption();
}
