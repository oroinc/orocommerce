<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\RateService;

use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceSoapClient;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\Factory\FedexRateServiceResponseFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettings;
use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettingsInterface;
use Oro\Bundle\SoapBundle\Client\SoapClientInterface;
use PHPUnit\Framework\TestCase;

class FedexRateServiceSoapClientTest extends TestCase
{
    /**
     * @var SoapClientInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $soapClient;

    /**
     * @var FedexRateServiceResponseFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $responseFactory;

    /**
     * @var SoapClientSettingsInterface
     */
    private $soapSettings;

    /**
     * @var SoapClientSettingsInterface
     */
    private $soapTestSettings;

    /**
     * @var FedexRateServiceSoapClient
     */
    private $client;

    protected function setUp(): void
    {
        $this->soapClient = $this->createMock(SoapClientInterface::class);
        $this->responseFactory = $this->createMock(FedexRateServiceResponseFactoryInterface::class);
        $this->soapSettings = new SoapClientSettings('', '');
        $this->soapTestSettings = new SoapClientSettings('test', '');

        $this->client = new FedexRateServiceSoapClient(
            $this->soapClient,
            $this->responseFactory,
            $this->soapSettings,
            $this->soapTestSettings
        );
    }

    public function testSendTestMode()
    {
        $requestData = ['data'];
        $request = new FedexRequest($requestData);
        $soapResponse = 'response';

        $settings = new FedexIntegrationSettings();
        $settings->setFedexTestMode(true);

        $this->soapClient
            ->expects(static::once())
            ->method('send')
            ->with($this->soapTestSettings, $requestData)
            ->willReturn($soapResponse);

        $this->responseFactory
            ->expects(static::once())
            ->method('create')
            ->with($soapResponse);

        $this->client->send($request, $settings);
    }

    public function testSendProdMode()
    {
        $requestData = ['data'];
        $request = new FedexRequest($requestData);
        $soapResponse = 'response';

        $settings = new FedexIntegrationSettings();
        $settings->setFedexTestMode(false);

        $this->soapClient
            ->expects(static::once())
            ->method('send')
            ->with($this->soapSettings, $requestData)
            ->willReturn($soapResponse);

        $this->responseFactory
            ->expects(static::once())
            ->method('create')
            ->with($soapResponse);

        $this->client->send($request, $settings);
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

        $this->client->send($request, new FedexIntegrationSettings());
    }
}
