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
    use ConfigManagerAwareTestTrait;

    /**
     * @var CanonicalUrlGenerator
     */
    private $canonicalUrlGenerator;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var SwitchableUrlItemsProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->initClient();
        // also 2 pages created by main migrations
        $this->loadFixtures([LoadPageData::class, LoadSlugsData::class]);

        $this->canonicalUrlGenerator = $this->getContainer()->get('oro_redirect.generator.canonical_url');
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->configManager = self::getConfigManager('global');

        $this->provider = new SwitchableUrlItemsProvider(
            $this->canonicalUrlGenerator,
            self::getConfigManager(null),
            $eventDispatcher,
            $this->getContainer()->get('doctrine')
        );
        $this->provider->setType('cms_page');
        $this->provider->setEntityClass(Page::class);
        $this->provider->setChangeFrequencySettingsKey('oro_seo.sitemap_changefreq_cms_page');
        $this->provider->setPrioritySettingsKey('oro_seo.sitemap_priority_cms_page');
        $this->provider->setExcludeProviderKey('oro_seo.sitemap_exclude_landing_pages');
    }

    public function testExcludeProvider()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = 1;

        $this->configManager->set('oro_redirect.canonical_url_type', Configuration::SYSTEM_URL);
        $this->configManager->set('oro_seo.sitemap_exclude_landing_pages', true);
        $this->configManager->flush();

        $this->assertEquals([], $this->provider->getUrlItems($website, $version));
    }

    public function testExcludeProviderWebsiteLevel()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = 1;

        $this->configManager->set('oro_redirect.canonical_url_type', Configuration::SYSTEM_URL);
        // global config not exclude
        $this->configManager->set('oro_seo.sitemap_exclude_landing_pages', false);
        // site config exclude
        $this->configManager->set('oro_seo.sitemap_exclude_landing_pages', true, $website);
        $this->configManager->flush();

        $this->assertEquals([], $this->provider->getUrlItems($website, $version));
    }

    public function testGetUrlItems()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = 1;

        $this->configManager->set('oro_redirect.canonical_url_type', Configuration::SYSTEM_URL);
        $this->configManager->set('oro_seo.sitemap_exclude_landing_pages', false);
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

        static::assertContainsEquals($expectedUrlItem, $urlItems);
    }
}
