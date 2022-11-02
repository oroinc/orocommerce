<?php

namespace Oro\Bundle\SaleBundle\Storage;

use Oro\Bundle\ProductBundle\Storage\AbstractSessionDataStorage;

/**
 * Implementation of a data storage for storing return route.
 */
class ReturnRouteDataStorage extends AbstractSessionDataStorage
{
    public const RETURN_ROUTE_DATA_KEY = 'return_route';

    protected function getKey(): string
    {
        return self::RETURN_ROUTE_DATA_KEY;
    }
}
