<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\Sitemap\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Sitemap\Provider\SwitchableUrlItemsProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @dbIsolationPerTest
 */
class SwitchableUrlItemsProviderTest extends WebTestCase
{
    private const EXCLUDE_WEB_CATALOG_LANDING_PAGES = 'oro_seo.sitemap_exclude_landing_pages';
    private const INCLUDE_NOT_IN_WEB_CATALOG_LANDING_PAGES = 'oro_seo.sitemap_include_landing_pages_not_in_web_catalog';

    use ConfigManagerAwareTestTrait;

    private CanonicalUrlGenerator $canonicalUrlGenerator;
    private ConfigManager $configManager;
    private SwitchableUrlItemsProvider $provider;

    protected function setUp(): void
    {
        $this->initClient();
        // also 2 pages created by main migrations
        $this->loadFixtures([LoadPageData::class, LoadSlugsData::class]);

        $this->canonicalUrlGenerator = $this->getContainer()->get('oro_redirect.generator.canonical_url');
        $this->configManager = self::getConfigManager();

        $this->provider = new SwitchableUrlItemsProvider(
            $this->canonicalUrlGenerator,
            $this->configManager,
            $this->createMock(EventDispatcherInterface::class),
            $this->getContainer()->get('doctrine')
        );
        $this->provider->setType('cms_page');
        $this->provider->setEntityClass(Page::class);
        $this->provider->setChangeFrequencySettingsKey('oro_seo.sitemap_changefreq_cms_page');
        $this->provider->setPrioritySettingsKey('oro_seo.sitemap_priority_cms_page');
        $this->provider->setProvider(
            $this->getContainer()->get('oro_seo.sitemap.provider.restrict_cms_page_by_web_catalog_provider')
        );
    }

    public function testExcludeProvider()
    {
        $website = $this->createMock(WebsiteInterface::class);
        $version = 1;

        $this->configManager->set('oro_redirect.canonical_url_type', Configuration::SYSTEM_URL);
        $this->configManager->set(self::EXCLUDE_WEB_CATALOG_LANDING_PAGES, true);
        $this->configManager->set(self::INCLUDE_NOT_IN_WEB_CATALOG_LANDING_PAGES, false);
        $this->configManager->flush();

        $this->assertEquals([], $this->provider->getUrlItems($website, $version));

        $this->configManager->set(self::INCLUDE_NOT_IN_WEB_CATALOG_LANDING_PAGES, true);
        $this->configManager->flush();
        $this->assertNotEmpty($this->provider->getUrlItems($website, $version));
    }

    public function testExcludeProviderWebsiteLevel()
    {
        $website = $this->createMock(WebsiteInterface::class);
        $version = 1;

        $this->configManager->set('oro_redirect.canonical_url_type', Configuration::SYSTEM_URL);
        // global config not exclude
        $this->configManager->set(self::EXCLUDE_WEB_CATALOG_LANDING_PAGES, false);
        $this->configManager->set(self::INCLUDE_NOT_IN_WEB_CATALOG_LANDING_PAGES, false);
        // site config exclude
        $this->configManager->set(self::EXCLUDE_WEB_CATALOG_LANDING_PAGES, true, $website);
        $this->configManager->set(
            self::INCLUDE_NOT_IN_WEB_CATALOG_LANDING_PAGES,
            false,
            $website
        );
        $this->configManager->flush();

        $this->assertEquals([], $this->provider->getUrlItems($website, $version));

        $this->configManager->set(
            self::INCLUDE_NOT_IN_WEB_CATALOG_LANDING_PAGES,
            true,
            $website
        );
        $this->configManager->flush();
        $this->assertNotEmpty($this->provider->getUrlItems($website, $version));
    }

    public function testGetUrlItems()
    {
        $website = $this->createMock(WebsiteInterface::class);
        $version = 1;

        $this->configManager->set('oro_redirect.canonical_url_type', Configuration::SYSTEM_URL);
        $this->configManager->set(self::EXCLUDE_WEB_CATALOG_LANDING_PAGES, false);
        $this->configManager->set(self::INCLUDE_NOT_IN_WEB_CATALOG_LANDING_PAGES, false);
        $this->configManager->flush();

        $urlItems = iterator_to_array($this->provider->getUrlItems($website, $version));
        $this->assertCount(3, $urlItems);

        $expectedEntity = $this->getReference(LoadPageData::PAGE_1);
        $expectedUrl = $this->canonicalUrlGenerator->getSystemUrl($expectedEntity);
        $expectedUrlItem = new UrlItem(
            $expectedUrl,
            $expectedEntity->getUpdatedAt(),
            $this->configManager->get('oro_seo.sitemap_changefreq_cms_page'),
            $this->configManager->get('oro_seo.sitemap_priority_cms_page')
        );

        self::assertContainsEquals($expectedUrlItem, $urlItems);

        $this->configManager->set(self::INCLUDE_NOT_IN_WEB_CATALOG_LANDING_PAGES, true);
        $this->configManager->flush();

        $urlItems = iterator_to_array($this->provider->getUrlItems($website, $version));
        $this->assertCount(3, $urlItems);

        $expectedEntity = $this->getReference(LoadPageData::PAGE_1);
        $expectedUrl = $this->canonicalUrlGenerator->getSystemUrl($expectedEntity);
        $expectedUrlItem = new UrlItem(
            $expectedUrl,
            $expectedEntity->getUpdatedAt(),
            $this->configManager->get('oro_seo.sitemap_changefreq_cms_page'),
            $this->configManager->get('oro_seo.sitemap_priority_cms_page')
        );

        self::assertContainsEquals($expectedUrlItem, $urlItems);
    }
}
