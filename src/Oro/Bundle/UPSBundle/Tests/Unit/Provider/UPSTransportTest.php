<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\UPSBundle\Form\Type\UPSTransportSettingsType;
use Oro\Bundle\UPSBundle\Model\PriceRequest;
use Oro\Bundle\UPSBundle\Provider\UPSTransport;

class UPSTransportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var RestClientFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $clientFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $client;

    /**
     * @var UPSTransport
     */
    protected $transport;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->client = $this->getMock('Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface');

        $this->clientFactory = $this->getMock(
            'Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface'
        );

        $this->transport = new UPSTransport($this->registry);
        $this->transport->setRestClientFactory($this->clientFactory);
    }

    public function testGetLabel()
    {
        static::assertEquals('oro.ups.transport.label', $this->transport->getLabel());
    }

    public function testGetSettingsFormType()
    {
        static::assertEquals(UPSTransportSettingsType::class, $this->transport->getSettingsFormType());
    }

    public function testGetSettingsEntityFQCN()
    {
        static::assertEquals('Oro\Bundle\UPSBundle\Entity\UPSTransport', $this->transport->getSettingsEntityFQCN());
    }

    public function testGetPrices()
    {
        /** @var PriceRequest|\PHPUnit_Framework_MockObject_MockObject $rateRequest * */
        $rateRequest = $this->getMock(PriceRequest::class);

        $rateRequest->expects(static::once())
            ->method('setSecurity')
            ->willReturn($rateRequest);

        $rateRequest->expects(static::once())
            ->method('setShipperName')
            ->willReturn($rateRequest);

        $rateRequest->expects(static::once())
            ->method('setShipperNumber')
            ->willReturn($rateRequest);

        $integration = new Channel();
        $transportEntity = new \Oro\Bundle\UPSBundle\Entity\UPSTransport();
        $integration->setTransport($transportEntity);

        $this->clientFactory->expects(static::once())
            ->method('createRestClient')
            ->willReturn($this->client);

        $restResponse = $this->getMock('Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface');

        $json = '{
                   "RateResponse":{
                      "RatedShipment":{
                         "Service": {
                            "Code":"02"
                         },
                         "TotalCharges":{
                            "CurrencyCode":"USD",
                            "MonetaryValue":"8.60"
                         }
                      }
                   }
                }';

        $restResponse->expects(static::once())
            ->method('json')
            ->willReturn($json);

        $this->client->expects(static::once())
            ->method('post')
            ->willReturn($restResponse);

        //TODO: add test assertions
        $this->transport->getPrices($rateRequest, $transportEntity);
    }
}
