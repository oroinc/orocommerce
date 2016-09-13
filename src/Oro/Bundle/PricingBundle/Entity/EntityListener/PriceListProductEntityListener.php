<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;

use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;

class PriceListProductEntityListener extends AbstractRuleEntityListener
{
    /**
     * @param PriceListToProduct $priceListToProduct
     */
    public function preRemove(PriceListToProduct $priceListToProduct)
    {
        $this->recalculateByEntity($priceListToProduct->getProduct(), $priceListToProduct->getPriceList()->getId());
    }
    /**
     * @param PriceListToProduct $priceListToProduct
     */
    public function postPersist(PriceListToProduct $priceListToProduct)
    {
        $this->recalculateByEntity($priceListToProduct->getProduct(), $priceListToProduct->getPriceList()->getId());
    }
    /**
     * @param PriceListToProduct $priceListToProduct
     */
    public function preUpdate(PriceListToProduct $priceListToProduct)
    {
        $this->recalculateByEntity($priceListToProduct->getProduct(), $priceListToProduct->getPriceList()->getId());
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName()
    {
        return ProductPrice::class;
    }
}
