<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\EventListener;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures\LoadWebCatalogPageData;

class RestrictSitemapCmsPageByWebCatalogListenerTest extends WebTestCase
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadWebCatalogPageData::class
        ]);

        $this->configManager = $this->getContainer()->get('oro_config.manager');
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
            ->dispatch('oro_seo.event.restrict_sitemap_entity.cms_page', $event);

        $actual = array_map(function (Page $page) {
            return $page->getId();
        }, $qb->getQuery()->getResult());

        // All page are available including not test fixtures `About` and `Contact Us` pages
        $this->assertCount(5, $actual);
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
            ->dispatch('oro_seo.event.restrict_sitemap_entity.cms_page', $event);

        $actual = $qb->getQuery()->getResult();
        $expected = [$this->getReference(LoadPageData::PAGE_1)];

        $this->assertEquals($expected, $actual);
    }
}
