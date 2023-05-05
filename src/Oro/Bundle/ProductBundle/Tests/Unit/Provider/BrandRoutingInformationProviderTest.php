<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Provider\BrandRoutingInformationProvider;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;

class BrandRoutingInformationProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var RoutingInformationProviderInterface */
    private $provider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new BrandRoutingInformationProvider($this->configManager);
    }

    public function testIsSupported()
    {
        $this->assertTrue($this->provider->isSupported(new Brand()));
    }

    public function testIsNotSupported()
    {
        $this->assertFalse($this->provider->isSupported(new \stdClass()));
    }

    public function testGetUrlPrefix()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_product.brand_direct_url_prefix')
            ->willReturn('prefix');
        $this->assertSame('prefix', $this->provider->getUrlPrefix(new Brand()));
    }

    public function testGetRouteData()
    {
        $this->assertEquals(
            new RouteData('oro_product_frontend_brand_view', ['id' => 42]),
            $this->provider->getRouteData($this->getEntity(Brand::class, ['id' => 42]))
        );
    }
}
