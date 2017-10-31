<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Result\Factory;

use Oro\Bundle\UPSBundle\TimeInTransit\Result\EstimatedArrivalInterface;

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
