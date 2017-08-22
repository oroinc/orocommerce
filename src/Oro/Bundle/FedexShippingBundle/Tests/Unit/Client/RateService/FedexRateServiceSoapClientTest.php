<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\RateService;

use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceSoapClient;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\Factory\FedexRateServiceResponseFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettings;
use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettingsInterface;
use Oro\Bundle\SoapBundle\Client\SoapClientInterface;
use PHPUnit\Framework\TestCase;

class FedexRateServiceSoapClientTest extends TestCase
{
    /**
     * @var SoapClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $soapClient;

    /**
     * @var FedexRateServiceResponseFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $responseFactory;

    /**
     * @var SoapClientSettingsInterface
     */
    private $soapSettings;

    /**
     * @var FedexRateServiceSoapClient
     */
    private $client;

    protected function setUp()
    {
        $this->soapClient = $this->createMock(SoapClientInterface::class);
        $this->responseFactory = $this->createMock(FedexRateServiceResponseFactoryInterface::class);
        $this->soapSettings = new SoapClientSettings('', '');

        $this->client = new FedexRateServiceSoapClient(
            $this->soapClient,
            $this->responseFactory,
            $this->soapSettings
        );
    }

    public function testSend()
    {
        $requestData = ['data'];
        $request = new FedexRequest($requestData);
        $soapResponse = 'response';

        $this->soapClient
            ->expects(static::once())
            ->method('send')
            ->with($this->soapSettings, $requestData)
            ->willReturn($soapResponse);

        $this->responseFactory
            ->expects(static::once())
            ->method('create')
            ->with($soapResponse);

        $this->client->send($request);
    }

    public function testSendException()
    {
        $requestData = ['data'];
        $request = new FedexRequest($requestData);

        $this->soapClient
            ->expects(static::once())
            ->method('send')
            ->with($this->soapSettings, $requestData)
            ->willThrowException(new \Exception());

        $this->responseFactory
            ->expects(static::once())
            ->method('create')
            ->with(null);

        $this->client->send($request);
    }
}
