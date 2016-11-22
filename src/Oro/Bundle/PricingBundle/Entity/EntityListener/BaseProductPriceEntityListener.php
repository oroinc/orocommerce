<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;

abstract class BaseProductPriceEntityListener extends AbstractRuleEntityListener
{
    const FIELD_PRICE_LIST = 'priceList';
    const FIELD_PRODUCT = 'product';
    const FIELD_VALUE = 'value';

    /**
     * @param BaseProductPrice $price
     */
    public function postPersist(BaseProductPrice $price)
    {
        $this->recalculateByEntity($price->getProduct(), $price->getPriceList()->getId());
    }

    /**
     * @param BaseProductPrice $price
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(BaseProductPrice $price, PreUpdateEventArgs $event)
    {
        if (!$this->isPriceValueChanged($event)) {
            return;
        }

        $this->recalculateForOldValues($price, $event);
        $this->recalculateForNewValues($price, $event);
    }

    /**
     * @param BaseProductPrice $price
     * @param PreUpdateEventArgs $event
     */
    protected function recalculateForOldValues(BaseProductPrice $price, PreUpdateEventArgs $event)
    {
        $oldProduct = $price->getProduct();
        $oldPriceList = $price->getPriceList();
        if ($event->hasChangedField(self::FIELD_PRICE_LIST)) {
            /** @var PriceAttributePriceList $oldPriceList */
            $oldPriceList = $event->getOldValue(self::FIELD_PRICE_LIST);
        }
        if ($event->hasChangedField(self::FIELD_PRODUCT)) {
            /** @var Product $oldProduct */
            $oldProduct = $event->getOldValue(self::FIELD_PRODUCT);
        }

        if ($event->hasChangedField(self::FIELD_PRICE_LIST) || $event->hasChangedField(self::FIELD_PRODUCT)) {
            $this->recalculateByEntityFieldsUpdate(
                $event->getEntityChangeSet(),
                $oldProduct,
                $oldPriceList->getId()
            );
        }
    }

    /**
     * @param BaseProductPrice $price
     * @param PreUpdateEventArgs $event
     */
    protected function recalculateForNewValues(BaseProductPrice $price, PreUpdateEventArgs $event)
    {
        $this->recalculateByEntityFieldsUpdate(
            $event->getEntityChangeSet(),
            $price->getProduct(),
            $price->getPriceList()->getId()
        );
    }

    /**
     * @param PreUpdateEventArgs $event
     * @return bool
     */
    protected function isPriceValueChanged(PreUpdateEventArgs $event)
    {
        $changeSet = $event->getEntityChangeSet();

        // Skip price if only price value type changed
        if (count($changeSet) === 1 && $event->hasChangedField(self::FIELD_VALUE)) {
            $oldValue = $event->getOldValue(self::FIELD_VALUE);
            $newValue = $event->getNewValue(self::FIELD_VALUE);
            if (is_numeric($newValue) && (float)$oldValue === (float)$newValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param BaseProductPrice $price
     */
    public function preRemove(BaseProductPrice $price)
    {
        $this->recalculateByEntity($price->getProduct(), $price->getPriceList()->getId());
    }
}
