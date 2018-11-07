<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;

/**
 * Adds feature enable check to precede execution of existing events
 */
class PriceAttributeProductPriceEntityListener extends BaseProductPriceEntityListener implements
    FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * {@inheritdoc}
     */
    public function postPersist(BaseProductPrice $price)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        parent::postPersist($price);
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(BaseProductPrice $price, PreUpdateEventArgs $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        return parent::preUpdate($price, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function preRemove(BaseProductPrice $price)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        parent::preRemove($price);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName()
    {
        return PriceAttributeProductPrice::class;
    }
}
