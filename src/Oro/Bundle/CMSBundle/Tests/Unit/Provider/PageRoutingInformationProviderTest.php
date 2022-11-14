<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Provider\PageRoutingInformationProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;

class PageRoutingInformationProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var PageRoutingInformationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new PageRoutingInformationProvider($this->configManager);
    }

    public function testIsSupported()
    {
        $this->assertTrue($this->provider->isSupported(new Page()));
    }

    public function testIsNotSupported()
    {
        $this->assertFalse($this->provider->isSupported(new \DateTime()));
    }

    public function testGetUrlPrefix()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_cms.landing_page_direct_url_prefix')
            ->willReturn('prefix');
        $this->assertSame('prefix', $this->provider->getUrlPrefix(new Page()));
    }

    public function testGetRouteData()
    {
        $this->assertEquals(
            new RouteData('oro_cms_frontend_page_view', ['id' => 42]),
            $this->provider->getRouteData($this->getEntity(Page::class, ['id' => 42]))
        );
    }
}
