<?php

namespace Oro\Bundle\ProductBundle\EventListener\Search;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Utils\IndexationEntitiesContainer;

/**
 * Schedule for reindex the parent configurable product when one of the product variants has changed
 */
class ReindexParentConfigurableProductListener
{
    /** @var IndexationEntitiesContainer */
    private $changedEntities;

    public function __construct(IndexationEntitiesContainer $changedEntities)
    {
        $this->changedEntities = $changedEntities;
    }

    public function postPersist(Product $product)
    {
        $this->populateProductIds($product);
    }

    public function postUpdate(Product $product)
    {
        $this->populateProductIds($product);
    }

    public function preRemove(Product $product)
    {
        $this->populateProductIds($product);
    }

    private function populateProductIds(Product $product)
    {
        if ($product->isVariant()) {
            foreach ($product->getParentVariantLinks() as $parentVariantLink) {
                $this->changedEntities->addEntity($parentVariantLink->getParentProduct());
            }
        }
    }
}
