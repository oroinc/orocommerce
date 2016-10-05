<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;

class PriceAttributeProductPriceEntityListener extends BaseProductPriceEntityListener
{
    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName()
    {
        return PriceAttributeProductPrice::class;
    }
}
