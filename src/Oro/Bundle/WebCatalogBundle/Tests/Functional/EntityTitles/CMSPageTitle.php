<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures\AbstractLoadWebCatalogData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures\LoadWebCatalogPageData;

class CMSPageTitle extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadWebCatalogPageData::class
            ]
        );
    }

    public function testWebCatalogTitles()
    {
        $crawler = $this->client->request('GET', AbstractLoadWebCatalogData::CONTENT_NODE_SLUG);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains(
            AbstractLoadWebCatalogData::CONTENT_NODE_TITLE,
            $crawler->filter('title')->html()
        );
        $this->assertContains(
            AbstractLoadWebCatalogData::CONTENT_NODE_TITLE,
            $crawler->filter('h1.page-title')->html()
        );
    }
}
