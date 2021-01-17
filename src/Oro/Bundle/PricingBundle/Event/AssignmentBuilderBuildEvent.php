<?php

namespace Oro\Bundle\PricingBundle\Event;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Contracts\EventDispatcher\Event;

class AssignmentBuilderBuildEvent extends Event
{
    const NAME = 'oro_pricing.assignment_rule_builder.build';

    /**
     * @var PriceList
     */
    protected $priceList;

    /**
     * @var array|Product[]
     */
    private $products;

    /**
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    public function __construct(PriceList $priceList, array $products = [])
    {
        $this->priceList = $priceList;
        $this->products = $products;
    }

    /**
     * @return PriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * @return array|Product[]
     */
    public function getProducts()
    {
        return $this->products;
    }
}
