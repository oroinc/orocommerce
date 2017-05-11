<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Client;

use JMS\Serializer\Serializer;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\AuthorizeNetSDKClient;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\Factory\AnetSDKRequestFactoryInterface;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Response\AuthorizeNetSDKResponse;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class AuthorizeNetSDKClientTest extends \PHPUnit_Framework_TestCase
{
    const HOST_ADDRESS = 'http://example.local/api';

    /** @var Serializer|\PHPUnit_Framework_MockObject_MockObject */
    protected $serializer;

    /** @var AnetSDKRequestFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestFactory;

    /** @var AuthorizeNetSDKClient */
    protected $client;

    protected function setUp()
    {
        $this->serializer = $this->createMock(Serializer::class);
        $this->requestFactory = $this->createMock(AnetSDKRequestFactoryInterface::class);
        $this->client = new AuthorizeNetSDKClient($this->serializer, $this->requestFactory);
    }

    public function testSend()
    {
        $requestOptions = $this->getRequiredOptionsData();

        $request = $this->createMock(AnetAPI\CreateTransactionRequest::class);
        $this->requestFactory->expects($this->once())
            ->method('createRequest')
            ->with($requestOptions)
            ->willReturn($request);

        $transactionResponse = new AnetAPI\CreateTransactionResponse();
        $controller = $this->createMock(AnetController\CreateTransactionController::class);
        $controller->expects($this->once())->method('executeWithApiResponse')
            ->with(self::HOST_ADDRESS)
            ->willReturn($transactionResponse);

        $this->requestFactory->expects($this->once())
            ->method('createController')
            ->with($request)
            ->willReturn($controller);

        $response = $this->client->send(self::HOST_ADDRESS, $requestOptions);
        $this->assertInstanceOf(AuthorizeNetSDKResponse::class, $response);
    }

    /**
     * @expectedException \LogicException
     */
    public function testSendReturnsUnexpectedResponse()
    {
        $requestOptions = $this->getRequiredOptionsData();

        $request = $this->createMock(AnetAPI\CreateTransactionRequest::class);
        $this->requestFactory->expects($this->once())
            ->method('createRequest')
            ->with($requestOptions)
            ->willReturn($request);

        $errorResponse = new AnetAPI\ErrorResponse();
        $controller = $this->createMock(AnetController\CreateTransactionController::class);
        $controller->expects($this->once())->method('executeWithApiResponse')
            ->with(self::HOST_ADDRESS)
            ->willReturn($errorResponse);

        $this->requestFactory->expects($this->once())
            ->method('createController')
            ->with($request)
            ->willReturn($controller);

        $this->client->send(self::HOST_ADDRESS, $requestOptions);
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
        ];
    }
}
