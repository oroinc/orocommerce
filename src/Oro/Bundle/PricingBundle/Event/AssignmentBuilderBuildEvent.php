<?php

namespace Oro\Bundle\PricingBundle\Event;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\EventDispatcher\Event;

class AssignmentBuilderBuildEvent extends Event
{
    const NAME = 'oro_pricing.assignment_rule_builder.build';

    /**
     * @var PriceList
     */
    protected $priceList;

    /**
     * @var Product
     */
    private $product;

    /**
     * @param PriceList $priceList
     * @param Product $product
     */
    public function __construct(PriceList $priceList, Product $product = null)
    {
        $this->priceList = $priceList;
        $this->product = $product;
    }

    /**
     * @return PriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }
}
