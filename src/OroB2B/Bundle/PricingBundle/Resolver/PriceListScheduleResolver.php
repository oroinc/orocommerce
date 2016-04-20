<?php

namespace OroB2B\Bundle\PricingBundle\Resolver;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListSchedule;

class PriceListScheduleResolver
{
    const ON = 'on';
    const OFF = 'off';
    const PRICE_LISTS_KEY = 'priceLists';
    const ACTIVATE_AT_KEY = 'activateAt';
    const EXPIRE_AT_KEY = 'expireAt';

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
                $schedule[$time][$scheduleItem->getPriceList()->getId()] = self::ON;
            } else {
                $turnedOffPriceLists[0] = true;
            }
            if ($scheduleItem->getDeactivateAt()) {
                $time = $scheduleItem->getDeactivateAt()->getTimestamp();
                $schedule[$time][$scheduleItem->getPriceList()->getId()] = self::OFF;
            }
        }
        $lines = $this->processSchedule($schedule, $baseSetOfPriceLists, $turnedOffPriceLists);


        return $lines;
    }

    /**
     * @param array $schedule
     * @param array $baseName
     * @param array $turnedOffPriceLists
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
                self::PRICE_LISTS_KEY => array_keys($currentName),
                self::ACTIVATE_AT_KEY => null,
                self::EXPIRE_AT_KEY => null
            ];
            $lastTime = 0;
        }
        ksort($schedule);
        foreach ($schedule as $time => $changesAtTimeMoment) {
            foreach ($changesAtTimeMoment as $priceListId => $action) {
                $currentName = $baseName;
                if ($action == self::ON) {
                    unset($turnedOffPriceLists[$priceListId]);
                } else {
                    $turnedOffPriceLists[$priceListId] = true;
                }

                foreach ($turnedOffPriceLists as $priceListDisabled => $val) {
                    unset($currentName[$priceListDisabled]);
                }
                $lines[$time] = [
                    self::PRICE_LISTS_KEY => array_keys($currentName),
                    self::ACTIVATE_AT_KEY => $time,
                    self::EXPIRE_AT_KEY => null
                ];
                if ($lastTime !== null) {
                    $lines[$lastTime][self::EXPIRE_AT_KEY] = $time;
                }
                $lastTime = $time;
            }
        }
        return $lines;
    }
}
