<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Event\ProductPriceRemove;
use Oro\Bundle\PricingBundle\Event\ProductPriceSaveAfterEvent;

/**
 * Handles product price lifecycle events
 */
class ProductPriceEntityListener extends BaseProductPriceEntityListener
{
    #[\Override]
    protected function getEntityClassName()
    {
        return ProductPrice::class;
    }

    public function onSave(ProductPriceSaveAfterEvent $event)
    {
        /** @var ProductPrice  $price */
        $args = $event->getEventArgs();
        $price = $args->getObject();

        if ($args->getEntityChangeSet()) {
            $this->preUpdate($price, $args);
        } else {
            $this->postPersist($price);
        }
    }

    public function onRemove(ProductPriceRemove $event)
    {
        $this->preRemove($event->getPrice());
    }
}
