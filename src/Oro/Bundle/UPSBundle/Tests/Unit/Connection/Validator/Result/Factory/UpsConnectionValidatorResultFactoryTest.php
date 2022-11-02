<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Connection\Validator\Result\Factory;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\UPSBundle\Connection\Validator\Result\Factory\UpsConnectionValidatorResultFactory;
use Oro\Bundle\UPSBundle\Connection\Validator\Result\Factory\UpsConnectionValidatorResultFactoryInterface;
use Oro\Bundle\UPSBundle\Connection\Validator\Result\UpsConnectionValidatorResult;
use Oro\Bundle\UPSBundle\Connection\Validator\Result\UpsConnectionValidatorResultInterface;

class UpsConnectionValidatorResultFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UpsConnectionValidatorResultFactoryInterface
     */
    protected $connectionValidatorResultFactory;

    protected function setUp(): void
    {
        $this->connectionValidatorResultFactory = new UpsConnectionValidatorResultFactory();
    }

    /**
     * @dataProvider getUpsResponse
     */
    public function testCreateResultByUpsClientResponse(
        array $upsResponse,
        UpsConnectionValidatorResultInterface $expectedResult
    ) {
        $response = $this->createMock(RestResponseInterface::class);
        $response->expects(static::once())
            ->method('json')
            ->willReturn($upsResponse);

        static::assertEquals(
            $expectedResult,
            $this->connectionValidatorResultFactory->createResultByUpsClientResponse($response)
        );
    }

    public function testCreateExceptionResult()
    {
        $message = 'error message';

        $expected = new UpsConnectionValidatorResult([
            UpsConnectionValidatorResult::STATUS_KEY => false,
            UpsConnectionValidatorResult::ERROR_SEVERITY_KEY
                => UpsConnectionValidatorResultFactory::SERVER_SEVERITY,
            UpsConnectionValidatorResult::ERROR_MESSAGE_KEY => $message,
        ]);

        static::assertEquals(
            $expected,
            $this->connectionValidatorResultFactory->createExceptionResult(new RestException($message))
        );
    }

    /**
     * @return array
     */
    public function getUpsResponse()
    {
        return [
            'noErrors' => [
                'upsResponse' => $this->createUpsSuccessResponse(),
                'expectedResult' => new UpsConnectionValidatorResult([
                    UpsConnectionValidatorResult::STATUS_KEY => true,
                    UpsConnectionValidatorResult::ERROR_SEVERITY_KEY => null,
                    UpsConnectionValidatorResult::ERROR_MESSAGE_KEY => null
                ])
            ],
            'unavailableServiceError' => [
                'upsResponse' => $this->createUpsFaultResponse(
                    'Hard',
                    UpsConnectionValidatorResultFactory::UNAVAILABLE_SERVICE_BETWEEN_LOCATIONS_ERROR_CODE,
                    'The requested service is unavailable between the selected locations.'
                ),
                'expectedResult' => new UpsConnectionValidatorResult([
                    UpsConnectionValidatorResult::STATUS_KEY => true,
                    UpsConnectionValidatorResult::ERROR_SEVERITY_KEY => null,
                    UpsConnectionValidatorResult::ERROR_MESSAGE_KEY => null
                ])
            ],
            'measurementSystemError' => [
                'upsResponse' => $this->createUpsFaultResponse(
                    'Hard',
                    UpsConnectionValidatorResultFactory::WRONG_MEASUREMENT_SYSTEM_ERROR_CODE,
                    'This measurement system is not valid for the selected country.'
                ),
                'expectedResult' => new UpsConnectionValidatorResult([
                    UpsConnectionValidatorResult::STATUS_KEY => false,
                    UpsConnectionValidatorResult::ERROR_SEVERITY_KEY
                        => UpsConnectionValidatorResultFactory::MEASUREMENT_SYSTEM_SEVERITY,
                    UpsConnectionValidatorResult::ERROR_MESSAGE_KEY =>
                        'This measurement system is not valid for the selected country.'
                ])
            ],
            'AuthenticationError' => [
                'upsResponse' => $this->createUpsFaultResponse(
                    UpsConnectionValidatorResultFactory::AUTHENTICATION_ERROR_SEVERITY_CODE,
                    '250002',
                    'Invalid Authentication Information.'
                ),
                'expectedResult' => new UpsConnectionValidatorResult([
                    UpsConnectionValidatorResult::STATUS_KEY => false,
                    UpsConnectionValidatorResult::ERROR_SEVERITY_KEY
                        => UpsConnectionValidatorResultFactory::AUTHENTICATION_SEVERITY,
                    UpsConnectionValidatorResult::ERROR_MESSAGE_KEY => 'Invalid Authentication Information.'
                ])
            ],
            'OtherError' => [
                'upsResponse' => $this->createUpsFaultResponse(
                    'Warning',
                    '119005',
                    'The requested service may not guarantee Second Day arrival to the selected location.'
                ),
                'expectedResult' => new UpsConnectionValidatorResult([
                    UpsConnectionValidatorResult::STATUS_KEY => false,
                    UpsConnectionValidatorResult::ERROR_SEVERITY_KEY
                        => UpsConnectionValidatorResultFactory::UNEXPECTED_SEVERITY,
                    UpsConnectionValidatorResult::ERROR_MESSAGE_KEY
                        => 'The requested service may not guarantee Second Day arrival to the selected location.'
                ])
            ]
        ];
    }

    /**
     * @param string $severity
     * @param string $code
     * @param string $message
     *
     * @return array
     */
    private function createUpsFaultResponse($severity, $code, $message)
    {
        return [
            'Fault' => [
                'detail' => [
                    'Errors' => [
                        'ErrorDetail' => [
                            'Severity' => $severity,
                            'PrimaryErrorCode' => [
                                'Code' => $code,
                                'Description' => $message
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    private function createUpsSuccessResponse()
    {
        return [
            'RateResponse' => [
                'ResponseStatus' => [
                    'Code' => '1',
                    'Description' => 'Success'
                ]
            ]
        ];
    }
}
