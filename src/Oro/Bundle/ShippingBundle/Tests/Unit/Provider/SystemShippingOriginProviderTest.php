<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ShippingBundle\Factory\ShippingOriginModelFactory;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\ShippingBundle\Provider\SystemShippingOriginProvider;

class SystemShippingOriginProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ShippingOriginModelFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingOriginModelFactory;

    /** @var SystemShippingOriginProvider */
    private $shippingOriginProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->shippingOriginModelFactory = $this->createMock(ShippingOriginModelFactory::class);

        $this->shippingOriginProvider = new SystemShippingOriginProvider(
            $this->configManager,
            $this->shippingOriginModelFactory
        );
    }

    public function testGetSystemShippingOrigin(): void
    {
        $shippingOriginData = ['key' => 'value'];
        $shippingOrigin = $this->createMock(ShippingOrigin::class);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shipping.shipping_origin')
            ->willReturn($shippingOriginData);
        $this->shippingOriginModelFactory->expects(self::once())
            ->method('create')
            ->with($shippingOriginData)
            ->willReturn($shippingOrigin);

        self::assertSame($shippingOrigin, $this->shippingOriginProvider->getSystemShippingOrigin());
    }

    public function testGetSystemShippingOriginWhenItDoesNotConfigured(): void
    {
        $shippingOrigin = $this->createMock(ShippingOrigin::class);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shipping.shipping_origin')
            ->willReturn(null);
        $this->shippingOriginModelFactory->expects(self::once())
            ->method('create')
            ->with([])
            ->willReturn($shippingOrigin);

        self::assertSame($shippingOrigin, $this->shippingOriginProvider->getSystemShippingOrigin());
    }
}
