<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\FlatRate;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\UPSBundle\Method\UPS\UPSShippingMethod;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethodProvider;
use Oro\Bundle\UPSBundle\Provider\ChannelType;
use Oro\Bundle\UPSBundle\Provider\UPSTransport;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class UPSShippingMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var UPSShippingMethodProvider
     */
    protected $provider;

    public function setUp()
    {
        $transportProvider = $this->getMockBuilder(UPSTransport::class)
            ->disableOriginalConstructor()->getMock();
        $doctrine = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()->getMock();

        $repository = $this->getMockBuilder(ChannelRepository::class)
            ->disableOriginalConstructor()->getMock();

        $manager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()->getMock();

        $manager->expects($this->once())
            ->method('getRepository')
            ->with('OroIntegrationBundle:Channel')
            ->willReturn($repository);

        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroIntegrationBundle:Channel')
            ->willReturn($manager);

        $channel = $this->getEntity(Channel::class, [
            'id' => 10,
            'enabled' => true,
        ]);

        $repository->expects($this->once())
            ->method('findBy')
            ->with([
                'type' => ChannelType::TYPE,
            ])
            ->willReturn([$channel]);

        $this->provider = new UPSShippingMethodProvider($transportProvider, $doctrine);
    }

    public function testGetShippingMethods()
    {
        $methods = $this->provider->getShippingMethods();
        $this->assertCount(1, $methods);
        $method = reset($methods);
        $this->assertInstanceOf(UPSShippingMethod::class, $method);
        $this->assertEquals(['ups_10'], array_keys($methods));
    }

    public function testGetShippingMethod()
    {
        $method = $this->provider->getShippingMethod('ups_10');
        $this->assertInstanceOf(UPSShippingMethod::class, $method);
    }

    public function testHasShippingMethod()
    {
        $this->assertTrue($this->provider->hasShippingMethod('ups_10'));
    }

    public function testHasShippingMethodFalse()
    {
        $this->assertFalse($this->provider->hasShippingMethod('wrong'));
    }
}
