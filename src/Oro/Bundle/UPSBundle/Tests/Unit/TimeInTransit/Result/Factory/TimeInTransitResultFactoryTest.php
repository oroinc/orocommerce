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
    private const SERVICE_CODE_1 = '1DM';
    private const ARRIVAL_DATE_1 = '20170918';
    private const ARRIVAL_TIME_1 = '080000';
    private const BUSINESS_DAYS_IN_TRANSIT_1 = '1';
    private const DAY_OF_WEEK_1 = 'MON';
    private const CUSTOMER_CENTER_CUTOFF_1 = '140000';
    private const SERVICE_CODE_2 = '1DA';
    private const ARRIVAL_DATE_2 = '20170918';
    private const ARRIVAL_TIME_2 = '103000';
    private const BUSINESS_DAYS_IN_TRANSIT_2 = '1';
    private const DAY_OF_WEEK_2 = 'MON';
    private const CUSTOMER_CENTER_CUTOFF_2 = '140000';
    private const AUTO_DUTY_CODE = 'Sample AutoDutyCode';
    private const STATUS_CODE = '1';
    private const STATUS = true;
    private const STATUS_DESCRIPTION = 'Success';
    private const CUSTOMER_CONTEXT = 'sample context';
    private const TRANSACTION_IDENTIFIER = 'sample id';

    /** @var EstimatedArrivalFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $estimatedArrivalFactory;

    /** @var TimeInTransitResultFactory */
    private $timeInTransitResultFactory;

    protected function setUp(): void
    {
        $this->estimatedArrivalFactory = $this->createMock(EstimatedArrivalFactoryInterface::class);

        $this->timeInTransitResultFactory = new TimeInTransitResultFactory($this->estimatedArrivalFactory);
    }

    public function testCreateExceptionResult()
    {
        $restException = $this->createMock(RestException::class);

        $expectedTimeInTransitResult = new TimeInTransitResult([
            TimeInTransitResult::STATUS_KEY => false,
            TimeInTransitResult::STATUS_DESCRIPTION_KEY => '',
        ]);

        $result = $this->timeInTransitResultFactory->createExceptionResult($restException);

        self::assertEquals($expectedTimeInTransitResult, $result);
    }

    public function testCreateResultByUpsClientResponse()
    {
        $estimatedArrivals = $this->getExpectedEstimatedArrivals();

        $this->estimatedArrivalFactory->expects(self::exactly(2))
            ->method('createEstimatedArrival')
            ->withConsecutive(
                [
                    self::isInstanceOf(\DateTime::class),
                    self::BUSINESS_DAYS_IN_TRANSIT_1,
                    self::DAY_OF_WEEK_1,
                    null,
                    self::CUSTOMER_CENTER_CUTOFF_1,
                ],
                [
                    self::isInstanceOf(\DateTime::class),
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

        self::assertEquals($expectedTimeInTransitResult, $timeInTransitResult);
    }

    public function testCreateResultByUpsClientResponseWithSingleResult()
    {
        $estimatedArrivals = $this->getExpectedEstimatedArrivals();

        $this->estimatedArrivalFactory->expects(self::once())
            ->method('createEstimatedArrival')
            ->withConsecutive(
                [
                    self::isInstanceOf(\DateTime::class),
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

        self::assertEquals($expectedTimeInTransitResult, $timeInTransitResult);
    }

    public function testCreateResultByUpsClientResponseWithMalformedJson()
    {
        $this->expectException(\LogicException::class);

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

        self::assertEquals($expectedResult, $result);
    }

    public function testParseResponseWithMalformedDate()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches(
            '/^Could not parse estimated arrivals: Could not parse arrival date time: .+?/i'
        );

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

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/^Could not parse estimated arrivals: .+?/i');

        $this
            ->timeInTransitResultFactory
            ->createResultByUpsClientResponse($this->createRestResponse($data));
    }

    private function getRestResponseWithMalformedDate(): RestResponseInterface
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

    private function getExpectedEstimatedArrivals(): array
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

    private function getArrivalDateFromRaw(string $rawDate, string $rawTime): \DateTime
    {
        return \DateTime::createFromFormat('Ymd His', $rawDate . ' ' . $rawTime);
    }

    private function getSuccessRestResponse(): RestResponseInterface
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

    private function getSuccessSingleServiceRestResponse(): RestResponseInterface
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

    private function getFaultRestResponse(string $code, string $description): RestResponseInterface
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

    private function createRestResponse(?array $data): RestResponseInterface
    {
        $response = $this->createMock(RestResponseInterface::class);
        $response->expects(self::once())
            ->method('json')
            ->willReturn($data);

        return $response;
    }
}
