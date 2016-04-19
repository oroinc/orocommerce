<?php

namespace OroB2B\Bundle\PricingBundle\Resolver;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListSchedule;

class PriceListScheduleResolver
{
    /**
     * @param PriceListSchedule[] $priceListSchedules
     * @param CombinedPriceListToPriceList[] $priceListRelations
     * @return array
     */
    public function mergeSchedule(array $priceListSchedules, array $priceListRelations)
    {
        $baseSetOfPriceLists = [];
        foreach ($priceListRelations as $relation) {
            $baseSetOfPriceLists[$relation->getPriceList()->getId()] = true;
        }
        $schedule = [];
        $turnedOffPriceLists = [];
        foreach ($priceListSchedules as $scheduleItem) {
            if ($scheduleItem->getActiveAt()) {
                //if start time exist, it might be turned off before this time
                $turnedOffPriceLists[$scheduleItem->getPriceList()->getId()] = true;
                $time = $scheduleItem->getActiveAt()->getTimestamp();
                $schedule[$time][$scheduleItem->getPriceList()->getId()] = 'on';
            }
            if ($scheduleItem->getDeactivateAt()) {
                $time = $scheduleItem->getDeactivateAt()->getTimestamp();
                $schedule[$time][$scheduleItem->getPriceList()->getId()] = 'off';
            }
        }
        $lines = $this->processSchedule($schedule, $baseSetOfPriceLists, $turnedOffPriceLists);


        return $lines;
    }

    /**
     * @param array $schedule
     * @param $baseName
     * @param $turnedOffPriceLists
     * @return array
     */
    protected function processSchedule(array $schedule, array $baseName, array $turnedOffPriceLists)
    {
        $lines = [];
        $lastTime = null;
        if (!empty($turnedOffPriceLists)) {
            $currentName = $baseName;
            foreach ($turnedOffPriceLists as $priceListDisabled => $val) {
                unset($currentName[$priceListDisabled]);
            }
            $lines[0] = [
                'priceLists' => array_keys($currentName),
                'activateAt' => null,
                'expireAt' => null
            ];
            $lastTime = 0;
        }
        ksort($schedule);
        foreach ($schedule as $time => $changesAtTimeMoment) {
            foreach ($changesAtTimeMoment as $priceListId => $action) {
                $currentName = $baseName;
                if ($action == 'on') {
                    unset($turnedOffPriceLists[$priceListId]);
                } else {
                    $turnedOffPriceLists[$priceListId] = true;
                }

                foreach ($turnedOffPriceLists as $priceListDisabled => $val) {
                    unset($currentName[$priceListDisabled]);
                }
                $lines[$time] = [
                    'priceLists' => array_keys($currentName),
                    'activateAt' => $time,
                    'expireAt' => null
                ];
                if ($lastTime !== null) {
                    $lines[$lastTime]['expireAt'] = $time;
                }
                $lastTime = $time;
            }
        }
        return $lines;
    }
}
