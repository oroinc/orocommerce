<?php

namespace Oro\Bundle\PricingBundle\Entity;

interface PriceTypeAwareInterface
{
    const PRICE_TYPE_UNIT = 10;
    const PRICE_TYPE_BUNDLED = 20;

    /**
     * @return int
     */
    public function getPriceType();
}
