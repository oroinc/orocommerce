<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Collects and adds the kit item line items data for each product kit line item.
 */
class DatagridKitLineItemsDataListener
{
    public const IS_KIT = 'isKit';
    public const SUB_DATA = 'subData';

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        $allKitItemLineItemsById = [];
        $kitItemLineItemsByIdGroupedByLineItem = [];

        foreach ($event->getLineItems() as $lineItemId => $lineItem) {
            if (!$lineItem instanceof ProductKitItemLineItemsAwareInterface) {
                continue;
            }

            $lineItemType = $event->getDataForLineItem($lineItemId)['type'] ?? '';
            if ($lineItemType !== Product::TYPE_KIT) {
                continue;
            }

            foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
                $id = $kitItemLineItem->getEntityIdentifier();
                $kitItemLineItemsByIdGroupedByLineItem[$lineItemId][$id] = $kitItemLineItem;
                $allKitItemLineItemsById[$id] = $kitItemLineItem;
            }
        }

        if ($allKitItemLineItemsById) {
            $kitItemLineItemsDataEvent = new DatagridLineItemsDataEvent(
                $allKitItemLineItemsById,
                [],
                $event->getDatagrid(),
                []
            );
            $this->eventDispatcher->dispatch($kitItemLineItemsDataEvent, $kitItemLineItemsDataEvent->getName());

            $allKitItemLineItemsData = $kitItemLineItemsDataEvent->getDataForAllLineItems();
            foreach ($kitItemLineItemsByIdGroupedByLineItem as $lineItemId => $kitItemLineItemsById) {
                $kitItemLineItemsData = array_intersect_key($allKitItemLineItemsData, $kitItemLineItemsById);
                $event->addDataForLineItem(
                    $lineItemId,
                    [self::IS_KIT => true, self::SUB_DATA => array_values($kitItemLineItemsData)]
                );
            }
        }
    }
}
