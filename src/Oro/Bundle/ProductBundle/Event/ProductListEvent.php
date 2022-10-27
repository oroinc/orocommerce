<?php

namespace Oro\Bundle\ProductBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * The base class for classes containing data for product list related events.
 */
abstract class ProductListEvent extends Event
{
    private string $productListType;

    public function __construct(string $productListType)
    {
        $this->productListType = $productListType;
    }

    public function getProductListType(): string
    {
        return $this->productListType;
    }
}
