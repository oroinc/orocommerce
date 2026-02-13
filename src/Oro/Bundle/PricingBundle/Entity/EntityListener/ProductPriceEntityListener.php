<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Event\ProductPriceRemove;
use Oro\Bundle\PricingBundle\Event\ProductPriceSaveAfterEvent;

/**
 * Handles product price lifecycle events.
 *
 * Version presence in changeset indicates mass operation, so we should not recalculate prices per-entity.
 * All prices should be later recalculated by PriceList::class `prices` field
 * like this is done in ImportExportResultListener.
 * On update, it's important to run recalculation for old values if pricelist was changed,
 * such change can't be processed by a mass message that contains new price list id only.
 */
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
        $price = $args->getObject();

        if ($args->getEntityChangeSet()) {
            $this->preUpdate($price, $args);
        } elseif (!$price->getVersion()) {
            $this->postPersist($price);
        }
    }

    protected function recalculateForNewValues(BaseProductPrice $price, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField('version')) {
            return;
        }

        parent::recalculateForNewValues($price, $event);
    }

    public function onRemove(ProductPriceRemove $event)
    {
        $this->preRemove($event->getPrice());
    }
}
