<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use OroB2B\Bundle\ProductBundle\Entity\Product;

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
