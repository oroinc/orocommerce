<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit\Result;

use Oro\Bundle\UPSBundle\TimeInTransit\Result\EstimatedArrival;

class EstimatedArrivalTest extends \PHPUnit\Framework\TestCase
{
    public function testAccessors()
    {
        $arrivalDate = new \DateTime();
        $businessDaysInTransit = '1';
        $dayOfWeek = 'MON';
        $totalTransitDays = '1';
        $customerCenterCutoff = '140000';

        $estimatedArrival = new EstimatedArrival([
            EstimatedArrival::ARRIVAL_DATE_KEY => $arrivalDate,
            EstimatedArrival::BUSINESS_DAYS_IN_TRANSIT_KEY => $businessDaysInTransit,
            EstimatedArrival::DAY_OF_WEEK_KEY => $dayOfWeek,
            EstimatedArrival::TOTAL_TRANSIT_DAYS_KEY => $totalTransitDays,
            EstimatedArrival::CUSTOMER_CENTER_CUTOFF_KEY => $customerCenterCutoff,
        ]);

        self::assertSame($arrivalDate, $estimatedArrival->getArrivalDate());
        self::assertSame($businessDaysInTransit, $estimatedArrival->getBusinessDaysInTransit());
        self::assertSame($dayOfWeek, $estimatedArrival->getDayOfWeek());
        self::assertSame($totalTransitDays, $estimatedArrival->getTotalTransitDays());
        self::assertSame($customerCenterCutoff, $estimatedArrival->getCustomerCenterCutoff());
    }
}
