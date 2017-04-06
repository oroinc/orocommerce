<?php

namespace Oro\Bundle\PricingBundle\Event;

use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Symfony\Component\EventDispatcher\Event;

class ProductPriceRemove extends Event
{
    const NAME = 'oro_pricing.product_price.remove';

    /**
     * @var BaseProductPrice
     */
    protected $price;

    /**
     * ProductPriceRemove constructor.
     * @param ProductPrice $price
     */
    public function __construct(ProductPrice $price)
    {
        $this->price = $price;
    }

    /**
     * @return ProductPrice
     */
    public function getPrice()
    {
        return $this->price;
    }
}
