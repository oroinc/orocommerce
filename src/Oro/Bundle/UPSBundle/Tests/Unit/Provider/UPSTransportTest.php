<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UPSBundle\Provider\RateRequestInterface;
use Oro\Bundle\UPSBundle\Provider\UPSTransport;

class UPSTransportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
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
        static::assertEquals('oro_ups_transport_setting_form_type', $this->transport->getSettingsFormType());
    }

    public function testGetSettingsEntityFQCN()
    {
        static::assertEquals('Oro\Bundle\UPSBundle\Entity\UPSTransport', $this->transport->getSettingsEntityFQCN());
    }

    public function testGetRates()
    {
        /** @var RateRequestInterface|\PHPUnit_Framework_MockObject_MockObject $rateRequest **/
        $rateRequest = $this->getMock(RateRequestInterface::class);
        $rateRequest->expects(static::once())
            ->method('toArray')
            ->willReturn([]);

        $integration = new Channel();
        $transportEntity = new \Oro\Bundle\UPSBundle\Entity\UPSTransport();
        $integration->setTransport($transportEntity);

        $repository = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository')
            ->disableOriginalConstructor()->getMock();
        $repository->expects(static::once())
            ->method('findOneBy')
            ->willReturn($integration);

        $entityManager = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects(static::once())
            ->method('getRepository')
            ->with('Oro\Bundle\IntegrationBundle\Entity\Channel')
            ->willReturn($repository);

        $this->registry->expects(static::once())
            ->method('getManagerForClass')
            ->with('Oro\Bundle\IntegrationBundle\Entity\Channel')
            ->willReturn($entityManager);

        $this->clientFactory->expects(static::once())
            ->method('createRestClient')
            ->willReturn($this->client);

        $restResponce = $this->getMock('Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface');

        $this->client->expects(static::once())
            ->method('post')
            ->willReturn($restResponce);

        //TODO: add test assertions
        $this->transport->getRates($rateRequest);
    }
}
