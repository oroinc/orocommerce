<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Sitemap\Provider\CmsPageSitemapRestrictionProvider;
use Oro\Bundle\SEOBundle\Sitemap\Provider\SwitchableUrlItemsProvider;
use Oro\Component\Website\WebsiteInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SwitchableUrlItemsProviderTest extends TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $canonicalUrlGenerator;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    protected function setUp(): void
    {
        $this->canonicalUrlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
    }

    public function testExcludeProvider()
    {
        $website = $this->createMock(WebsiteInterface::class);
        $providerConfig = $this->createMock(CmsPageSitemapRestrictionProvider::class);
        $version = '1';

        $providerConfig->expects($this->once())
            ->method('isUrlItemsExcluded')
            ->willReturn(true);

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $urlItemsProvider = new SwitchableUrlItemsProvider(
            $this->canonicalUrlGenerator,
            $this->configManager,
            $this->eventDispatcher,
            $this->registry
        );
        $urlItemsProvider->setProvider($providerConfig);

        $res = $urlItemsProvider->getUrlItems($website, $version);

        $this->assertEquals([], $res);
    }

    public function testExcludeProviderWebsiteLevel()
    {
        $website = $this->createMock(WebsiteInterface::class);
        $website2 = $this->createMock(WebsiteInterface::class);
        $version = '1';

        $provider = $this->createMock(CmsPageSitemapRestrictionProvider::class);
        $provider->expects(self::any())
            ->method('isUrlItemsExcluded')
            ->willReturnMap([
                [null, true],
                [$website, true],
                [$website2, true]
            ]);

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $urlItemsProvider = new SwitchableUrlItemsProvider(
            $this->canonicalUrlGenerator,
            $this->configManager,
            $this->eventDispatcher,
            $this->registry
        );
        $urlItemsProvider->setProvider($provider);

        $res = $urlItemsProvider->getUrlItems($website, $version);
        $this->assertEquals([], $res);

        $res = $urlItemsProvider->getUrlItems($website2, $version);
        $this->assertEquals([], $res);
    }
}
