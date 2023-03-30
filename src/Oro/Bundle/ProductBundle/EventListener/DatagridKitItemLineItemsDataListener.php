<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;

/**
 * Adds kit item line items basic data.
 */
class DatagridKitItemLineItemsDataListener
{
    private LocalizationHelper $localizationHelper;

    public function __construct(LocalizationHelper $localizationHelper)
    {
        $this->localizationHelper = $localizationHelper;
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        foreach ($event->getLineItems() as $lineItem) {
            if (!$lineItem instanceof ProductKitItemLineItemInterface) {
                continue;
            }

            $kitItemLineItemId = $lineItem->getEntityIdentifier();
            $kitItemLineItemData = [
                'id' => 'kit_item_line_item:' . $kitItemLineItemId,
            ];

            $kitItem = $lineItem->getKitItem();
            if ($kitItem !== null) {
                $kitItemLineItemData['kitItemLabel'] = (string)$this->localizationHelper
                    ->getLocalizedValue($kitItem->getLabels());
            }

            $event->addDataForLineItem($kitItemLineItemId, $kitItemLineItemData);
        }
    }
}
