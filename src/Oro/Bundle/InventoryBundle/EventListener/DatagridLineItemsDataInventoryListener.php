<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;

/**
 * Adds inventory line items data.
 */
class DatagridLineItemsDataInventoryListener
{
    /** @var UpcomingProductProvider */
    private $upcomingProductProvider;

    /** @var LowInventoryProvider */
    private $lowInventoryProvider;

    /** @var DateTimeFormatterInterface */
    private $formatter;

    /** @var LocaleSettings */
    private $localeSettings;

    public function __construct(
        UpcomingProductProvider $upcomingProductProvider,
        LowInventoryProvider $lowInventoryProvider,
        DateTimeFormatterInterface $formatter,
        LocaleSettings $localeSettings
    ) {
        $this->upcomingProductProvider = $upcomingProductProvider;
        $this->lowInventoryProvider = $lowInventoryProvider;
        $this->formatter = $formatter;
        $this->localeSettings = $localeSettings;
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        foreach ($event->getLineItems() as $lineItem) {
            $product = $lineItem->getProduct();
            if (!$product) {
                continue;
            }

            $lineItemData = [
                'inventoryStatus' => $product->getInventoryStatus()->getId(),
                'isLowInventory' => $this->lowInventoryProvider->isLowInventoryProduct($product),
                'isUpcoming' => $this->upcomingProductProvider->isUpcoming($product),
                'minimumQuantityToOrder' => $product->getMinimumQuantityToOrder()?->getScalarValue(),
                'maximumQuantityToOrder' => $product->getMaximumQuantityToOrder()?->getScalarValue()
            ];

            if ($lineItemData['isUpcoming']) {
                $availabilityDate = $this->upcomingProductProvider->getAvailabilityDate($product);
                if ($availabilityDate) {
                    $lineItemData['availabilityDate'] = $this->formatter
                        ->formatDate($availabilityDate, null, null, $this->localeSettings->getTimeZone());
                }
            }

            $event->addDataForLineItem($lineItem->getId(), $lineItemData);
        }
    }
}
