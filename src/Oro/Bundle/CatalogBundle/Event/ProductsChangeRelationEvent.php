<?php

namespace Oro\Bundle\CatalogBundle\Event;

use Oro\Bundle\ProductBundle\Entity\Product;

use Symfony\Component\EventDispatcher\Event;

class ProductsChangeRelationEvent extends Event
{
    const NAME = 'oro_catalog.event.products_change_relation';

    /** @var Product[] */
    private $products;

    /**
     * @param Product[] $products
     */
    public function __construct(array $products)
    {
        if (!$products) {
            throw new \InvalidArgumentException('At least one product must be passed');
        }

        $this->products = $products;
    }

    /**
     * @return Product[]
     */
    public function getProducts()
    {
        return $this->products;
    }
}
