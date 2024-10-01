<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit\Result;

use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResult;

class TimeInTransitResultTest extends \PHPUnit\Framework\TestCase
{
    private const STATUS = true;
    private const STATUS_DESCRIPTION = 'sample';
    private const ESTIMATED_ARRIVALS = ['1DM' => ['sample arrival']];
    private const AUTO_DUTY_CODE = '1';
    private const CUSTOMER_CONTEXT = 'sample context';
    private const TRANSACTION_IDENTIFIER = 'sample id';

    private TimeInTransitResult $timeInTransitResult;

    #[\Override]
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
        self::assertSame(self::STATUS, $this->timeInTransitResult->getStatus());
        self::assertSame(self::STATUS_DESCRIPTION, $this->timeInTransitResult->getStatusDescription());
        self::assertSame(self::ESTIMATED_ARRIVALS, $this->timeInTransitResult->getEstimatedArrivals());
        self::assertSame(self::AUTO_DUTY_CODE, $this->timeInTransitResult->getAutoDutyCode());
        self::assertSame(self::CUSTOMER_CONTEXT, $this->timeInTransitResult->getCustomerContext());
        self::assertSame(self::TRANSACTION_IDENTIFIER, $this->timeInTransitResult->getTransactionIdentifier());
    }

    /**
     * @dataProvider estimatedArrivalByServiceDataProvier
     */
    public function testGetEstimatedArrivalByService(string $serviceCode, ?array $expectedResult)
    {
        $result = $this->timeInTransitResult->getEstimatedArrivalByService($serviceCode);

        self::assertEquals($result, $expectedResult);
    }

    public function estimatedArrivalByServiceDataProvier(): array
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
