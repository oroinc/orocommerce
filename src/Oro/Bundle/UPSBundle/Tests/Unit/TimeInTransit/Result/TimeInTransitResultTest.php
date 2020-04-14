<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit\Result;

use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResult;

class TimeInTransitResultTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @internal
     */
    const STATUS = true;

    /**
     * @internal
     */
    const STATUS_DESCRIPTION = 'sample';

    /**
     * @internal
     */
    const ESTIMATED_ARRIVALS = ['1DM' => ['sample arrival']];

    /**
     * @internal
     */
    const AUTO_DUTY_CODE = '1';

    /**
     * @internal
     */
    const CUSTOMER_CONTEXT = 'sample context';

    /**
     * @internal
     */
    const TRANSACTION_IDENTIFIER = 'sample id';

    /**
     * @var TimeInTransitResult
     */
    private $timeInTransitResult;

    protected function setUp(): void
    {
        $this->timeInTransitResult = new TimeInTransitResult([
            TimeInTransitResult::STATUS_KEY => self::STATUS,
            TimeInTransitResult::STATUS_DESCRIPTION_KEY => self::STATUS_DESCRIPTION,
            TimeInTransitResult::ESTIMATED_ARRIVALS_KEY => self::ESTIMATED_ARRIVALS,
            TimeInTransitResult::AUTO_DUTY_CODE_KEY => self::AUTO_DUTY_CODE,
            TimeInTransitResult::CUSTOMER_CONTEXT_KEY => self::CUSTOMER_CONTEXT,
            TimeInTransitResult::TRANSACTION_IDENTIFIER_KEY => self::TRANSACTION_IDENTIFIER,
        ]);
    }

    public function testAccessors()
    {
        static::assertSame(self::STATUS, $this->timeInTransitResult->getStatus());
        static::assertSame(self::STATUS_DESCRIPTION, $this->timeInTransitResult->getStatusDescription());
        static::assertSame(self::ESTIMATED_ARRIVALS, $this->timeInTransitResult->getEstimatedArrivals());
        static::assertSame(self::AUTO_DUTY_CODE, $this->timeInTransitResult->getAutoDutyCode());
        static::assertSame(self::CUSTOMER_CONTEXT, $this->timeInTransitResult->getCustomerContext());
        static::assertSame(self::TRANSACTION_IDENTIFIER, $this->timeInTransitResult->getTransactionIdentifier());
    }

    /**
     * @dataProvider estimatedArrivalByServiceDataProvier
     *
     * @param string     $serviceCode
     * @param array|null $expectedResult
     */
    public function testGetEstimatedArrivalByService($serviceCode, $expectedResult)
    {
        $result = $this->timeInTransitResult->getEstimatedArrivalByService($serviceCode);

        static::assertEquals($result, $expectedResult);
    }

    /**
     * @return array
     */
    public function estimatedArrivalByServiceDataProvier()
    {
        return [
            [
                'serviceCode' => '1DM',
                'expectedResult' => self::ESTIMATED_ARRIVALS['1DM'],
            ],
            [
                'serviceCode' => 'not-exists',
                'expectedResult' => null,
            ],
        ];
    }
}
