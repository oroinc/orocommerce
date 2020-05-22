<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit\Result\Factory;

use Oro\Bundle\UPSBundle\TimeInTransit\Result\EstimatedArrival;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\Factory\EstimatedArrivalFactory;

class EstimatedArrivalFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @internal
     */
    const BUSINESS_DAYS_IN_TRANSIT = 1;

    /**
     * @internal
     */
    const DAY_OF_WEEK = 'MON';

    /**
     * @internal
     */
    const TOTAL_TRANSIT_DAYS = 1;

    /**
     * @internal
     */
    const CUSTOMER_CENTER_CUTOFF = '140000';

    /**
     * @var EstimatedArrivalFactory
     */
    private $estimatedArrivalFactory;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->estimatedArrivalFactory = new EstimatedArrivalFactory();
    }

    public function testCreate()
    {
        $dateTime = new \DateTime();

        $expectedEstimatedArrival = new EstimatedArrival([
            EstimatedArrival::ARRIVAL_DATE_KEY => $dateTime,
            EstimatedArrival::BUSINESS_DAYS_IN_TRANSIT_KEY => self::BUSINESS_DAYS_IN_TRANSIT,
            EstimatedArrival::DAY_OF_WEEK_KEY => self::DAY_OF_WEEK,
            EstimatedArrival::TOTAL_TRANSIT_DAYS_KEY => self::TOTAL_TRANSIT_DAYS,
            EstimatedArrival::CUSTOMER_CENTER_CUTOFF_KEY => self::CUSTOMER_CENTER_CUTOFF,
        ]);

        $estimatedArrival = $this
            ->estimatedArrivalFactory
            ->createEstimatedArrival(
                $dateTime,
                self::BUSINESS_DAYS_IN_TRANSIT,
                self::DAY_OF_WEEK,
                self::TOTAL_TRANSIT_DAYS,
                self::CUSTOMER_CENTER_CUTOFF
            );

        static::assertEquals($expectedEstimatedArrival, $estimatedArrival);
    }
}
