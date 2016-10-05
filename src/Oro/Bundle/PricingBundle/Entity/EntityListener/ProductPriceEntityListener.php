<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;

class ProductPriceEntityListener extends BaseProductPriceEntityListener
{
    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName()
    {
        return ProductPrice::class;
    }
}
