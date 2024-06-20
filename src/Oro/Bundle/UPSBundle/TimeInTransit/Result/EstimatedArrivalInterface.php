<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Result;

/**
 * Interface for UPS EstimatedArrivals response
 */
interface EstimatedArrivalInterface
{
    /**
     * @return \DateTime
     */
    public function getArrivalDate(): \DateTime;

    /**
     * Number of business days from Origin to Destination Locations.
     *
     * @return string
     */
    public function getBusinessDaysInTransit(): string;

    /**
     * Day of week.
     * Valid values are: MON, TUE, WED, THU, FRI, SAT.
     *
     * @return string
     */
    public function getDayOfWeek(): string;

    /**
     * The total number of days in transit from one location to the next. Returned for International requests.
     *
     * @return string
     */
    public function getCustomerCenterCutoff(): string;

    /**
     * Customer Service call time. Returned for domestic as well as international requests.
     *
     * @return string
     */
    public function getTotalTransitDays(): string;
}
