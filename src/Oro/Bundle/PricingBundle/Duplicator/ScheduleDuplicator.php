<?php

namespace Oro\Bundle\PricingBundle\Duplicator;

use Oro\Bundle\PricingBundle\Entity\PriceList;

/**
 * Duplicates price list schedules from one price list to another.
 *
 * Copies active and future schedules from a source price list to a target price list,
 * excluding schedules that have already been deactivated.
 */
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
