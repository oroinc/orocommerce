<?php
namespace Oro\Bundle\SEOBundle\Tests\Functional\Sitemap\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @dbIsolationPerTest
 */
class UrlItemsProviderTest extends WebTestCase
{
    /**
     * @var CanonicalUrlGenerator
     */
    private $canonicalUrlGenerator;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;

    /**
     * @var WebsiteInterface
     */
    private $website;

    /**
     * @var string
     */
    private $providerType = 'cms_page';

    /**
     * @var string
     */
    private $providerEntityClass = Page::class;

    protected function setUp()
    {
        $this->initClient();
        // also 2 pages created by main migrations
        $this->loadFixtures([LoadPageData::class, LoadSlugsData::class]);

        $this->canonicalUrlGenerator = $this->getContainer()->get('oro_redirect.generator.canonical_url');
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->website = new Website();
    }

    public function testItYieldsSystemUrls()
    {
        $doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $configManager = $this->getContainer()->get('oro_config.manager');
        $configManager->set('oro_redirect.canonical_url_type', Configuration::SYSTEM_URL);
        $configManager->flush();
        $configManager = $this->getContainer()->get('oro_config.manager');
        $provider = new UrlItemsProvider(
            $this->canonicalUrlGenerator,
            $configManager,
            $this->eventDispatcher,
            $doctrineHelper,
            $this->providerType,
            $this->providerEntityClass
        );

        $urlItems = [];
        foreach ($provider->getUrlItems($this->website) as $urlItem) {
            $urlItems[] = $urlItem;
        }

        $this->assertCount(5, $urlItems);

        $expectedEntity = $this->getReference(LoadPageData::PAGE_1);
        $expectedUrl = $this->canonicalUrlGenerator->getSystemUrl($expectedEntity);
        $expectedUrlItem = new UrlItem(
            $expectedUrl,
            $expectedEntity->getUpdatedAt(),
            $configManager->get(sprintf('oro_seo.sitemap_changefreq_%s', $this->providerType)),
            $configManager->get(sprintf('oro_seo.sitemap_priority_%s', $this->providerType))
        );

        $this->assertContains($expectedUrlItem, $urlItems, '', false, false);
    }

    public function testItYieldsDirectUrls()
    {
        $doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $configManager = $this->getContainer()->get('oro_config.manager');
        $configManager->set('oro_redirect.canonical_url_type', Configuration::DIRECT_URL);
        $configManager->flush();
        $provider = new UrlItemsProvider(
            $this->canonicalUrlGenerator,
            $configManager,
            $this->eventDispatcher,
            $doctrineHelper,
            $this->providerType,
            $this->providerEntityClass
        );

        $urlItems = [];
        foreach ($provider->getUrlItems($this->website) as $urlItem) {
            $urlItems[] = $urlItem;
        }

        $expectedEntity = $this->getReference(LoadPageData::PAGE_1);
        $expectedUrl = $this->canonicalUrlGenerator->getDirectUrl($expectedEntity);
        $expectedUrlItem = new UrlItem(
            $expectedUrl,
            $expectedEntity->getUpdatedAt(),
            $configManager->get(sprintf('oro_seo.sitemap_changefreq_%s', $this->providerType)),
            $configManager->get(sprintf('oro_seo.sitemap_priority_%s', $this->providerType))
        );

        $this->assertContains($expectedUrlItem, $urlItems, '', false, false);
    }

    public function testItDispatchEvent()
    {
        $doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);
        $provider = new UrlItemsProvider(
            $this->canonicalUrlGenerator,
            $configManager,
            $this->eventDispatcher,
            $doctrineHelper,
            $this->providerType,
            $this->providerEntityClass
        );

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                RestrictSitemapEntitiesEvent::NAME.'.cms_page',
                new \PHPUnit_Framework_Constraint_IsInstanceOf(RestrictSitemapEntitiesEvent::class)
            );

        $urlItems = [];
        foreach ($provider->getUrlItems($this->website) as $urlItem) {
            $urlItems[] = $urlItem;
        }

        $this->assertCount(5, $urlItems);
    }

    public function testItCacheConfig()
    {
        $doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);
        $provider = new UrlItemsProvider(
            $this->canonicalUrlGenerator,
            $configManager,
            $this->eventDispatcher,
            $doctrineHelper,
            $this->providerType,
            $this->providerEntityClass
        );

        $configManager->expects($this->exactly(2))
            ->method('get');

        $urlItems = [];
        foreach ($provider->getUrlItems($this->website) as $urlItem) {
            $urlItems[] = $urlItem;
        }

        $this->assertCount(5, $urlItems);
    }
}
