<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductRoutingInformationProvider;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductRoutingInformationProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var RoutingInformationProviderInterface */
    private $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new ProductRoutingInformationProvider($this->configManager);
    }

    public function testIsSupported()
    {
        $this->assertTrue($this->provider->isSupported(new Product()));
    }

    public function testIsNotSupported()
    {
        $this->assertFalse($this->provider->isSupported(new \DateTime()));
    }

    public function testGetUrlPrefix()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_product.product_direct_url_prefix')
            ->willReturn('prefix');
        $this->assertSame('prefix', $this->provider->getUrlPrefix(new Product()));
    }

    public function testGetRouteData()
    {
        $this->assertEquals(
            new RouteData('oro_product_frontend_product_view', ['id' => 42]),
            $this->provider->getRouteData($this->getEntity(Product::class, ['id' => 42]))
        );
    }
}
