<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Provider\Integration;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ChannelShippingMethodProvider;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class ChannelShippingMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @internal
     */
    const TYPE = 'custom_type';

    use EntityTrait;

    /**
     * @var ChannelShippingMethodProvider
     */
    private $provider;

    /**
     * @var ChannelRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;

    /**
     * @var IntegrationShippingMethodFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $methodFactory;

    /**
     * @var ShippingMethodInterface
     */
    private $enabledMethod;

    /**
     * @var ShippingMethodInterface
     */
    private $disabledMethod;

    public function setUp()
    {
        $this->repository = $this->createMock(ChannelRepository::class);

        $enabledChannel = $this->getEntity(
            Channel::class,
            ['id' => 10, 'name' => 'ch_enabled', 'enabled' => true, 'type' => static::TYPE]
        );

        $disabledChannel = $this->getEntity(
            Channel::class,
            ['id' => 20, 'name' => 'ch_disabled', 'enabled' => false, 'type' => static::TYPE]
        );

        $this->enabledMethod = $this->createMock(ShippingMethodInterface::class);
        $this->enabledMethod
            ->method('getIdentifier')
            ->willReturn('ups_10');

        $this->disabledMethod = $this->createMock(ShippingMethodInterface::class);
        $this->disabledMethod
            ->method('getIdentifier')
            ->willReturn('ups_20');

        $this->methodFactory = $this->createMock(IntegrationShippingMethodFactoryInterface::class);
        $this->methodFactory
            ->method('create')
            ->will($this->returnValueMap([
                [$enabledChannel, $this->enabledMethod],
                [$disabledChannel, $this->disabledMethod],
            ]));

        $this->provider = new ChannelShippingMethodProvider(static::TYPE, $this->repository, $this->methodFactory);

        $doctrineEvent = $this->createMock(LifecycleEventArgs::class);
        $this->provider->postLoad($enabledChannel, $doctrineEvent);

        $this->repository
            ->method('findByTypeAndExclude')
            ->will(static::returnCallback(function () use ($disabledChannel, $doctrineEvent) {
                $this->provider->postLoad($disabledChannel, $doctrineEvent);
                return [$disabledChannel];
            }));
    }

    public function testGetShippingMethods()
    {
        $methods = $this->provider->getShippingMethods();
        static::assertCount(2, $methods);
        $actualMethod = reset($methods);
        static::assertSame($this->enabledMethod, $actualMethod);
    }

    public function testGetShippingMethod()
    {
        $method = $this->provider->getShippingMethod($this->enabledMethod->getIdentifier());
        static::assertInstanceOf(ShippingMethodInterface::class, $method);
    }

    public function testHasShippingMethod()
    {
        static::assertTrue($this->provider->hasShippingMethod($this->enabledMethod->getIdentifier()));
    }

    public function testHasShippingMethodFalse()
    {
        static::assertFalse($this->provider->hasShippingMethod('wrong'));
    }
}
