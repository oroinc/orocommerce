<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Cache\Dumper;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Cache\Dumper\SluggableUrlDumper;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Bundle\RedirectBundle\Tests\Unit\Stub\UrlCacheAllCapabilities;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

class SluggableUrlDumperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var UrlCacheAllCapabilities|MockObject
     */
    private $cache;

    /**
     * @var RoutingInformationProviderInterface|MockObject
     */
    private $routingInformationProvider;

    /**
     * @var ConfigManager|MockObject
     */
    private $configManager;

    /**
     * @var WebsiteProviderInterface|MockObject
     */
    private $websiteProvider;

    /**
     * @var SluggableUrlDumper
     */
    private SluggableUrlDumper $dumper;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(UrlCacheAllCapabilities::class);
        $this->routingInformationProvider = $this->createMock(RoutingInformationProviderInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->websiteProvider = $this->createMock(WebsiteProviderInterface::class);

        $this->dumper = new SluggableUrlDumper(
            $this->cache,
            $this->routingInformationProvider,
            $this->configManager,
            $this->websiteProvider
        );
    }

    public function testDumpWithBaseUrl()
    {
        $baseSlug = new Slug();
        $baseSlug->setRouteName('test_route');
        $baseSlug->setRouteParameters(['id' => 1]);
        $baseSlug->setUrl('/test_url');
        $baseSlug->setSlugPrototype('test_url');

        $slug2 = new Slug();
        $slug2->setRouteName('test_route');
        $slug2->setRouteParameters(['id' => 2]);
        $slug2->setUrl('/test_url2');
        $slug2->setSlugPrototype('test_url2');
        $slug2->setLocalization($this->getEntity(Localization::class, ['id' => 2]));

        $entity = $this->createMock(SluggableInterface::class);
        $entity->expects($this->any())
            ->method('getSlugs')
            ->willReturn([$baseSlug, $slug2]);

        $this->routingInformationProvider->expects($this->once())
            ->method('getRouteData')
            ->with($entity)
            ->willReturn(new RouteData('test_route', ['id' => 1]));

        $website = $this->getEntity(Website::class, ['id' => 1]);
        $this->websiteProvider->expects($this->once())
            ->method('getWebsites')
            ->willReturn([$website]);
        $this->configManager->expects($this->once())
            ->method('getValues')
            ->with('oro_locale.enabled_localizations', [$website])
            ->willReturn([1 => [1, 2]]);

        $this->cache->expects($this->never())
            ->method('removeUrl');
        $this->cache->expects($this->exactly(2))
            ->method('setUrl')
            ->withConsecutive(
                ['test_route', ['id' => 1], '/test_url2', 'test_url2', 2],
                ['test_route', ['id' => 1], '/test_url', 'test_url', 1]
            );
        $this->cache->expects($this->once())
            ->method('flushAll');

        $this->dumper->dump($entity);
    }

    public function testDumpWithOnlyOneLocalizedUrl()
    {
        $slug2 = new Slug();
        $slug2->setRouteName('test_route');
        $slug2->setRouteParameters(['id' => 2]);
        $slug2->setUrl('/test_url2');
        $slug2->setSlugPrototype('test_url2');
        $slug2->setLocalization($this->getEntity(Localization::class, ['id' => 2]));

        $entity = $this->createMock(SluggableInterface::class);
        $entity->expects($this->any())
            ->method('getSlugs')
            ->willReturn([$slug2]);

        $this->routingInformationProvider->expects($this->once())
            ->method('getRouteData')
            ->with($entity)
            ->willReturn(new RouteData('test_route', ['id' => 1]));

        $website = $this->getEntity(Website::class, ['id' => 1]);
        $this->websiteProvider->expects($this->once())
            ->method('getWebsites')
            ->willReturn([$website]);
        $this->configManager->expects($this->once())
            ->method('getValues')
            ->with('oro_locale.enabled_localizations', [$website])
            ->willReturn([1 => [1, 2]]);

        $this->cache->expects($this->once())
            ->method('removeUrl')
            ->with('test_route', ['id' => 1], 1);
        $this->cache->expects($this->once())
            ->method('setUrl')
            ->with('test_route', ['id' => 1], '/test_url2', 'test_url2', 2);
        $this->cache->expects($this->once())
            ->method('flushAll');

        $this->dumper->dump($entity);
    }

    public function testDumpWithAllLocalizedUrl()
    {
        $slug2 = new Slug();
        $slug2->setRouteName('test_route');
        $slug2->setRouteParameters(['id' => 2]);
        $slug2->setUrl('/test_url2');
        $slug2->setSlugPrototype('test_url2');
        $slug2->setLocalization($this->getEntity(Localization::class, ['id' => 2]));

        $entity = $this->createMock(SluggableInterface::class);
        $entity->expects($this->any())
            ->method('getSlugs')
            ->willReturn([$slug2]);

        $this->routingInformationProvider->expects($this->once())
            ->method('getRouteData')
            ->with($entity)
            ->willReturn(new RouteData('test_route', ['id' => 1]));

        $website = $this->getEntity(Website::class, ['id' => 1]);
        $this->websiteProvider->expects($this->once())
            ->method('getWebsites')
            ->willReturn([$website]);
        $this->configManager->expects($this->once())
            ->method('getValues')
            ->with('oro_locale.enabled_localizations', [$website])
            ->willReturn([1 => [2]]);

        $this->cache->expects($this->never())
            ->method('removeUrl');
        $this->cache->expects($this->once())
            ->method('setUrl')
            ->with('test_route', ['id' => 1], '/test_url2', 'test_url2', 2);
        $this->cache->expects($this->once())
            ->method('flushAll');

        $this->dumper->dump($entity);
    }
}
