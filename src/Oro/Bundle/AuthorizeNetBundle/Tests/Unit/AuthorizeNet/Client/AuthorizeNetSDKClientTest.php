<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Client;

use JMS\Serializer\Serializer;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\AuthorizeNetSDKClient;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\Factory\AnetSDKRequestFactory;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Response\AuthorizeNetSDKResponse;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class AuthorizeNetSDKClientTest extends \PHPUnit_Framework_TestCase
{
    /** @var Serializer|\PHPUnit_Framework_MockObject_MockObject */
    protected $serializer;

    /** @var  AnetSDKRequestFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $factory;

    /** @var  AuthorizeNetSDKClient */
    protected $apiClient;

    protected function setUp()
    {
        $this->serializer = $this->createMock(Serializer::class);
        $this->factory = $this->createMock(AnetSDKRequestFactory::class);
        $this->apiClient = new AuthorizeNetSDKClient($this->serializer, $this->factory);
    }

    public function testSend()
    {
        $requestOptions = $this->getRequiredOptionsData();

        $request = $this->createMock(AnetAPI\CreateTransactionRequest::class);
        $request->expects($this->once())->method('setMerchantAuthentication');
        $request->expects($this->once())->method('setTransactionRequest');

        $transactionResponse = new AnetAPI\CreateTransactionResponse();
        $controller = $this->createMock(AnetController\CreateTransactionController::class);
        $controller->expects($this->once())->method('executeWithApiResponse')
            ->with($requestOptions[Option\Environment::ENVIRONMENT])
            ->willReturn($transactionResponse);

        $this->factory->expects($this->once())->method('createRequest')->willReturn($request);
        $this->factory->expects($this->once())->method('createController')
            ->with($request)->willReturn($controller);

        $this->assertEquals(
            new AuthorizeNetSDKResponse($this->serializer, $transactionResponse),
            $this->apiClient->send($requestOptions)
        );
    }

    /**
     * @return array
     */
    protected function getRequiredOptionsData()
    {
        return [
            Option\ApiLoginId::API_LOGIN_ID => 'some_login_id',
            Option\Transaction::TRANSACTION_TYPE => Option\Transaction::CHARGE,
            Option\TransactionKey::TRANSACTION_KEY => 'some_transaction_key',
            Option\DataDescriptor::DATA_DESCRIPTOR => 'some_data_descriptor',
            Option\DataValue::DATA_VALUE => 'some_data_value',
            Option\Amount::AMOUNT => 10.00,
            Option\Currency::CURRENCY => Option\Currency::US_DOLLAR,
            Option\Environment::ENVIRONMENT => \net\authorize\api\constants\ANetEnvironment::PRODUCTION,
        ];
    }
}
