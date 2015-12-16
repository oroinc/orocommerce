<?php

namespace OroB2B\Bundle\RFPBundle\Storage;

use OroB2B\Bundle\ProductBundle\Storage\AbstractSessionDataStorage;

class OffersDataStorage extends AbstractSessionDataStorage
{
    const OFFERS_DATA_KEY = 'offers';

    /** {@inheritdoc} */
    protected function getKey()
    {
        return self::OFFERS_DATA_KEY;
    }
}
