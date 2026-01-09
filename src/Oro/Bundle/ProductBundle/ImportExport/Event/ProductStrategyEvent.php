<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Event;

use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched before and after product import strategy processing.
 *
 * This event allows listeners to modify or validate product data during import operations, providing access to
 * both the product entity and the raw import data for custom processing before or after the main import strategy logic.
 */
class ProductStrategyEvent extends Event
{
    public const PROCESS_BEFORE = 'oro_product.strategy.process_before';
    public const PROCESS_AFTER = 'oro_product.strategy.process_after';

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var array
     */
    protected $rawData = [];

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
