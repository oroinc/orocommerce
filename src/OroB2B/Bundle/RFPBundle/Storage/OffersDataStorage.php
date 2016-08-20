<?php

namespace Oro\Bundle\RFPBundle\Storage;

use Oro\Bundle\ProductBundle\Storage\AbstractSessionDataStorage;

class OffersDataStorage extends AbstractSessionDataStorage
{
    const OFFERS_DATA_KEY = 'offers';

    /** {@inheritdoc} */
    protected function getKey()
    {
        return self::OFFERS_DATA_KEY;
    }
}
