<?php

namespace Oro\Bundle\PricingBundle\Resolver;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;

/**
 * Resolves price list schedule into cpl activation rules
 */
class PriceListScheduleResolver
{
    public const ON = 'on';
    public const OFF = 'off';
    public const PRICE_LISTS_KEY = 'priceLists';
    public const ACTIVATE_AT_KEY = 'activateAt';
    public const EXPIRE_AT_KEY = 'expireAt';
    public const TIME_KEY = 'time';

    private array $priceListSchedules = [];
    private array $priceListsWithoutSchedule = [];
    private array $rules = [];
    private array $activationDates = [];

    /**
     * @param PriceListSchedule[] $priceListSchedules
     * @param CombinedPriceListToPriceList[] $priceListRelations
     * @return array
     */
    public function mergeSchedule(array $priceListSchedules, array $priceListRelations)
    {
        if (!$priceListSchedules) {
            return [];
        }

        $this->buildActivationDates($priceListSchedules);
        $this->buildPriceListsWithoutSchedule($priceListRelations);

        try {
            return $this->getRules();
        } finally {
            $this->clear();
        }
    }

    public function getRules(): array
    {
        //Activate price lists without schedule or schedule without activateAt value
        $this->addInitialRules();

        //Activate price lists without schedule or schedule is active in date period
        foreach ($this->activationDates as $i => $date) {
            $this->addDateRules($date, $this->activationDates[$i + 1] ?? null);
        }

        return $this->rules;
    }

    /**
     * Builds sorted list of unique schedule activation dates
     */
    private function buildActivationDates(array $priceListSchedules): void
    {
        $this->priceListSchedules = array_values($priceListSchedules);

        $dates = [];

        foreach ($this->priceListSchedules as $scheduleItem) {
            if ($activateAt = $scheduleItem->getActiveAt()) {
                $dates[$activateAt->getTimestamp()] = $activateAt;
            }

            if ($deactivateAt = $scheduleItem->getDeactivateAt()) {
                $dates[$deactivateAt->getTimestamp()] = $deactivateAt;
            }
        }

        ksort($dates);
        $this->activationDates = array_values($dates);
    }

    private function buildPriceListsWithoutSchedule(array $relations): void
    {
        $baseSetOfPriceLists = [];

        /** @var CombinedPriceListToPriceList $relation */
        foreach ($relations as $relation) {
            $baseSetOfPriceLists[$relation->getPriceList()->getId()] = true;
        }

        foreach ($this->priceListSchedules as $scheduleItem) {
            $plId = $scheduleItem->getPriceList()->getId();
            unset($baseSetOfPriceLists[$plId]);
        }

        $this->priceListsWithoutSchedule = array_keys($baseSetOfPriceLists);
    }

    /**
     * Activates price lists without schedule or schedule without activateAt value
     */
    private function addInitialRules(): void
    {
        $priceLists = $this->priceListsWithoutSchedule;

        foreach ($this->priceListSchedules as $schedule) {
            if (!$schedule->getActiveAt()) {
                $priceLists[] = $schedule->getPriceList()->getId();
            }
        }

        $this->addRule($priceLists, null, reset($this->activationDates));
    }

    /**
     * Activate price lists without schedule
     * or schedule is active in period between $activationDate and $nextActivationDate
     */
    private function addDateRules(\DateTime $activationDate, ?\DateTime $nextActivationDate): void
    {
        $priceLists = $this->priceListsWithoutSchedule;

        foreach ($this->priceListSchedules as $schedule) {
            if ($this->isScheduleActive($schedule, $activationDate)) {
                $priceLists[] = $schedule->getPriceList()->getId();
            }
        }

        $this->addRule($priceLists, $activationDate, $nextActivationDate);
    }

    private function addRule(array $priceLists, ?\DateTime $activateAt, ?\DateTime $expireAt): void
    {
        $this->rules[] = [
            self::PRICE_LISTS_KEY => array_values(array_unique($priceLists)),
            self::ACTIVATE_AT_KEY => $activateAt,
            self::EXPIRE_AT_KEY => $expireAt,
        ];
    }

    /**
     * Checks if $schedule is active on $date
     */
    private function isScheduleActive(PriceListSchedule $schedule, \DateTime $date): bool
    {
        $time = $date->getTimestamp();
        $activateAt = $schedule->getActiveAt()?->getTimestamp();
        $deactivateAt = $schedule->getDeactivateAt()?->getTimestamp();


        if ($activateAt && $deactivateAt) {
            return $activateAt <= $time && $deactivateAt > $time;
        }

        if ($activateAt) {
            return $activateAt <= $time;
        }

        return $deactivateAt > $time;
    }

    private function clear(): void
    {
        $this->priceListSchedules = [];
        $this->priceListsWithoutSchedule = [];
        $this->rules = [];
        $this->activationDates = [];
    }
}
