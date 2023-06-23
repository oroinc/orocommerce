<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit\Result\Factory;

use Oro\Bundle\UPSBundle\TimeInTransit\Result\EstimatedArrival;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\Factory\EstimatedArrivalFactory;

class EstimatedArrivalFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $arrivalDate = new \DateTime();
        $businessDaysInTransit = '1';
        $dayOfWeek = 'MON';
        $totalTransitDays = '1';
        $customerCenterCutoff = '140000';

        $expectedEstimatedArrival = new EstimatedArrival([
            EstimatedArrival::ARRIVAL_DATE_KEY => $arrivalDate,
            EstimatedArrival::BUSINESS_DAYS_IN_TRANSIT_KEY => $businessDaysInTransit,
            EstimatedArrival::DAY_OF_WEEK_KEY => $dayOfWeek,
            EstimatedArrival::TOTAL_TRANSIT_DAYS_KEY => $totalTransitDays,
            EstimatedArrival::CUSTOMER_CENTER_CUTOFF_KEY => $customerCenterCutoff,
        ]);

        $estimatedArrivalFactory = new EstimatedArrivalFactory();
        $estimatedArrival = $estimatedArrivalFactory->createEstimatedArrival(
            $arrivalDate,
            $businessDaysInTransit,
            $dayOfWeek,
            $totalTransitDays,
            $customerCenterCutoff
        );

        self::assertEquals($expectedEstimatedArrival, $estimatedArrival);
    }
}
