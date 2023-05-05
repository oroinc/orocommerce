<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Provider\CategoryRoutingInformationProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;

class CategoryRoutingInformationProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var CategoryRoutingInformationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->provider = new CategoryRoutingInformationProvider($this->configManager);
    }

    public function testIsSupported()
    {
        $this->assertTrue($this->provider->isSupported(new Category()));
    }

    public function testIsNotSupported()
    {
        $this->assertFalse($this->provider->isSupported(new \DateTime()));
    }

    public function testGetUrlPrefix()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_catalog.category_direct_url_prefix')
            ->willReturn('prefix');
        $this->assertSame('prefix', $this->provider->getUrlPrefix(new Category()));
    }

    public function testGetRouteData()
    {
        $this->assertEquals(
            new RouteData('oro_product_frontend_product_index', ['categoryId' => 42, 'includeSubcategories' => true]),
            $this->provider->getRouteData($this->getEntity(Category::class, ['id' => 42]))
        );
    }
}
