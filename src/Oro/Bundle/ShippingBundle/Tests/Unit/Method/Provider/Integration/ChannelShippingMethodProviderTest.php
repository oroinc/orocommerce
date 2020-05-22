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

class ChannelShippingMethodProviderTest extends \PHPUnit\Framework\TestCase
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
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var IntegrationShippingMethodFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
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

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $repository = $this->createMock(ChannelRepository::class);

        $this->doctrineHelper
            ->method('getEntityRepository')
            ->with('OroIntegrationBundle:Channel')
            ->willReturn($repository);

        $loadedChannel = $this->createChannel('ch_enabled');
        $fetchedChannel = $this->createChannel('ch_disabled');

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
                [$loadedChannel, $this->enabledMethod],
                [$fetchedChannel, $this->disabledMethod],
            ]));

        $this->provider = new ChannelShippingMethodProvider(static::TYPE, $this->doctrineHelper, $this->methodFactory);

        $doctrineEvent = $this->createLifecycleEventArgsMock();
        $this->provider->postLoad($loadedChannel, $doctrineEvent);

        $repository
            ->method('findByTypeAndExclude')
            ->will(static::returnCallback(function () use ($fetchedChannel, $doctrineEvent) {
                $this->provider->postLoad($fetchedChannel, $doctrineEvent);
                return [$fetchedChannel];
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

    /**
     * @param string $name
     *
     * @return Channel
     */
    private function createChannel($name)
    {
        return $this->getEntity(
            Channel::class,
            ['id' => 20, 'name' => $name, 'type' => static::TYPE]
        );
    }

    /**
     * @return LifecycleEventArgs|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createLifecycleEventArgsMock()
    {
        return $this->createMock(LifecycleEventArgs::class);
    }
}
