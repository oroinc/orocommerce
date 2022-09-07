<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\EventListener\RestrictSitemapCmsPageByWebCatalogListener;
use Oro\Bundle\SEOBundle\Sitemap\Provider\CmsPageSitemapRestrictionProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures\LoadWebCatalogPageData;
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


    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadWebCatalogPageData::class
        ]);

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $listener = new RestrictSitemapCmsPageByWebCatalogListener(
            $this->configManager,
            $this->getContainer()->get('oro_seo.sitemap.provider.web_catalog_scope_criteria_provider')
        );
        $listener->setProvider($this->getRestrictionProvider());

        /** @var EventDispatcher eventDispatcher */
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

    public function testRestrictDisabled()
    {
        $version = '1';

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $qb = $this->getContainer()->get('doctrine')->getManagerForClass(Page::class)
            ->getRepository(Page::class)
            ->createQueryBuilder('page');

        $event = new RestrictSitemapEntitiesEvent($qb, $version);
        $this->eventDispatcher->dispatch($event, self::EVENT_NAME);

        $actual = array_map(function (Page $page) {
            return $page->getId();
        }, $qb->getQuery()->getResult());

        $this->assertCount(3, $actual);
        $expected = [
            $this->getReference(LoadPageData::PAGE_1),
            $this->getReference(LoadPageData::PAGE_2),
            $this->getReference(LoadPageData::PAGE_3)
        ];

        foreach ($expected as $page) {
            $this->assertContains($page->getId(), $actual);
        }
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
            [self::WEB_CATALOG, false, false, null, $webCatalogId],
            [self::EXCLUDE_WEB_CATALOG_LANDING_PAGES, true, false, null, $exclude],
            [self::INCLUDE_NOT_IN_WEB_CATALOG_LANDING_PAGES, false, false, null, $includeNotOwn]
        ]);

        /** @var QueryBuilder $qb */
        $qb = $this->getContainer()->get('doctrine')->getManagerForClass(Page::class)
            ->getRepository(Page::class)
            ->createQueryBuilder('page');

        $event = new RestrictSitemapEntitiesEvent($qb, $version);
        $this->eventDispatcher->dispatch($event, self::EVENT_NAME);

        $actualIds = array_map(function ($page) {
            return $page->getId();
        }, $qb->getQuery()->getResult());
        sort($actualIds);

        $this->assertEquals($expectedIds, $actualIds);
    }

    public function restrictQueryBuilderDataProvider(): array
    {
        return [
            'no restriction - no web catalog, exclude=true, include not own=true' => [
                'exclude'        => true,
                'includeNotOwn'  => true,
                'expected'       => [LoadPageData::PAGE_1, LoadPageData::PAGE_2, LoadPageData::PAGE_3],
                'webCatalogName' => null,
            ],
            'no restriction - no web catalog, exclude=true, include not own=false' => [
                'exclude'        => true,
                'includeNotOwn'  => false,
                'expected'       => [LoadPageData::PAGE_1, LoadPageData::PAGE_2, LoadPageData::PAGE_3],
                'webCatalogName' => null,
            ],
            'no restriction - no web catalog, exclude=false, include not own=true' => [
                'exclude'        => false,
                'includeNotOwn'  => true,
                'expected'       => [LoadPageData::PAGE_1, LoadPageData::PAGE_2, LoadPageData::PAGE_3],
                'webCatalogName' => null,
            ],
            'no restriction - no web catalog, exclude=false, include not own=false' => [
                'exclude'        => false,
                'includeNotOwn'  => false,
                'expected'       => [LoadPageData::PAGE_1, LoadPageData::PAGE_2, LoadPageData::PAGE_3],
                'webCatalogName' => null,
            ],
            'pages not owned by CATALOG_1' => [
                'exclude'        => true,
                'includeNotOwn'  => true,
                'expected'       => [LoadPageData::PAGE_2, LoadPageData::PAGE_3],
                'webCatalogName' => LoadWebCatalogData::CATALOG_1,
            ],
            'no restriction - web catalog, exclude=true, include not own=false' => [
                'exclude'        => true,
                'includeNotOwn'  => false,
                'expected'       => [LoadPageData::PAGE_1, LoadPageData::PAGE_2, LoadPageData::PAGE_3],
                'webCatalogName' => LoadWebCatalogData::CATALOG_1,
            ],
            'no restriction - web catalog, exclude=false, include not own=true' => [
                'exclude'        => false,
                'includeNotOwn'  => true,
                'expected'       => [LoadPageData::PAGE_1, LoadPageData::PAGE_2, LoadPageData::PAGE_3],
                'webCatalogName' => LoadWebCatalogData::CATALOG_1,
            ],
            'pages owned by CATALOG_1' => [
                'exclude'        => false,
                'includeNotOwn'  => false,
                'expected'       => [LoadPageData::PAGE_1],
                'webCatalogName' => LoadWebCatalogData::CATALOG_1,
            ],
        ];
    }
}
