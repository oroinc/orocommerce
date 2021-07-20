<?php

namespace Oro\Bundle\PricingBundle\Duplicator;

use Oro\Bundle\PricingBundle\Entity\PriceList;

class ScheduleDuplicator
{
    public function duplicateSchedule(PriceList $sourcePriceList, PriceList $duplicatedPriceList)
    {
        $duplicatedPriceList->setContainSchedule(false);
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        foreach ($sourcePriceList->getSchedules() as $schedule) {
            if ($schedule->getDeactivateAt() === null ||
                $schedule->getDeactivateAt() > $now
            ) {
                $duplicatedPriceList->addSchedule(clone $schedule);
            }
        };
    }
}
