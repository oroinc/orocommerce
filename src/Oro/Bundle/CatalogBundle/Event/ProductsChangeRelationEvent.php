<?php

namespace Oro\Bundle\CatalogBundle\Event;

use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when product relations change.
 *
 * Carries information about products whose relations have been modified, allowing listeners
 * to react to changes in product relationships and perform the necessary updates.
 */
class ProductsChangeRelationEvent extends Event
{
    public const NAME = 'oro_catalog.event.products_change_relation';

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
