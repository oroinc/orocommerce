<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Provider\Integration;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ChannelLoaderInterface;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ChannelShippingMethodProvider;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Component\Testing\ReflectionUtil;

class ChannelShippingMethodProviderTest extends \PHPUnit\Framework\TestCase
{
    private const TYPE = 'custom_type';

    private ShippingMethodInterface $shippingMethod1;
    private ShippingMethodInterface $shippingMethod2;
    private ChannelShippingMethodProvider $provider;

    protected function setUp(): void
    {
        $channel1 = $this->getChannel('channel1');
        $channel2 = $this->getChannel('channel2');

        $this->shippingMethod1 = $this->getShippingMethod('ups_10');
        $this->shippingMethod2 = $this->getShippingMethod('ups_20');

        $methodFactory = $this->createMock(IntegrationShippingMethodFactoryInterface::class);
        $methodFactory->expects(self::any())
            ->method('create')
            ->willReturnMap([
                [$channel1, $this->shippingMethod1],
                [$channel2, $this->shippingMethod2],
            ]);

        $channelLoader = $this->createMock(ChannelLoaderInterface::class);
        $channelLoader->expects(self::any())
            ->method('loadChannels')
            ->with(self::TYPE, self::isTrue())
            ->willReturn([$channel1, $channel2]);

        $this->provider = new ChannelShippingMethodProvider(self::TYPE, $methodFactory, $channelLoader);
    }

    private function getChannel(string $name): Channel
    {
        $channel = new Channel();
        ReflectionUtil::setId($channel, 20);
        $channel->setName($name);
        $channel->setType(self::TYPE);

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

    public function testGetShippingMethods(): void
    {
        self::assertEquals(
            [
                $this->shippingMethod1->getIdentifier() => $this->shippingMethod1,
                $this->shippingMethod2->getIdentifier() => $this->shippingMethod2
            ],
            $this->provider->getShippingMethods()
        );
    }

    public function testGetShippingMethod(): void
    {
        self::assertSame(
            $this->shippingMethod1,
            $this->provider->getShippingMethod($this->shippingMethod1->getIdentifier())
        );
    }

    public function testGetShippingMethodForUnknownMethod(): void
    {
        self::assertNull($this->provider->getShippingMethod('another'));
    }

    public function testHasShippingMethod(): void
    {
        self::assertTrue($this->provider->hasShippingMethod($this->shippingMethod1->getIdentifier()));
    }

    public function testHasShippingMethodForUnknownMethod(): void
    {
        self::assertFalse($this->provider->hasShippingMethod('another'));
    }
}
