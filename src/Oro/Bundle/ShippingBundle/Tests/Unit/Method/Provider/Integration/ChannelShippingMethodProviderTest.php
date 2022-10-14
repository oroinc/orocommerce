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
    private const TYPE = 'custom_type';

    use EntityTrait;

    /** @var ShippingMethodInterface */
    private $enabledMethod;

    /** @var ShippingMethodInterface */
    private $disabledMethod;

    /** @var ChannelShippingMethodProvider */
    private $provider;

    protected function setUp(): void
    {
        $repository = $this->createMock(ChannelRepository::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(self::any())
            ->method('getEntityRepository')
            ->with('OroIntegrationBundle:Channel')
            ->willReturn($repository);

        $loadedChannel = $this->createChannel('ch_enabled');
        $fetchedChannel = $this->createChannel('ch_disabled');

        $this->enabledMethod = $this->createMock(ShippingMethodInterface::class);
        $this->enabledMethod->expects(self::any())
            ->method('getIdentifier')
            ->willReturn('ups_10');

        $this->disabledMethod = $this->createMock(ShippingMethodInterface::class);
        $this->disabledMethod->expects(self::any())
            ->method('getIdentifier')
            ->willReturn('ups_20');

        $methodFactory = $this->createMock(IntegrationShippingMethodFactoryInterface::class);
        $methodFactory->expects(self::any())
            ->method('create')
            ->willReturnMap([
                [$loadedChannel, $this->enabledMethod],
                [$fetchedChannel, $this->disabledMethod],
            ]);

        $this->provider = new ChannelShippingMethodProvider(self::TYPE, $doctrineHelper, $methodFactory);

        $doctrineEvent = $this->createMock(LifecycleEventArgs::class);
        $this->provider->postLoad($loadedChannel, $doctrineEvent);

        $repository->expects(self::any())
            ->method('findByTypeAndExclude')
            ->willReturnCallback(function () use ($fetchedChannel, $doctrineEvent) {
                $this->provider->postLoad($fetchedChannel, $doctrineEvent);

                return [$fetchedChannel];
            });
    }

    public function testGetShippingMethods()
    {
        $methods = $this->provider->getShippingMethods();
        self::assertCount(2, $methods);
        $actualMethod = reset($methods);
        self::assertSame($this->enabledMethod, $actualMethod);
    }

    public function testGetShippingMethod()
    {
        $method = $this->provider->getShippingMethod($this->enabledMethod->getIdentifier());
        self::assertInstanceOf(ShippingMethodInterface::class, $method);
    }

    public function testHasShippingMethod()
    {
        self::assertTrue($this->provider->hasShippingMethod($this->enabledMethod->getIdentifier()));
    }

    public function testHasShippingMethodFalse()
    {
        self::assertFalse($this->provider->hasShippingMethod('wrong'));
    }

    private function createChannel(string $name): Channel
    {
        return $this->getEntity(
            Channel::class,
            ['id' => 20, 'name' => $name, 'type' => self::TYPE]
        );
    }
}
