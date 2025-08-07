<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\Sitemap\Provider;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Sitemap\Provider\ContentVariantUrlItemsProvider;
use Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\ContentVariantUrlItemsProvider as FixtureDir;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @dbIsolationPerTest
 */
class ContentVariantUrlItemsProviderTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?int $initialWebCatalogId;
    private ContentVariantUrlItemsProvider $contentVariantUrlItemsProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([FixtureDir\LoadWebCatalogPageData::class]);

        $this->contentVariantUrlItemsProvider = self::getContainer()->get(
            'oro_seo.sitemap.provider.content_variant_items_provider'
        );

        $configManager = self::getConfigManager();
        $this->initialWebCatalogId = $configManager->get('oro_web_catalog.web_catalog');
        $configManager->set(
            'oro_web_catalog.web_catalog',
            $this->getReference(LoadWebCatalogData::CATALOG_1)->getId()
        );
        $configManager->flush();
        self::getContainer()->get('oro_web_catalog.cache.merged')->clear();
        self::getContainer()->get('oro_web_catalog.cache.root')->clear();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_web_catalog.web_catalog', $this->initialWebCatalogId);
        $configManager->flush();
        self::getContainer()->get('oro_web_catalog.cache.merged')->clear();
        self::getContainer()->get('oro_web_catalog.cache.root')->clear();
    }

    public function testGetUrlItems(): void
    {
        $urlItems = $this->contentVariantUrlItemsProvider->getUrlItems(
            $this->getReference(FixtureDir\LoadWebsiteData::WEBSITE_DEFAULT),
            1
        );

        $urlItems = iterator_to_array($urlItems);
        $actualLocations = \array_map(static function (UrlItem $urlItem) {
            return $urlItem->getLocation();
        }, $urlItems);
        sort($actualLocations);

        self::assertEquals([
            'http://localhost/content-node-slug-1',
            'http://localhost/content-node-slug-3',
            'http://localhost/content-node-slug-5',
        ], $actualLocations);
    }

    public function testGetUrlItemsOfRootNode(): void
    {
        $website = $this->getReference(FixtureDir\LoadWebsiteData::WEBSITE_DEFAULT);

        $webCatalogProvider = $this->createMock(WebCatalogProvider::class);
        $webCatalogProvider->expects(self::once())
            ->method('getNavigationRootWithCatalogRootFallback')
            ->with($website)
            ->willReturn($this->getReference('content-node-4'));

        ReflectionUtil::setPropertyValue(
            $this->contentVariantUrlItemsProvider,
            'webCatalogProvider',
            $webCatalogProvider
        );

        $urlItems = $this->contentVariantUrlItemsProvider->getUrlItems($website, 1);

        $urlItems = iterator_to_array($urlItems);
        $actualLocations = \array_map(static function (UrlItem $urlItem) {
            return $urlItem->getLocation();
        }, $urlItems);
        sort($actualLocations);

        self::assertEquals([
            'http://localhost/content-node-slug-1',
            'http://localhost/content-node-slug-3',
            'http://localhost/content-node-slug-5',
        ], $actualLocations);
    }
}
