<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Event\ProductPriceRemove;
use Oro\Bundle\PricingBundle\Event\ProductPriceSaveAfterEvent;

class ProductPriceEntityListener extends BaseProductPriceEntityListener
{
    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName()
    {
        return ProductPrice::class;
    }

    public function onSave(ProductPriceSaveAfterEvent $event)
    {
        /** @var ProductPrice  $price */
        $args = $event->getEventArgs();
        $price = $args->getEntity();

        $idChanged = $args->hasChangedField('id');
        if (!$idChanged || ($idChanged && $args->getOldValue('id'))) {
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
