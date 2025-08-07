<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\Sitemap\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @dbIsolationPerTest
 */
class UrlItemsProviderTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?string $initialCanonicalUrlType;
    private CanonicalUrlGenerator $canonicalUrlGenerator;
    private UrlItemsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        // also 2 pages created by main migrations
        $this->loadFixtures([LoadPageData::class, LoadSlugsData::class]);

        $this->initialCanonicalUrlType = self::getConfigManager()->get('oro_redirect.canonical_url_type');

        $this->canonicalUrlGenerator = self::getContainer()->get('oro_redirect.generator.canonical_url');
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->provider = new UrlItemsProvider(
            $this->canonicalUrlGenerator,
            self::getConfigManager(null),
            $eventDispatcher,
            self::getContainer()->get('doctrine')
        );
        $this->provider->setType('cms_page');
        $this->provider->setEntityClass(Page::class);
        $this->provider->setChangeFrequencySettingsKey('oro_seo.sitemap_changefreq_cms_page');
        $this->provider->setPrioritySettingsKey('oro_seo.sitemap_priority_cms_page');
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_redirect.canonical_url_type', $this->initialCanonicalUrlType);
        $configManager->flush();
    }

    public function testItYieldsSystemUrls()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = 1;

        $configManager = self::getConfigManager();
        $configManager->set('oro_redirect.canonical_url_type', Configuration::SYSTEM_URL);
        $configManager->flush();

        $urlItems = iterator_to_array($this->provider->getUrlItems($website, $version));
        $this->assertCount(3, $urlItems);

        $expectedEntity = $this->getReference(LoadPageData::PAGE_1);
        $expectedUrl = $this->canonicalUrlGenerator->getSystemUrl($expectedEntity);
        $expectedUrlItem = new UrlItem(
            $expectedUrl,
            $expectedEntity->getUpdatedAt(),
            $configManager->get('oro_seo.sitemap_changefreq_cms_page'),
            $configManager->get('oro_seo.sitemap_priority_cms_page')
        );

        static::assertContainsEquals($expectedUrlItem, $urlItems);
    }

    public function testItYieldsDirectUrls()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = 1;

        $configManager = self::getConfigManager();
        $configManager->set('oro_redirect.canonical_url_type', Configuration::DIRECT_URL);
        $configManager->flush();

        $urlItems = iterator_to_array($this->provider->getUrlItems($website, $version));

        $expectedEntity = $this->getReference(LoadPageData::PAGE_1);
        $expectedUrl = $this->canonicalUrlGenerator->getDirectUrl($expectedEntity);
        $expectedUrlItem = new UrlItem(
            $expectedUrl,
            $expectedEntity->getUpdatedAt(),
            $configManager->get('oro_seo.sitemap_changefreq_cms_page'),
            $configManager->get('oro_seo.sitemap_priority_cms_page')
        );

        static::assertContainsEquals($expectedUrlItem, $urlItems);
    }
}
