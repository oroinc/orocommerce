<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodIconAwareInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodIconProvider;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

class ShippingMethodIconProviderTest extends \PHPUnit_Framework_TestCase
{
    use LoggerAwareTraitTestTrait;

    const SHIPPING_METHOD = 'shipping_method_1';
    const ICON = 'bundles/icon-uri.png';

    /**
     * @var ShippingMethodRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingMethodRegistry;

    /**
     * @var ShippingMethodIconProvider
     */
    private $provider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->shippingMethodRegistry = $this->createMock(ShippingMethodRegistry::class);
        $this->provider = new ShippingMethodIconProvider($this->shippingMethodRegistry);

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

        static::assertSame('', $this->provider->getIcon(self::SHIPPING_METHOD));
    }

    public function testGetIconIfNoShippingMethod()
    {
        $this->shippingMethodRegistry
            ->method('hasShippingMethod')
            ->with(self::SHIPPING_METHOD)
            ->willReturn(false);

        $this->assertLoggerWarningMethodCalled();

        static::assertSame('', $this->provider->getIcon(self::SHIPPING_METHOD));
    }

    /**
     * @param string $identifier
     * @param \PHPUnit_Framework_MockObject_MockObject $shippingMethod
     *
     * @return ShippingMethodRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private function configureShippingMethodRegistry($identifier, $shippingMethod)
    {
        $this->shippingMethodRegistry
            ->method('hasShippingMethod')
            ->with($identifier)
            ->willReturn(true);

        $this->shippingMethodRegistry
            ->method('getShippingMethod')
            ->with($identifier)
            ->willReturn($shippingMethod);

        return $this->shippingMethodRegistry;
    }
}
