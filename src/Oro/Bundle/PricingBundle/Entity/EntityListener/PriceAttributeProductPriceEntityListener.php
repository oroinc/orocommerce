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

    #[\Override]
    public function postPersist(BaseProductPrice $price)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        parent::postPersist($price);
    }

    #[\Override]
    public function preUpdate(BaseProductPrice $price, PreUpdateEventArgs $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        return parent::preUpdate($price, $event);
    }

    #[\Override]
    public function preRemove(BaseProductPrice $price)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        parent::preRemove($price);
    }

    #[\Override]
    protected function getEntityClassName()
    {
        return PriceAttributeProductPrice::class;
    }
}
