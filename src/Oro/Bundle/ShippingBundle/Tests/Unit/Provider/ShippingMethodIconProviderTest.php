<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodIconAwareInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodIconProvider;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

class ShippingMethodIconProviderTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    const SHIPPING_METHOD = 'shipping_method_1';
    const ICON = 'bundles/icon-uri.png';

    /**
     * @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingMethodProvider;

    /**
     * @var ShippingMethodIconProvider
     */
    private $provider;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);
        $this->provider = new ShippingMethodIconProvider($this->shippingMethodProvider);

        $this->setUpLoggerMock($this->provider);
    }

    public function testGetIcon()
    {
        $shippingMethod = $this->createMock(ShippingMethodIconAwareInterface::class);
        $shippingMethod
            ->method('getIcon')
            ->willReturn(self::ICON);

        $this->configureShippingMethodRegistry(self::SHIPPING_METHOD, $shippingMethod);

        static::assertSame(self::ICON, $this->provider->getIcon(self::SHIPPING_METHOD));
    }

    public function testGetIconIfNotIconAware()
    {
        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $this->configureShippingMethodRegistry(self::SHIPPING_METHOD, $shippingMethod);

        static::assertNull($this->provider->getIcon(self::SHIPPING_METHOD));
    }

    public function testGetIconIfNoShippingMethod()
    {
        $this->shippingMethodProvider
            ->method('hasShippingMethod')
            ->with(self::SHIPPING_METHOD)
            ->willReturn(false);

        $this->assertLoggerWarningMethodCalled();

        static::assertNull($this->provider->getIcon(self::SHIPPING_METHOD));
    }

    /**
     * @param string                                   $identifier
     * @param \PHPUnit\Framework\MockObject\MockObject $shippingMethod
     *
     * @return ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function configureShippingMethodRegistry($identifier, $shippingMethod)
    {
        $this->shippingMethodProvider
            ->method('hasShippingMethod')
            ->with($identifier)
            ->willReturn(true);

        $this->shippingMethodProvider
            ->method('getShippingMethod')
            ->with($identifier)
            ->willReturn($shippingMethod);

        return $this->shippingMethodProvider;
    }
}
