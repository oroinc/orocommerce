<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Result;

use Symfony\Component\HttpFoundation\ParameterBag;

class EstimatedArrival extends ParameterBag implements EstimatedArrivalInterface
{
    const ARRIVAL_DATE_KEY = 'arrival_date';
    const BUSINESS_DAYS_IN_TRANSIT_KEY = 'business_days_in_transit';
    const DAY_OF_WEEK_KEY = 'day_of_week';
    const TOTAL_TRANSIT_DAYS_KEY = 'total_transit_days';
    const CUSTOMER_CENTER_CUTOFF_KEY = 'customer_center_cutoff';

    /**
     * {@inheritDoc}
     */
    public function getArrivalDate()
    {
        return $this->get(self::ARRIVAL_DATE_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function getBusinessDaysInTransit()
    {
        return (string)$this->get(self::BUSINESS_DAYS_IN_TRANSIT_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function getDayOfWeek()
    {
        return (string)$this->get(self::DAY_OF_WEEK_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function getCustomerCenterCutoff()
    {
        return (string)$this->get(self::CUSTOMER_CENTER_CUTOFF_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalTransitDays()
    {
        return (string)$this->get(self::TOTAL_TRANSIT_DAYS_KEY);
    }
}
