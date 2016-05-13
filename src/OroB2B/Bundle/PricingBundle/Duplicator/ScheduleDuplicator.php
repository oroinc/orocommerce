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
        $newSchedules = new ArrayCollection();
        foreach ($sourcePriceList->getSchedules() as $schedule) {
            if ($schedule->getDeactivateAt() > new \DateTime('now', new \DateTimeZone('UTC'))) {
                $newSchedules->add($schedule);
            }
        };
        $duplicatedPriceList->setSchedules($newSchedules);
        if ($newSchedules->count() > 0) {
            $duplicatedPriceList->setContainSchedule(true);
        }
    }
}
