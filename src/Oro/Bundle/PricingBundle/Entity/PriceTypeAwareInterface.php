<?php

namespace Oro\Bundle\PricingBundle\Entity;

interface PriceTypeAwareInterface
{
    public const PRICE_TYPE_UNIT = 10;
    public const PRICE_TYPE_BUNDLED = 20;

    /**
     * @return int
     */
    public function getPriceType();
}
