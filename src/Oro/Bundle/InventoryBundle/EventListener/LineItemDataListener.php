<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

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
    private $provider;

    /** @var DateTimeFormatterInterface */
    private $formatter;

    /** @var LocaleSettings */
    private $localeSettings;

    public function __construct(
        UpcomingProductProvider $provider,
        DateTimeFormatterInterface $formatter,
        LocaleSettings $localeSettings
    ) {
        $this->provider = $provider;
        $this->formatter = $formatter;
        $this->localeSettings = $localeSettings;
    }

    /**
     * @param LineItemDataEvent $event
     */
    public function onLineItemData(LineItemDataEvent $event): void
    {
        foreach ($event->getLineItems() as $lineItem) {
            $isUpcoming = $this->provider->isUpcoming($lineItem->getProduct());

            $event->addDataForLineItem($lineItem->getId(), 'isUpcoming', $isUpcoming);

            if ($isUpcoming) {
                $availabilityDate = $this->provider->getAvailabilityDate($lineItem->getProduct());

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
        }
    }
}
