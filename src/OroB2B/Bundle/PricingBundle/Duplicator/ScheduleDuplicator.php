<?php

namespace OroB2B\Bundle\PricingBundle\Duplicator;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class ScheduleDuplicator
{
    /**
     * @param PriceList $sourcePriceList
     * @param PriceList $duplicatedPriceList
     */
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
