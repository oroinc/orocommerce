<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodIconProvider;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodStub;
use Psr\Log\LoggerInterface;

class ShippingMethodIconProviderTest extends \PHPUnit\Framework\TestCase
{
    private const SHIPPING_METHOD = 'shipping_method_1';

    /** @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodProvider;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ShippingMethodIconProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->provider = new ShippingMethodIconProvider($this->shippingMethodProvider, $this->logger);
    }

    public function testGetIcon()
    {
        $icon = 'bundles/icon-uri.png';

        $shippingMethod = $this->createMock(ShippingMethodStub::class);
        $shippingMethod->expects(self::once())
            ->method('getIcon')
            ->willReturn($icon);

        $this->shippingMethodProvider->expects(self::once())
            ->method('hasShippingMethod')
            ->with(self::SHIPPING_METHOD)
            ->willReturn(true);
        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with(self::SHIPPING_METHOD)
            ->willReturn($shippingMethod);

        static::assertSame($icon, $this->provider->getIcon(self::SHIPPING_METHOD));
    }

    public function testGetIconIfNotIconAware()
    {
        $shippingMethod = $this->createMock(ShippingMethodInterface::class);

        $this->shippingMethodProvider->expects(self::once())
            ->method('hasShippingMethod')
            ->with(self::SHIPPING_METHOD)
            ->willReturn(true);
        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with(self::SHIPPING_METHOD)
            ->willReturn($shippingMethod);

        static::assertNull($this->provider->getIcon(self::SHIPPING_METHOD));
    }

    public function testGetIconIfNoShippingMethod()
    {
        $this->shippingMethodProvider->expects(self::once())
            ->method('hasShippingMethod')
            ->with(self::SHIPPING_METHOD)
            ->willReturn(false);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(sprintf('Requested icon for non-existing shipping method "%s"', self::SHIPPING_METHOD));

        static::assertNull($this->provider->getIcon(self::SHIPPING_METHOD));
    }
}
