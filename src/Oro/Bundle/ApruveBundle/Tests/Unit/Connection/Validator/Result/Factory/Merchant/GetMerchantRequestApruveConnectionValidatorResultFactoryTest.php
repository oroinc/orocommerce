<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Connection\Validator\Result\Factory\Merchant;

use Oro\Bundle\ApruveBundle\Connection\Validator\Result\ApruveConnectionValidatorResult;
use Oro\Bundle\ApruveBundle\Connection\Validator\Result\Factory\Merchant;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;

class GetMerchantRequestApruveConnectionValidatorResultFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Merchant\GetMerchantRequestApruveConnectionValidatorResultFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->factory = new Merchant\GetMerchantRequestApruveConnectionValidatorResultFactory();
    }

    public function testCreateResultByApruveClientResponse()
    {
        $response = $this->createRestResponseMock();

        $resultParams = [
            ApruveConnectionValidatorResult::STATUS_KEY => true,
            ApruveConnectionValidatorResult::ERROR_SEVERITY_KEY => null,
            ApruveConnectionValidatorResult::ERROR_MESSAGE_KEY => null,
        ];

        $expectedResult = new ApruveConnectionValidatorResult($resultParams);

        $actualResult = $this->factory->createResultByApruveClientResponse($response);

        static::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @dataProvider createExceptionResultDataProvider
     *
     * @param int    $responseCode
     * @param string $errorSeverity
     */
    public function testCreateExceptionResult($responseCode, $errorSeverity)
    {
        $errorMessage = 'error message';

        $restException = new RestException($errorMessage, $responseCode);

        $resultParams = [
            ApruveConnectionValidatorResult::STATUS_KEY => false,
            ApruveConnectionValidatorResult::ERROR_SEVERITY_KEY => $errorSeverity,
            ApruveConnectionValidatorResult::ERROR_MESSAGE_KEY => $errorMessage,
        ];

        $expectedResult = new ApruveConnectionValidatorResult($resultParams);

        $actualResult = $this->factory->createExceptionResult($restException);

        static::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @return array
     */
    public function createExceptionResultDataProvider()
    {
        return [
            'wrong api key' => [
                'responseCode' => 401,
                'errorSeverity' => 'authentication',
            ],
            'wrong merchant id' => [
                'responseCode' => 404,
                'errorSeverity' => 'merchant',
            ],
            'other error 500' => [
                'responseCode' => 500,
                'errorSeverity' => 'server',
            ],
            'other error 403' => [
                'responseCode' => 403,
                'errorSeverity' => 'server',
            ],
        ];
    }

    /**
     * @return RestResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createRestResponseMock()
    {
        return $this->createMock(RestResponseInterface::class);
    }
}
