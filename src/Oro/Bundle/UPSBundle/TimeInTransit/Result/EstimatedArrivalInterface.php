<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Result;

interface EstimatedArrivalInterface
{
    /**
     * @return \DateTime
     */
    public function getArrivalDate();

    /**
     * Number of business days from Origin to Destination Locations.
     *
     * @return int
     */
    public function getBusinessDaysInTransit();

    /**
     * Day of week.
     * Valid values are: MON, TUE, WED, THU, FRI, SAT.
     *
     * @return string
     */
    public function getDayOfWeek();

    /**
     * The total number of days in transit from one location to the next. Returned for International requests.
     *
     * @return string
     */
    public function getCustomerCenterCutoff();

    /**
     * Customer Service call time. Returned for domestic as well as international requests.
     *
     * @return int
     */
    public function getTotalTransitDays();
}
