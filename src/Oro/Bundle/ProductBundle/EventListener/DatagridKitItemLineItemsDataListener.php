<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Event\DatagridKitItemLineItemsDataEvent;

/**
 * Adds kit item line items basic data.
 */
class DatagridKitItemLineItemsDataListener
{
    public const ID = 'id';
    public const ENTITY = '_entity';
    public const KIT_ITEM_LABEL = 'kitItemLabel';

    private LocalizationHelper $localizationHelper;

    public function __construct(LocalizationHelper $localizationHelper)
    {
        $this->localizationHelper = $localizationHelper;
    }

    public function onLineItemData(DatagridKitItemLineItemsDataEvent $event): void
    {
        foreach ($event->getLineItems() as $lineItem) {
            $kitItemLineItemId = $lineItem->getEntityIdentifier();
            $kitItemLineItemData = [
                self::ID => 'productkititemlineitem:' . $kitItemLineItemId,
                self::ENTITY => $lineItem,
            ];

            $kitItem = $lineItem->getKitItem();
            if ($kitItem !== null) {
                $kitItemLineItemData[self::KIT_ITEM_LABEL] = (string)$this->localizationHelper
                    ->getLocalizedValue($kitItem->getLabels());
            }

            $event->addDataForLineItem($kitItemLineItemId, $kitItemLineItemData);
        }
    }
}
