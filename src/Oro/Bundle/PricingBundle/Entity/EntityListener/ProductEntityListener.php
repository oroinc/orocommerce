<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Handle product scalar attributes change that may affect prices recalculation.
 */
class ProductEntityListener extends AbstractRuleEntityListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    public function preUpdate(Product $product, PreUpdateEventArgs $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $this->recalculateByEntityFieldsUpdate($event->getEntityChangeSet(), $product);
    }

    public function postPersist(Product $product)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $this->recalculateByEntity($product);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName()
    {
        return Product::class;
    }
}
