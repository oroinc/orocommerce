<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\RateService;

use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceSoapClient;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\Factory\FedexRateServiceResponseFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\SoapBundle\Client\Settings\Factory\SoapClientSettingsFactoryInterface;
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
     * @var SoapClientSettingsFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $soapSettingsFactory;

    /**
     * @var FedexRateServiceResponseFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $responseFactory;

    /**
     * @var FedexRateServiceSoapClient
     */
    private $client;

    protected function setUp()
    {
        $this->soapClient = $this->createMock(SoapClientInterface::class);
        $this->soapSettingsFactory = $this->createMock(SoapClientSettingsFactoryInterface::class);
        $this->responseFactory = $this->createMock(FedexRateServiceResponseFactoryInterface::class);

        $this->client = new FedexRateServiceSoapClient(
            $this->soapClient,
            $this->soapSettingsFactory,
            $this->responseFactory
        );
    }

    public function testSend()
    {
        $requestData = ['data'];
        $request = new FedexRequest($requestData);
        $soapResponse = 'response';

        $settings = $this->createMock(SoapClientSettingsInterface::class);

        $this->soapSettingsFactory
            ->expects(static::once())
            ->method('create')
            ->willReturn($settings);

        $this->soapClient
            ->expects(static::once())
            ->method('send')
            ->with($settings, $requestData)
            ->willReturn($soapResponse);

        $this->responseFactory
            ->expects(static::once())
            ->method('create')
            ->with($soapResponse);

        $this->client->send($request);
    }
}
