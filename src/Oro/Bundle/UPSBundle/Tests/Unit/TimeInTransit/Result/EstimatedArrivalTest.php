<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit\Result;

use Oro\Bundle\UPSBundle\TimeInTransit\Result\EstimatedArrival;

class EstimatedArrivalTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @internal
     */
    const BUSINESS_DAYS_IN_TRANSIT = '1';

    /**
     * @internal
     */
    const DAY_OF_WEEK = 'MON';

    /**
     * @internal
     */
    const TOTAL_TRANSIT_DAYS = '1';

    /**
     * @internal
     */
    const CUSTOMER_CENTER_CUTOFF = '140000';

    public function testAccessors()
    {
        $dateTime = new \DateTime();

        $estimatedArrival = new EstimatedArrival([
            EstimatedArrival::ARRIVAL_DATE_KEY => $dateTime,
            EstimatedArrival::BUSINESS_DAYS_IN_TRANSIT_KEY => self::BUSINESS_DAYS_IN_TRANSIT,
            EstimatedArrival::DAY_OF_WEEK_KEY => self::DAY_OF_WEEK,
            EstimatedArrival::TOTAL_TRANSIT_DAYS_KEY => self::TOTAL_TRANSIT_DAYS,
            EstimatedArrival::CUSTOMER_CENTER_CUTOFF_KEY => self::CUSTOMER_CENTER_CUTOFF,
        ]);

        static::assertSame($dateTime, $estimatedArrival->getArrivalDate());
        static::assertSame(self::BUSINESS_DAYS_IN_TRANSIT, $estimatedArrival->getBusinessDaysInTransit());
        static::assertSame(self::DAY_OF_WEEK, $estimatedArrival->getDayOfWeek());
        static::assertSame(self::TOTAL_TRANSIT_DAYS, $estimatedArrival->getTotalTransitDays());
        static::assertSame(self::CUSTOMER_CENTER_CUTOFF, $estimatedArrival->getCustomerCenterCutoff());
    }
}
