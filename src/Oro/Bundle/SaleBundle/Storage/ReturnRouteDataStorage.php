<?php

namespace Oro\Bundle\SaleBundle\Storage;

use Oro\Bundle\ProductBundle\Storage\AbstractSessionDataStorage;

class ReturnRouteDataStorage extends AbstractSessionDataStorage
{
    const RETURN_ROUTE_DATA_KEY = 'return_route';

    /** {@inheritdoc} */
    protected function getKey()
    {
        return self::RETURN_ROUTE_DATA_KEY;
    }
}
