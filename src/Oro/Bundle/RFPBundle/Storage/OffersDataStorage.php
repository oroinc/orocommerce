<?php

namespace Oro\Bundle\RFPBundle\Storage;

use Oro\Bundle\ProductBundle\Storage\AbstractSessionDataStorage;

/**
 * Implementation of a data storage for RFP offers.
 */
class OffersDataStorage extends AbstractSessionDataStorage
{
    public const OFFERS_DATA_KEY = 'offers';

    protected function getKey(): string
    {
        return self::OFFERS_DATA_KEY;
    }
}
