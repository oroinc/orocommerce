<?php

namespace Oro\Bundle\ProductBundle\Event;

use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Provides common functionality for events related to product duplication.
 *
 * This base class encapsulates both the newly created product and its source product,
 * allowing event listeners to access both entities during the duplication process.
 * Subclasses should extend this to create specific duplication events that are dispatched
 * at different stages of the product copying workflow.
 */
abstract class AbstractProductDuplicateEvent extends Event
{
    /**
     * @var Product
     */
    protected $product;

    /**
     * @var Product
     */
    protected $sourceProduct;

    public function __construct(Product $product, Product $sourceProduct)
    {
        $this->product = $product;
        $this->sourceProduct = $sourceProduct;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return Product
     */
    public function getSourceProduct()
    {
        return $this->sourceProduct;
    }
}
