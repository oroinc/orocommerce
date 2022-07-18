<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles;

use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures\AbstractLoadWebCatalogData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures\LoadWebCatalogPageData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;
use Symfony\Component\HttpFoundation\Request;

class CMSPageTitleTest extends WebTestCase
{
    use WebsiteSearchExtensionTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadWebCatalogPageData::class
            ]
        );

        $this->reindexProductData();
    }

    public function testWebCatalogTitles()
    {
        $page = $this->getReference(LoadPageData::PAGE_1);
        $crawler = $this->client->request(
            Request::METHOD_GET,
            sprintf('%s-%s', AbstractLoadWebCatalogData::CONTENT_NODE_SLUG, $page->getId())
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertStringContainsString(
            AbstractLoadWebCatalogData::CONTENT_NODE_TITLE,
            $crawler->filter('title')->html()
        );
        static::assertStringContainsString(
            AbstractLoadWebCatalogData::CONTENT_NODE_TITLE,
            $crawler->filter('h1.page-title')->html()
        );
    }
}
