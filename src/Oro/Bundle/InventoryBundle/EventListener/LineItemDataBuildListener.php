<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ShoppingListBundle\Event\LineItemDataBuildEvent;

/**
 * Adds data to the LineItemDataBuildEvent.
 */
class LineItemDataBuildListener
{
    /** @var UpcomingProductProvider */
    private $upcomingProductProvider;

    /** @var LowInventoryProvider */
    private $lowInventoryProvider;

    /** @var DateTimeFormatterInterface */
    private $formatter;

    /** @var LocaleSettings */
    private $localeSettings;

    /**
     * @param UpcomingProductProvider $upcomingProductProvider
     * @param LowInventoryProvider $lowInventoryProvider
     * @param DateTimeFormatterInterface $formatter
     * @param LocaleSettings $localeSettings
     */
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

    /**
     * @param LineItemDataBuildEvent $event
     */
    public function onLineItemData(LineItemDataBuildEvent $event): void
    {
        foreach ($event->getLineItems() as $lineItem) {
            $product = $lineItem->getProduct();
            $status = $product->getInventoryStatus();
            $lineItemId = $lineItem->getId();
            $event->addDataForLineItem(
                $lineItemId,
                'inventoryStatus',
                ['name' => $status->getId(), 'label' => $status->getName()]
            );

            $isUpcoming = $this->upcomingProductProvider->isUpcoming($product);

            $event->addDataForLineItem($lineItemId, 'isUpcoming', $isUpcoming);

            if ($isUpcoming) {
                $availabilityDate = $this->upcomingProductProvider->getAvailabilityDate($product);

                if ($availabilityDate) {
                    $event->addDataForLineItem(
                        $lineItemId,
                        'availabilityDate',
                        $this->formatter->formatDate(
                            $availabilityDate,
                            null,
                            null,
                            $this->localeSettings->getTimeZone()
                        )
                    );
                }
            }

            $isLowInventory = $this->lowInventoryProvider->isLowInventoryProduct($product);

            $event->addDataForLineItem($lineItemId, 'isLowInventory', $isLowInventory);
        }
    }
}
