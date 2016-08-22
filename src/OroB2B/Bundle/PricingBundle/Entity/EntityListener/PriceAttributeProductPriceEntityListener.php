<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class PriceAttributeProductPriceEntityListener extends AbstractRuleEntityListener
{
    const FIELD_PRICE_LIST = 'priceList';
    const FIELD_PRODUCT = 'product';
    const FIELD_VALUE = 'value';

    /**
     * @param PriceAttributeProductPrice $price
     */
    public function postPersist(PriceAttributeProductPrice $price)
    {
        $this->recalculateByEntity($price->getProduct(), $price->getPriceList()->getId());
    }

    /**
     * @param PriceAttributeProductPrice $price
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(PriceAttributeProductPrice $price, PreUpdateEventArgs $event)
    {
        $changeSet = $event->getEntityChangeSet();

        // Skip price if only price value type changed
        if (count($changeSet) === 1 && $event->hasChangedField(self::FIELD_VALUE)) {
            $oldValue = $event->getOldValue(self::FIELD_VALUE);
            $newValue = $event->getNewValue(self::FIELD_VALUE);
            if (is_numeric($newValue) && (float)$oldValue === (float)$newValue) {
                return;
            }
        }

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
        // Schedule recalculation for old values
        if ($event->hasChangedField(self::FIELD_PRICE_LIST) || $event->hasChangedField(self::FIELD_PRODUCT)) {
            $this->recalculateByEntityFieldsUpdate(
                $changeSet,
                $oldProduct,
                $oldPriceList->getId()
            );
        }

        // Schedule recalculation for new values
        $this->recalculateByEntityFieldsUpdate(
            $changeSet,
            $price->getProduct(),
            $price->getPriceList()->getId()
        );
    }

    /**
     * @param PriceAttributeProductPrice $price
     */
    public function preRemove(PriceAttributeProductPrice $price)
    {
        $this->recalculateByEntity($price->getProduct(), $price->getPriceList()->getId());
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName()
    {
        return PriceAttributeProductPrice::class;
    }
}
