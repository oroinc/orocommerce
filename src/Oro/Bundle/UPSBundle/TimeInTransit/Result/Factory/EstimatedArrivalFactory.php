<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Result\Factory;

use Oro\Bundle\UPSBundle\TimeInTransit\Result\EstimatedArrival;

/**
 * Creates EstimatedArrival instances from UPS Time In Transit API response data.
 *
 * This factory constructs {@see EstimatedArrival} value objects containing delivery estimates
 * for specific UPS shipping services. Each estimated arrival includes the expected delivery date,
 * transit time in business days, and additional metadata such as customer center cutoff times.
 *
 * @see EstimatedArrivalFactoryInterface
 */
class EstimatedArrivalFactory implements EstimatedArrivalFactoryInterface
{
    #[\Override]
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
