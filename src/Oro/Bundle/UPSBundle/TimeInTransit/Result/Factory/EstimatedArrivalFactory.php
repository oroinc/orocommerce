<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Result\Factory;

use Oro\Bundle\UPSBundle\TimeInTransit\Result\EstimatedArrival;

class EstimatedArrivalFactory implements EstimatedArrivalFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createEstimatedArrival(
        \DateTime $arrivalDate,
        $businessDaysInTransit,
        $dayOfWeek,
        $totalTransitDays = null,
        $customerCenterCutOff = null
    ) {
        return new EstimatedArrival([
            EstimatedArrival::ARRIVAL_DATE_KEY => $arrivalDate,
            EstimatedArrival::BUSINESS_DAYS_IN_TRANSIT_KEY => $businessDaysInTransit,
            EstimatedArrival::DAY_OF_WEEK_KEY => $dayOfWeek,
            EstimatedArrival::TOTAL_TRANSIT_DAYS_KEY => $totalTransitDays,
            EstimatedArrival::CUSTOMER_CENTER_CUTOFF_KEY => $customerCenterCutOff
        ]);
    }
}
