<?php

namespace Oro\Bundle\PricingBundle\Event;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Symfony\Component\EventDispatcher\Event;

class ProductPriceSaveAfterEvent extends Event
{
    const NAME = 'oro_pricing.product_price.save_after';

    /**
     * @var ProductPrice
     */
    protected $price;

    /**
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
