<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures\AbstractLoadWebCatalogData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures\LoadWebCatalogProductData;

class ProductPageTitleTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadWebCatalogProductData::class
            ]
        );
    }

    public function testWebCatalogTitles()
    {
        $crawler = $this->client->request('GET', AbstractLoadWebCatalogData::CONTENT_NODE_SLUG);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertStringContainsString(
            AbstractLoadWebCatalogData::CONTENT_NODE_TITLE,
            $crawler->filter('title')->html()
        );

        static::assertStringContainsString(
            AbstractLoadWebCatalogData::CONTENT_NODE_TITLE,
            $crawler->filter('.product-view h1.page-title')->html()
        );
    }
}
