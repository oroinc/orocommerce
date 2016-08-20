<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Handle product scalar attributes change that may affect prices recalculation.
 */
class ProductEntityListener extends AbstractRuleEntityListener
{
    /**
     * @param Product $product
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(Product $product, PreUpdateEventArgs $event)
    {
        $this->recalculateByEntityFieldsUpdate($event->getEntityChangeSet(), $product);
    }

    public function postPersist()
    {
        $this->recalculateByEntity();
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName()
    {
        return Product::class;
    }
}
