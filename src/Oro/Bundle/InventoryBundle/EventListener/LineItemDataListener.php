<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ShoppingListBundle\Event\LineItemDataEvent;

/**
 * Adds data to the LineItemDataEvent.
 */
class LineItemDataListener
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
     * @param LineItemDataEvent $event
     */
    public function onLineItemData(LineItemDataEvent $event): void
    {
        foreach ($event->getLineItems() as $lineItem) {
            $isUpcoming = $this->upcomingProductProvider->isUpcoming($lineItem->getProduct());

            $event->addDataForLineItem($lineItem->getId(), 'isUpcoming', $isUpcoming);

            if ($isUpcoming) {
                $availabilityDate = $this->upcomingProductProvider->getAvailabilityDate($lineItem->getProduct());

                if ($availabilityDate) {
                    $event->addDataForLineItem(
                        $lineItem->getId(),
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

            $isLowInventory = $this->lowInventoryProvider->isLowInventoryProduct($lineItem->getProduct());

            $event->addDataForLineItem($lineItem->getId(), 'isLowInventory', $isLowInventory);
        }
    }
}
