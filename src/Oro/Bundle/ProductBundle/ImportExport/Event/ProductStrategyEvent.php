<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\ProductBundle\Entity\Product;

class ProductStrategyEvent extends Event
{
    const PROCESS_BEFORE = 'orob2b_product.strategy.process_before';
    const PROCESS_AFTER = 'orob2b_product.strategy.process_after';

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var array
     */
    protected $rawData = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(Product $product, array $rawData)
    {
        $this->product = $product;
        $this->rawData = $rawData;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return array
     */
    public function getRawData()
    {
        return $this->rawData;
    }
}
