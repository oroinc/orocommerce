<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures\AbstractLoadWebCatalogData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures\LoadWebCatalogCategoryData;

class CategoryPageTitleTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped('Skipped by BAP-22079');
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures(
            [
                LoadWebCatalogCategoryData::class,
            ]
        );
        $this->getContainer()->get('oro_catalog.tests.layout.data_provider.category.cache')->deleteAll();
        $this->getContainer()->get('oro_search.search.engine.indexer')->reindex(Product::class);
    }

    protected function tearDown(): void
    {
        $this->getContainer()->get('oro_catalog.tests.layout.data_provider.category.cache')->deleteAll();
        parent::tearDown();
    }

    public function testWebCatalogTitles()
    {
        $crawler = $this->client->request('GET', AbstractLoadWebCatalogData::CONTENT_NODE_SLUG);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200, $result->getContent());

        static::assertStringContainsString(
            AbstractLoadWebCatalogData::CONTENT_NODE_TITLE,
            $crawler->filter('title')->html()
        );

        static::assertStringContainsString(
            AbstractLoadWebCatalogData::CONTENT_NODE_TITLE,
            $crawler->filter('h1.category-title')->html()
        );
    }
}
