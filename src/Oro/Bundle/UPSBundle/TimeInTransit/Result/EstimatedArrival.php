<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Result;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 *  Base implementation of UPS EstimatedArrivals response
 */
class EstimatedArrival extends ParameterBag implements EstimatedArrivalInterface
{
    public const ARRIVAL_DATE_KEY = 'arrival_date';
    public const BUSINESS_DAYS_IN_TRANSIT_KEY = 'business_days_in_transit';
    public const DAY_OF_WEEK_KEY = 'day_of_week';
    public const TOTAL_TRANSIT_DAYS_KEY = 'total_transit_days';
    public const CUSTOMER_CENTER_CUTOFF_KEY = 'customer_center_cutoff';

    #[\Override]
    public function getArrivalDate(): \DateTime
    {
        return $this->get(self::ARRIVAL_DATE_KEY);
    }

    #[\Override]
    public function getBusinessDaysInTransit(): string
    {
        return (string) $this->get(self::BUSINESS_DAYS_IN_TRANSIT_KEY);
    }

    #[\Override]
    public function getDayOfWeek(): string
    {
        return (string) $this->get(self::DAY_OF_WEEK_KEY);
    }

    #[\Override]
    public function getCustomerCenterCutoff(): string
    {
        return (string) $this->get(self::CUSTOMER_CENTER_CUTOFF_KEY);
    }

    #[\Override]
    public function getTotalTransitDays(): string
    {
        return (string) $this->get(self::TOTAL_TRANSIT_DAYS_KEY);
    }
}
