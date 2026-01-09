<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Result\Factory;

use Oro\Bundle\UPSBundle\TimeInTransit\Result\EstimatedArrivalInterface;

/**
 * Defines the contract for factories that create EstimatedArrival instances.
 *
 * Implementations of this interface create {@see EstimatedArrivalInterface} value objects
 * from UPS Time In Transit API response data. These objects encapsulate delivery estimates
 * including arrival dates, transit times, and service-specific metadata.
 */
interface EstimatedArrivalFactoryInterface
{
    /**
     * @param \DateTime   $arrivalDate
     * @param string      $businessDaysInTransit
     * @param string      $dayOfWeek
     * @param string|null $totalTransitDays
     * @param string|null $customerCenterCutOff
     *
     * @return EstimatedArrivalInterface
     */
    public function createEstimatedArrival(
        \DateTime $arrivalDate,
        $businessDaysInTransit,
        $dayOfWeek,
        $totalTransitDays = null,
        $customerCenterCutOff = null
    );
}
