<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Provider\Integration;

use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ChannelShippingMethodProvider;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodLoader;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;

class ChannelShippingMethodProviderTest extends \PHPUnit\Framework\TestCase
{
    private const TYPE = 'channel_type';

    /** @var ChannelShippingMethodProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodLoader;

    /** @var ChannelShippingMethodProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodFactory;

    /** @var ChannelShippingMethodProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->shippingMethodFactory = $this->createMock(IntegrationShippingMethodFactoryInterface::class);
        $this->shippingMethodLoader = $this->createMock(ShippingMethodLoader::class);

        $this->provider = new ChannelShippingMethodProvider(
            self::TYPE,
            $this->shippingMethodFactory,
            $this->shippingMethodLoader
        );
    }

    private function expectsLoadShippingMethods(array $shippingMethods): void
    {
        $this->shippingMethodLoader->expects(self::once())
            ->method('loadShippingMethods')
            ->with(self::TYPE, self::identicalTo($this->shippingMethodFactory))
            ->willReturn($shippingMethods);
    }

    public function testGetShippingMethods(): void
    {
        $shippingMethods = ['method' => $this->createMock(ShippingMethodInterface::class)];

        $this->expectsLoadShippingMethods($shippingMethods);

        self::assertSame($shippingMethods, $this->provider->getShippingMethods());
    }

    public function testGetShippingMethod(): void
    {
        $shippingMethods = ['method' => $this->createMock(ShippingMethodInterface::class)];

        $this->expectsLoadShippingMethods($shippingMethods);

        self::assertSame($shippingMethods['method'], $this->provider->getShippingMethod('method'));
    }

    public function testGetShippingMethodForUnknownMethod(): void
    {
        $shippingMethods = ['method' => $this->createMock(ShippingMethodInterface::class)];

        $this->expectsLoadShippingMethods($shippingMethods);

        self::assertNull($this->provider->getShippingMethod('another'));
    }

    public function testHasShippingMethod(): void
    {
        $shippingMethods = ['method' => $this->createMock(ShippingMethodInterface::class)];

        $this->expectsLoadShippingMethods($shippingMethods);

        self::assertTrue($this->provider->hasShippingMethod('method'));
    }

    public function testHasShippingMethodForUnknownMethod(): void
    {
        $shippingMethods = ['method' => $this->createMock(ShippingMethodInterface::class)];

        $this->expectsLoadShippingMethods($shippingMethods);

        self::assertFalse($this->provider->hasShippingMethod('another'));
    }
}
