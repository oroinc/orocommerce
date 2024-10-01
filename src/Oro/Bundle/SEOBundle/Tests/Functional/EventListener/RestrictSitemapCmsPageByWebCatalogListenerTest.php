<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\EventListener\RestrictSitemapCmsPageByWebCatalogListener;
use Oro\Bundle\SEOBundle\Sitemap\Provider\CmsPageSitemapRestrictionProvider;
use Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\RestrictSitemapCmsPageByWebCatalogListener as FixtureDir;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @dbIsolationPerTest
 */
class RestrictSitemapCmsPageByWebCatalogListenerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private const EVENT_NAME = 'oro_seo.event.restrict_sitemap_entity.cms_page';
    private const WEB_CATALOG = 'oro_web_catalog.web_catalog';
    private const EXCLUDE_WEB_CATALOG_LANDING_PAGES = 'oro_seo.sitemap_exclude_landing_pages';
    private const INCLUDE_NOT_IN_WEB_CATALOG_LANDING_PAGES = 'oro_seo.sitemap_include_landing_pages_not_in_web_catalog';

    private MockObject $configManager;
    private MockObject $featureChecker;
    private EventDispatcher $eventDispatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            FixtureDir\LoadWebCatalogPageData::class
        ]);

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $listener = new RestrictSitemapCmsPageByWebCatalogListener(
            $this->configManager,
            $this->getRestrictionProvider(),
            $this->getContainer()->get('oro_seo.modifier.scope_query_builder_modifier')
        );

        $this->eventDispatcher = new EventDispatcher();
        $this->eventDispatcher->addListener(self::EVENT_NAME, [$listener, 'restrictQueryBuilder']);
    }

    private function getRestrictionProvider(): CmsPageSitemapRestrictionProvider
    {
        $restrictionProvider = new CmsPageSitemapRestrictionProvider($this->configManager);
        $restrictionProvider->setFeatureChecker($this->featureChecker);
        $restrictionProvider->addFeature('frontend_master_catalog');

        return $restrictionProvider;
    }

    /**
     * @dataProvider restrictQueryBuilderDataProvider
     */
    public function testRestrictQueryBuilder(
        bool $exclude,
        bool $includeNotOwn,
        array $expected,
        ?string $webCatalogName
    ) {
        $version = '1';
        $webCatalogId = null;
        /** @var Website $website */
        $website = $this->getReference(FixtureDir\LoadWebsiteData::WEBSITE_DEFAULT);

        if ($webCatalogName) {
            /** @var WebCatalog $webCatalog */
            $webCatalogId = $this->getReference($webCatalogName)->getId();
        }

        $expectedIds = array_map(function (string $refName) {
            return $this->getReference($refName)->getId();
        }, $expected);
        sort($expectedIds);

        $this->featureChecker
            ->method('isFeatureEnabled')
            ->with('frontend_master_catalog')
            ->willReturn(null === $webCatalogId);

        $this->configManager->method('get')->willReturnMap([
            [self::WEB_CATALOG, false, false, $website, $webCatalogId],
            [self::EXCLUDE_WEB_CATALOG_LANDING_PAGES, true, false, $website, $exclude],
            [self::INCLUDE_NOT_IN_WEB_CATALOG_LANDING_PAGES, false, false, $website, $includeNotOwn]
        ]);

        /** @var QueryBuilder $qb */
        $qb = self::getContainer()
            ->get('doctrine')
            ->getRepository(Page::class)
            ->createQueryBuilder('page');

        $event = new RestrictSitemapEntitiesEvent($qb, $version, $website);
        $this->eventDispatcher->dispatch($event, self::EVENT_NAME);

        $actualIds = array_map(fn (Page $page) => $page->getId(), $qb->getQuery()->getResult());
        sort($actualIds);

        $this->assertEquals($expectedIds, $actualIds);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function restrictQueryBuilderDataProvider(): array
    {
        return [
            'pages out of web catalog' => [
                'exclude'        => true,
                'includeNotOwn'  => true,
                'expected'       => [
                    FixtureDir\LoadPageData::PAGE2_WEB_CATALOG_SCOPE_DEFAULT,
                    FixtureDir\LoadPageData::PAGE4_WEB_CATALOG_SCOPE_DEFAULT,
                    FixtureDir\LoadPageData::PAGE_OUT_OF_WEB_CATALOG,
                    FixtureDir\LoadPageData::PAGE_WEB_CATALOG_SCOPE_CUSTOMER_GROUP1,
                    FixtureDir\LoadPageData::PAGE_WEB_CATALOG_SCOPE_CUSTOMER1,
                ],
                'webCatalogName' => LoadWebCatalogData::CATALOG_1,
            ],
            'inactive restriction, all pages' => [
                'exclude'        => true,
                'includeNotOwn'  => false,
                'expected'       => [
                    FixtureDir\LoadPageData::PAGE1_WEB_CATALOG_SCOPE_DEFAULT,
                    FixtureDir\LoadPageData::PAGE2_WEB_CATALOG_SCOPE_DEFAULT,
                    FixtureDir\LoadPageData::PAGE3_WEB_CATALOG_SCOPE_DEFAULT,
                    FixtureDir\LoadPageData::PAGE4_WEB_CATALOG_SCOPE_DEFAULT,
                    FixtureDir\LoadPageData::PAGE5_WEB_CATALOG_SCOPE_DEFAULT,
                    FixtureDir\LoadPageData::PAGE_OUT_OF_WEB_CATALOG,
                    FixtureDir\LoadPageData::PAGE_WEB_CATALOG_SCOPE_LOCALIZATION_EN_CA,
                    FixtureDir\LoadPageData::PAGE_WEB_CATALOG_SCOPE_CUSTOMER_GROUP_ANONYMOUS,
                    FixtureDir\LoadPageData::PAGE_WEB_CATALOG_SCOPE_CUSTOMER_GROUP1,
                    FixtureDir\LoadPageData::PAGE_WEB_CATALOG_SCOPE_CUSTOMER1,
                ],
                'webCatalogName' => LoadWebCatalogData::CATALOG_1,
            ],
            'all pages' => [
                'exclude'        => false,
                'includeNotOwn'  => true,
                'expected'       => [
                    FixtureDir\LoadPageData::PAGE1_WEB_CATALOG_SCOPE_DEFAULT,
                    FixtureDir\LoadPageData::PAGE2_WEB_CATALOG_SCOPE_DEFAULT,
                    FixtureDir\LoadPageData::PAGE3_WEB_CATALOG_SCOPE_DEFAULT,
                    FixtureDir\LoadPageData::PAGE4_WEB_CATALOG_SCOPE_DEFAULT,
                    FixtureDir\LoadPageData::PAGE5_WEB_CATALOG_SCOPE_DEFAULT,
                    FixtureDir\LoadPageData::PAGE_OUT_OF_WEB_CATALOG,
                    FixtureDir\LoadPageData::PAGE_WEB_CATALOG_SCOPE_LOCALIZATION_EN_CA,
                    FixtureDir\LoadPageData::PAGE_WEB_CATALOG_SCOPE_CUSTOMER_GROUP_ANONYMOUS,
                    FixtureDir\LoadPageData::PAGE_WEB_CATALOG_SCOPE_CUSTOMER_GROUP1,
                    FixtureDir\LoadPageData::PAGE_WEB_CATALOG_SCOPE_CUSTOMER1,
                ],
                'webCatalogName' => LoadWebCatalogData::CATALOG_1,
            ],
            'pages in web catalog' => [
                'exclude'        => false,
                'includeNotOwn'  => false,
                'expected'       => [
                    FixtureDir\LoadPageData::PAGE1_WEB_CATALOG_SCOPE_DEFAULT,
                    FixtureDir\LoadPageData::PAGE3_WEB_CATALOG_SCOPE_DEFAULT,
                    FixtureDir\LoadPageData::PAGE5_WEB_CATALOG_SCOPE_DEFAULT,
                    FixtureDir\LoadPageData::PAGE_WEB_CATALOG_SCOPE_LOCALIZATION_EN_CA,
                    FixtureDir\LoadPageData::PAGE_WEB_CATALOG_SCOPE_CUSTOMER_GROUP_ANONYMOUS,
                ],
                'webCatalogName' => LoadWebCatalogData::CATALOG_1,
            ],
        ];
    }
}
