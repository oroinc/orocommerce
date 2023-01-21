<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Provider\Integration;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ChannelLoaderInterface;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodLoader;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Component\Testing\ReflectionUtil;

class ShippingMethodLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChannelLoaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $channelLoader;

    /** @var MemoryCacheProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $memoryCacheProvider;

    /** @var ShippingMethodLoader */
    private $shippingMethodLoader;

    protected function setUp(): void
    {
        $this->memoryCacheProvider = $this->createMock(MemoryCacheProviderInterface::class);
        $this->channelLoader = $this->createMock(ChannelLoaderInterface::class);

        $this->shippingMethodLoader = new ShippingMethodLoader($this->channelLoader, $this->memoryCacheProvider);
    }

    private function getChannel(int $id, string $name, string $type): Channel
    {
        $channel = new Channel();
        ReflectionUtil::setId($channel, $id);
        $channel->setName($name);
        $channel->setType($type);

        return $channel;
    }

    private function getShippingMethod(string $identifier): ShippingMethodInterface
    {
        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::any())
            ->method('getIdentifier')
            ->willReturn($identifier);

        return $shippingMethod;
    }

    public function testLoadShippingMethods(): void
    {
        $channelType = 'channel_type';

        $channel1 = $this->getChannel(1, 'channel1', $channelType);
        $channel2 = $this->getChannel(2, 'channel2', $channelType);

        $shippingMethod1 = $this->getShippingMethod('ups_10');
        $shippingMethod2 = $this->getShippingMethod('ups_20');

        $this->channelLoader->expects(self::once())
            ->method('loadChannels')
            ->with($channelType, self::isTrue())
            ->willReturn([$channel1, $channel2]);

        $shippingMethodFactory = $this->createMock(IntegrationShippingMethodFactoryInterface::class);
        $shippingMethodFactory->expects(self::exactly(2))
            ->method('create')
            ->willReturnMap([
                [$channel1, $shippingMethod1],
                [$channel2, $shippingMethod2],
            ]);

        $this->memoryCacheProvider->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        self::assertEquals(
            [
                $shippingMethod1->getIdentifier() => $shippingMethod1,
                $shippingMethod2->getIdentifier() => $shippingMethod2
            ],
            $this->shippingMethodLoader->loadShippingMethods($channelType, $shippingMethodFactory)
        );
    }

    public function testLoadShippingMethodsWhenShippingMethodsCached(): void
    {
        $cachedShippingMethods = [
            'ups_10' => $this->getShippingMethod('ups_10'),
            'ups_20' => $this->getShippingMethod('ups_20')
        ];

        $this->channelLoader->expects(self::never())
            ->method('loadChannels');

        $shippingMethodFactory = $this->createMock(IntegrationShippingMethodFactoryInterface::class);
        $shippingMethodFactory->expects(self::never())
            ->method('create');

        $this->memoryCacheProvider->expects($this->once())
            ->method('get')
            ->willReturnCallback(function () use ($cachedShippingMethods) {
                return $cachedShippingMethods;
            });

        self::assertEquals(
            $cachedShippingMethods,
            $this->shippingMethodLoader->loadShippingMethods('channel_type', $shippingMethodFactory)
        );
    }
}
