<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\RelatedItem;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\RelatedItem\AssignerStrategyInterface;

class FakeAssignerStrategy implements AssignerStrategyInterface
{
    public $functionalityEnabled = true;

    public $addRelatedProductToItself = false;

    public $exceedLimit = false;

    /**
     * {@inheritdoc}
     */
    public function addRelations(Product $productFrom, array $productsTo)
    {
        if (!$this->functionalityEnabled) {
            throw new \LogicException('Related Items functionality is disabled.');
        }

        if ($this->addRelatedProductToItself) {
            throw new \InvalidArgumentException('It is not possible to create relations from product to itself.');
        }

        if ($this->exceedLimit) {
            throw new \OverflowException(
                'It is not possible to add more related items, because of the limit of relations.'
            );
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function removeRelations(Product $productFrom, array $productsTo)
    {
        return true;
    }
}
