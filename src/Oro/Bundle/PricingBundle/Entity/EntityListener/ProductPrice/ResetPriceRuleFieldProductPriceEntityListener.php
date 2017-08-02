<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener\ProductPrice;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;

class ResetPriceRuleFieldProductPriceEntityListener
{
    /**
     * @param ProductPrice       $productPrice
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(ProductPrice $productPrice, PreUpdateEventArgs $event)
    {
        $valueChanged = $event->getOldValue('value') != $event->getNewValue('value');

        if ($event->hasChangedField('quantity') ||
            $event->hasChangedField('unit') ||
            $valueChanged ||
            $event->hasChangedField('currency')) {
            $productPrice->setPriceRule(null);
        }
    }
}
