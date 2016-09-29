<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Method\FlatRate;

use Doctrine\ORM\EntityManager;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\UPSBundle\Factory\PriceRequestFactory;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethod;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethodProvider;
use Oro\Bundle\UPSBundle\Provider\ChannelType;
use Oro\Bundle\UPSBundle\Provider\UPSTransport;

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
        /** @var UPSTransport | \PHPUnit_Framework_MockObject_MockObject $transportProvider */
        $transportProvider = $this->getMockBuilder(UPSTransport::class)
            ->disableOriginalConstructor()->getMock();
        /** @var ManagerRegistry | \PHPUnit_Framework_MockObject_MockObject $doctrine */
        $doctrine = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()->getMock();
        /** @var PriceRequestFactory | \PHPUnit_Framework_MockObject_MockObject $priceRequestFactory */
        $priceRequestFactory = $this->getMockBuilder(PriceRequestFactory::class)
            ->disableOriginalConstructor()->getMock();

        $repository = $this->getMockBuilder(ChannelRepository::class)
            ->disableOriginalConstructor()->getMock();

        $manager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()->getMock();

        $manager->expects(static::once())
            ->method('getRepository')
            ->with('OroIntegrationBundle:Channel')
            ->willReturn($repository);

        $doctrine->expects(static::once())
            ->method('getManagerForClass')
            ->with('OroIntegrationBundle:Channel')
            ->willReturn($manager);

        $channel = $this->getEntity(Channel::class, [
            'id' => 10,
            'enabled' => true,
        ]);

        $repository->expects(static::once())
            ->method('findBy')
            ->with([
                'type' => ChannelType::TYPE,
            ])
            ->willReturn([$channel]);

        $this->provider = new UPSShippingMethodProvider($transportProvider, $doctrine, $priceRequestFactory);
    }

    public function testGetShippingMethods()
    {
        $methods = $this->provider->getShippingMethods();
        static::assertCount(1, $methods);
        $method = reset($methods);
        static::assertInstanceOf(UPSShippingMethod::class, $method);
        static::assertEquals(['ups_10'], array_keys($methods));
    }

    public function testGetShippingMethod()
    {
        $method = $this->provider->getShippingMethod('ups_10');
        static::assertInstanceOf(UPSShippingMethod::class, $method);
    }

    public function testHasShippingMethod()
    {
        static::assertTrue($this->provider->hasShippingMethod('ups_10'));
    }

    public function testHasShippingMethodFalse()
    {
        static::assertFalse($this->provider->hasShippingMethod('wrong'));
    }
}
