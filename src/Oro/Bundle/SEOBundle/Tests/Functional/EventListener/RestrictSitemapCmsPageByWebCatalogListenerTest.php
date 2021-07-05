<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\EventListener;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures\LoadWebCatalogPageData;

class RestrictSitemapCmsPageByWebCatalogListenerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadWebCatalogPageData::class
        ]);

        $this->configManager = self::getConfigManager('global');
    }

    public function testRestrictDisabled()
    {
        $version = '1';
        $this->configManager->set('oro_web_catalog.web_catalog', null);
        $this->configManager->flush();

        $qb = $this->getContainer()->get('doctrine')->getManagerForClass(Page::class)
            ->getRepository(Page::class)
            ->createQueryBuilder('page');

        $event = new RestrictSitemapEntitiesEvent($qb, $version);
        $this->getContainer()->get('event_dispatcher')
            ->dispatch($event, 'oro_seo.event.restrict_sitemap_entity.cms_page');

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

    public function testRestrictQueryBuilder()
    {
        $version = '1';
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);
        $this->configManager->set('oro_web_catalog.web_catalog', $webCatalog->getId());
        $this->configManager->flush();

        $qb = $this->getContainer()->get('doctrine')->getManagerForClass(Page::class)
            ->getRepository(Page::class)
            ->createQueryBuilder('page');

        $event = new RestrictSitemapEntitiesEvent($qb, $version);
        $this->getContainer()->get('event_dispatcher')
            ->dispatch($event, 'oro_seo.event.restrict_sitemap_entity.cms_page');

        $actual = $qb->getQuery()->getResult();
        $expected = [$this->getReference(LoadPageData::PAGE_1)];

        $this->assertEquals($expected, $actual);
    }
}
