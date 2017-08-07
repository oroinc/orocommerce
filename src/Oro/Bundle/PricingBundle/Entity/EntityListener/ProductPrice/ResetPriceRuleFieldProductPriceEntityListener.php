<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener\ProductPrice;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Manager\PriceManager;

class ResetPriceRuleFieldProductPriceEntityListener
{
    /**
     * @var PriceManager
     */
    private $priceManager;

    /**
     * @param PriceManager $priceManager
     */
    public function __construct(PriceManager $priceManager)
    {
        $this->priceManager = $priceManager;
    }

    /**
     * @param ProductPrice       $productPrice
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(ProductPrice $productPrice, PreUpdateEventArgs $event)
    {
        $valueChanged = false;
        if ($event->hasChangedField('value')) {
            $valueChanged = $event->getOldValue('value') != $event->getNewValue('value');
        }

        if ($event->hasChangedField('quantity') ||
            $event->hasChangedField('unit') ||
            $valueChanged ||
            $event->hasChangedField('currency')) {
            $productPrice->setPriceRule(null);

            $this->priceManager->persist($productPrice);
            $this->priceManager->flush();
        }
    }
}
