<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit\Result\Factory;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\EstimatedArrival;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\Factory\EstimatedArrivalFactoryInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\Factory\TimeInTransitResultFactory;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResult;

class TimeInTransitResultFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @internal
     */
    const SERVICE_CODE_1 = '1DM';

    /**
     * @internal
     */
    const ARRIVAL_DATE_1 = '20170918';

    /**
     * @internal
     */
    const ARRIVAL_TIME_1 = '080000';

    /**
     * @internal
     */
    const BUSINESS_DAYS_IN_TRANSIT_1 = '1';

    /**
     * @internal
     */
    const DAY_OF_WEEK_1 = 'MON';

    /**
     * @internal
     */
    const CUSTOMER_CENTER_CUTOFF_1 = '140000';

    /**
     * @internal
     */
    const SERVICE_CODE_2 = '1DA';

    /**
     * @internal
     */
    const ARRIVAL_DATE_2 = '20170918';

    /**
     * @internal
     */
    const ARRIVAL_TIME_2 = '103000';

    /**
     * @internal
     */
    const BUSINESS_DAYS_IN_TRANSIT_2 = '1';

    /**
     * @internal
     */
    const DAY_OF_WEEK_2 = 'MON';

    /**
     * @internal
     */
    const CUSTOMER_CENTER_CUTOFF_2 = '140000';

    /**
     * @internal
     */
    const AUTO_DUTY_CODE = 'Sample AutoDutyCode';

    /**
     * @internal
     */
    const STATUS_CODE = '1';

    /**
     * @internal
     */
    const STATUS = true;

    /**
     * @internal
     */
    const STATUS_DESCRIPTION = 'Success';

    /**
     * @internal
     */
    const CUSTOMER_CONTEXT = 'sample context';

    /**
     * @internal
     */
    const TRANSACTION_IDENTIFIER = 'sample id';

    /**
     * @var EstimatedArrivalFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $estimatedArrivalFactory;

    /**
     * @var TimeInTransitResultFactory
     */
    private $timeInTransitResultFactory;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->estimatedArrivalFactory = $this->createMock(EstimatedArrivalFactoryInterface::class);
        $this->timeInTransitResultFactory = new TimeInTransitResultFactory($this->estimatedArrivalFactory);
    }

    public function testCreateExceptionResult()
    {
        $restException = $this->createMock(RestException::class);
        $restException
            ->method('getMessage')
            ->willReturn('');

        $expectedTimeInTransitResult = new TimeInTransitResult([
            TimeInTransitResult::STATUS_KEY => false,
            TimeInTransitResult::STATUS_DESCRIPTION_KEY => '',
        ]);

        $result = $this->timeInTransitResultFactory->createExceptionResult($restException);

        static::assertEquals($expectedTimeInTransitResult, $result);
    }

    public function testCreateResultByUpsClientResponse()
    {
        $estimatedArrivals = $this->getExpectedEstimatedArrivals();

        $this->estimatedArrivalFactory
            ->expects(static::exactly(2))
            ->method('createEstimatedArrival')
            ->withConsecutive(
                [
                    static::isInstanceOf(\DateTime::class),
                    self::BUSINESS_DAYS_IN_TRANSIT_1,
                    self::DAY_OF_WEEK_1,
                    null,
                    self::CUSTOMER_CENTER_CUTOFF_1,
                ],
                [
                    static::isInstanceOf(\DateTime::class),
                    self::BUSINESS_DAYS_IN_TRANSIT_2,
                    self::DAY_OF_WEEK_2,
                    null,
                    self::CUSTOMER_CENTER_CUTOFF_2,
                ]
            )
            ->willReturnOnConsecutiveCalls(
                $estimatedArrivals[self::SERVICE_CODE_1],
                $estimatedArrivals[self::SERVICE_CODE_2]
            );

        $expectedTimeInTransitResult = new TimeInTransitResult([
            TimeInTransitResult::STATUS_KEY => self::STATUS,
            TimeInTransitResult::STATUS_DESCRIPTION_KEY => self::STATUS_DESCRIPTION,
            TimeInTransitResult::ESTIMATED_ARRIVALS_KEY => $estimatedArrivals,
            TimeInTransitResult::AUTO_DUTY_CODE_KEY => self::AUTO_DUTY_CODE,
            TimeInTransitResult::CUSTOMER_CONTEXT_KEY => self::CUSTOMER_CONTEXT,
            TimeInTransitResult::TRANSACTION_IDENTIFIER_KEY => self::TRANSACTION_IDENTIFIER,
        ]);

        $timeInTransitResult = $this
            ->timeInTransitResultFactory
            ->createResultByUpsClientResponse($this->getSuccessRestResponse());

        static::assertEquals($expectedTimeInTransitResult, $timeInTransitResult);
    }

    public function testCreateResultByUpsClientResponseWithSingleResult()
    {
        $estimatedArrivals = $this->getExpectedEstimatedArrivals();

        $this->estimatedArrivalFactory
            ->expects(static::exactly(1))
            ->method('createEstimatedArrival')
            ->withConsecutive(
                [
                    static::isInstanceOf(\DateTime::class),
                    self::BUSINESS_DAYS_IN_TRANSIT_1,
                    self::DAY_OF_WEEK_1,
                    null,
                    self::CUSTOMER_CENTER_CUTOFF_1,
                ]
            )
            ->willReturnOnConsecutiveCalls(
                $estimatedArrivals[self::SERVICE_CODE_1]
            );

        $expectedTimeInTransitResult = new TimeInTransitResult([
            TimeInTransitResult::STATUS_KEY => self::STATUS,
            TimeInTransitResult::STATUS_DESCRIPTION_KEY => self::STATUS_DESCRIPTION,
            TimeInTransitResult::ESTIMATED_ARRIVALS_KEY => [
                self::SERVICE_CODE_1 => $estimatedArrivals[self::SERVICE_CODE_1]
            ],
            TimeInTransitResult::AUTO_DUTY_CODE_KEY => self::AUTO_DUTY_CODE,
            TimeInTransitResult::CUSTOMER_CONTEXT_KEY => self::CUSTOMER_CONTEXT,
            TimeInTransitResult::TRANSACTION_IDENTIFIER_KEY => self::TRANSACTION_IDENTIFIER,
        ]);

        $timeInTransitResult = $this
            ->timeInTransitResultFactory
            ->createResultByUpsClientResponse($this->getSuccessSingleServiceRestResponse());

        static::assertEquals($expectedTimeInTransitResult, $timeInTransitResult);
    }

    public function testCreateResultByUpsClientResponseWithMalformedJson()
    {
        static::expectException(\LogicException::class);

        $this
            ->timeInTransitResultFactory
            ->createResultByUpsClientResponse($this->createRestResponse(null));
    }

    public function testCreateResultByUpsClientResponseWithFault()
    {
        $status = false;
        $code = '0';
        $description = 'sample';

        $expectedResult = new TimeInTransitResult([
            TimeInTransitResult::STATUS_KEY => $status,
            TimeInTransitResult::STATUS_DESCRIPTION_KEY => $description,
        ]);

        $result = $this
            ->timeInTransitResultFactory
            ->createResultByUpsClientResponse($this->getFaultRestResponse($code, $description));

        static::assertEquals($expectedResult, $result);
    }

    public function testParseResponseWithMalformedDate()
    {
        static::expectException(\LogicException::class);
        $exceptionMessage = '/^Could not parse estimated arrivals: Could not parse arrival date time: .+?/i';
        static::expectExceptionMessageRegExp($exceptionMessage);

        $this
            ->timeInTransitResultFactory
            ->createResultByUpsClientResponse($this->getRestResponseWithMalformedDate());
    }

    public function testParseResponseWithMalformedStructure()
    {
        $data = [
            'TimeInTransitResponse' => [
                'malformed element' => ['some' => 'data'],
            ],
        ];

        static::expectException(\LogicException::class);
        static::expectExceptionMessageRegExp('/^Could not parse estimated arrivals: .+?/i');

        $this
            ->timeInTransitResultFactory
            ->createResultByUpsClientResponse($this->createRestResponse($data));
    }

    /**
     * @return RestResponseInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getRestResponseWithMalformedDate()
    {
        $data = [
            'TimeInTransitResponse' =>
                [
                    'Response' =>
                        [
                            'ResponseStatus' =>
                                [
                                    'Code' => self::STATUS,
                                    'Description' => self::STATUS_DESCRIPTION,
                                ],
                        ],
                    'TransitResponse' =>
                        [
                            'ServiceSummary' =>
                                [
                                    [
                                        'Service' =>
                                            [
                                                'Code' => self::SERVICE_CODE_1,
                                            ],
                                        'EstimatedArrival' =>
                                            [
                                                'Arrival' =>
                                                    [
                                                        'Date' => 'malformed date',
                                                        'Time' => 'malformed time',
                                                    ],
                                                'BusinessDaysInTransit' => self::BUSINESS_DAYS_IN_TRANSIT_1,
                                                'DayOfWeek' => self::DAY_OF_WEEK_1,
                                            ],
                                    ],
                                ],
                        ],
                ],
        ];

        return $this->createRestResponse($data);
    }

    /**
     * @return array
     */
    private function getExpectedEstimatedArrivals()
    {
        return [
            self::SERVICE_CODE_1 => new EstimatedArrival([
                EstimatedArrival::ARRIVAL_DATE_KEY =>
                    $this->getArrivalDateFromRaw(self::ARRIVAL_DATE_1, self::ARRIVAL_TIME_1),
                EstimatedArrival::BUSINESS_DAYS_IN_TRANSIT_KEY => self::BUSINESS_DAYS_IN_TRANSIT_1,
                EstimatedArrival::DAY_OF_WEEK_KEY => self::DAY_OF_WEEK_1,
                EstimatedArrival::TOTAL_TRANSIT_DAYS_KEY => null,
                EstimatedArrival::CUSTOMER_CENTER_CUTOFF_KEY => self::CUSTOMER_CENTER_CUTOFF_1,
            ]),
            self::SERVICE_CODE_2 => new EstimatedArrival([
                EstimatedArrival::ARRIVAL_DATE_KEY =>
                    $this->getArrivalDateFromRaw(self::ARRIVAL_DATE_2, self::ARRIVAL_TIME_2),
                EstimatedArrival::BUSINESS_DAYS_IN_TRANSIT_KEY => self::BUSINESS_DAYS_IN_TRANSIT_2,
                EstimatedArrival::DAY_OF_WEEK_KEY => self::DAY_OF_WEEK_2,
                EstimatedArrival::TOTAL_TRANSIT_DAYS_KEY => null,
                EstimatedArrival::CUSTOMER_CENTER_CUTOFF_KEY => self::CUSTOMER_CENTER_CUTOFF_2,
            ]),
        ];
    }

    /**
     * @param string $rawDate
     * @param string $rawTime
     *
     * @return bool|\DateTime
     */
    private function getArrivalDateFromRaw($rawDate, $rawTime)
    {
        return \DateTime::createFromFormat('Ymd His', $rawDate . ' ' . $rawTime);
    }

    /**
     * @return RestResponseInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getSuccessRestResponse()
    {
        $data = [
            'TimeInTransitResponse' =>
                [
                    'Response' =>
                        [
                            'ResponseStatus' =>
                                [
                                    'Code' => self::STATUS_CODE,
                                    'Description' => self::STATUS_DESCRIPTION,
                                ],
                            'TransactionReference' => [
                                'CustomerContext' => self::CUSTOMER_CONTEXT,
                                'TransactionIdentifier' => self::TRANSACTION_IDENTIFIER,
                            ],
                        ],
                    'TransitResponse' =>
                        [
                            'ServiceSummary' =>
                                [
                                    [
                                        'Service' =>
                                            [
                                                'Code' => self::SERVICE_CODE_1,
                                            ],
                                        'EstimatedArrival' =>
                                            [
                                                'Arrival' =>
                                                    [
                                                        'Date' => self::ARRIVAL_DATE_1,
                                                        'Time' => self::ARRIVAL_TIME_1,
                                                    ],
                                                'BusinessDaysInTransit' => self::BUSINESS_DAYS_IN_TRANSIT_1,
                                                'DayOfWeek' => self::DAY_OF_WEEK_1,
                                                'CustomerCenterCutoff' => self::CUSTOMER_CENTER_CUTOFF_1,
                                            ],
                                    ],
                                    [
                                        'Service' =>
                                            [
                                                'Code' => self::SERVICE_CODE_2,
                                            ],
                                        'EstimatedArrival' =>
                                            [
                                                'Arrival' =>
                                                    [
                                                        'Date' => self::ARRIVAL_DATE_2,
                                                        'Time' => self::ARRIVAL_TIME_2,
                                                    ],
                                                'BusinessDaysInTransit' => self::BUSINESS_DAYS_IN_TRANSIT_2,
                                                'DayOfWeek' => self::DAY_OF_WEEK_2,
                                                'CustomerCenterCutoff' => self::CUSTOMER_CENTER_CUTOFF_2,
                                            ],
                                    ],
                                ],
                            'AutoDutyCode' => self::AUTO_DUTY_CODE,
                        ],
                ],
        ];

        return $this->createRestResponse($data);
    }

    /**
     * @return RestResponseInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getSuccessSingleServiceRestResponse()
    {
        $data = [
            'TimeInTransitResponse' => [
                'Response' => [
                    'ResponseStatus' => [
                        'Code' => self::STATUS_CODE,
                        'Description' => self::STATUS_DESCRIPTION,
                    ],
                    'TransactionReference' => [
                        'CustomerContext' => self::CUSTOMER_CONTEXT,
                        'TransactionIdentifier' => self::TRANSACTION_IDENTIFIER,
                    ],
                ],
                'TransitResponse' => [
                    'ServiceSummary' => [
                        'Service' => [
                            'Code' => self::SERVICE_CODE_1,
                        ],
                        'EstimatedArrival' => [
                            'Arrival' => [
                                'Date' => self::ARRIVAL_DATE_1,
                                'Time' => self::ARRIVAL_TIME_1,
                            ],
                            'BusinessDaysInTransit' => self::BUSINESS_DAYS_IN_TRANSIT_1,
                            'DayOfWeek' => self::DAY_OF_WEEK_1,
                            'CustomerCenterCutoff' => self::CUSTOMER_CENTER_CUTOFF_1,
                        ],
                    ],
                    'AutoDutyCode' => self::AUTO_DUTY_CODE,
                ],
            ],
        ];

        return $this->createRestResponse($data);
    }

    /**
     * @param string $code
     * @param string $description
     *
     * @return RestResponseInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getFaultRestResponse($code, $description)
    {
        $data = [
            'Fault' => [
                'detail' => [
                    'Errors' => [
                        'ErrorDetail' => [
                            'PrimaryErrorCode' => [
                                'Code' => $code,
                                'Description' => $description,
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $this->createRestResponse($data);
    }

    /**
     * @param array|null $data
     *
     * @return RestResponseInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createRestResponse($data)
    {
        $response = $this->createMock(RestResponseInterface::class);
        $response
            ->expects(static::once())
            ->method('json')
            ->willReturn($data);

        return $response;
    }
}
