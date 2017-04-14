<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\ClientInterface;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Gateway;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Request\AuthorizeRequest;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Request\RequestRegistry;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Response\ResponseInterface;

class GatewayTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ClientInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $apiClient;

    /** @var RequestRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestRegistry;

    /** @var  Gateway */
    protected $gateway;

    protected function setUp()
    {
        $this->apiClient = $this->createMock(ClientInterface::class);
        $this->requestRegistry = $this->createMock(RequestRegistry::class);
        $this->gateway = new Gateway($this->apiClient, $this->requestRegistry);
    }

    /**
     * @dataProvider environmentDataProvider
     * @param $testMode
     * @param $expectedEnvironment
     */
    public function testEnvironment($testMode, $expectedEnvironment)
    {
        $request = new AuthorizeRequest();

        $transactionType = Option\Transaction::AUTHORIZE;

        $options = $this->getRequiredOptionsData();
        $options[Option\Transaction::TRANSACTION_TYPE] = $transactionType;

        $this->requestRegistry->expects($this->once())->method('getRequest')->with($transactionType)
            ->willReturn($request);

        $this->gateway->setTestMode($testMode);
        $this->apiClient->expects($this->once())->method('send')
            ->with(array_merge($options, [Option\Environment::ENVIRONMENT => $expectedEnvironment]));

        $this->gateway->request($transactionType, $options);
    }

    public function testRequest()
    {
        $request = new AuthorizeRequest();

        $transactionType = Option\Transaction::CHARGE;

        $options = $this->getRequiredOptionsData();
        $options[Option\Transaction::TRANSACTION_TYPE] = $transactionType;

        $this->requestRegistry->expects($this->once())->method('getRequest')->with($transactionType)
            ->willReturn($request);

        $this->gateway->setTestMode(false);

        $response = $this->createMock(ResponseInterface::class);
        $this->apiClient->expects($this->once())->method('send')
            ->with(
                array_merge(
                    $options,
                    [Option\Environment::ENVIRONMENT => \net\authorize\api\constants\ANetEnvironment::PRODUCTION]
                )
            )->willReturn($response);

        $this->assertSame($response, $this->gateway->request($transactionType, $options));
    }

    /**
     * @return array
     */
    public function environmentDataProvider()
    {
        return [
            [false, \net\authorize\api\constants\ANetEnvironment::PRODUCTION],
            [true, \net\authorize\api\constants\ANetEnvironment::SANDBOX],
        ];
    }

    /**
     * @return array
     */
    protected function getRequiredOptionsData()
    {
        return [
            Option\ApiLoginId::API_LOGIN_ID => 'some_login_id',
            Option\TransactionKey::TRANSACTION_KEY => 'some_transaction_key',
            Option\DataDescriptor::DATA_DESCRIPTOR => 'some_data_descriptor',
            Option\DataValue::DATA_VALUE => 'some_data_value',
            Option\Amount::AMOUNT => 10.00,
            Option\Currency::CURRENCY => Option\Currency::US_DOLLAR,
        ];
    }
}
