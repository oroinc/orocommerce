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

    /**
     * {@inheritDoc}
     */
    public function getArrivalDate(): \DateTime
    {
        return $this->get(self::ARRIVAL_DATE_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function getBusinessDaysInTransit(): string
    {
        return (string) $this->get(self::BUSINESS_DAYS_IN_TRANSIT_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function getDayOfWeek(): string
    {
        return (string) $this->get(self::DAY_OF_WEEK_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function getCustomerCenterCutoff(): string
    {
        return (string) $this->get(self::CUSTOMER_CENTER_CUTOFF_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalTransitDays(): string
    {
        return (string) $this->get(self::TOTAL_TRANSIT_DAYS_KEY);
    }
}
