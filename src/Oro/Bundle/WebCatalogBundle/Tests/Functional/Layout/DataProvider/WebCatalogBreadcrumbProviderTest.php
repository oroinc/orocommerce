<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogCategoryVariantsData;

class WebCatalogBreadcrumbProviderTest extends WebTestCase
{
    use SearchExtensionTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                LoadWebCatalogCategoryVariantsData::class
            ]
        );

        self::getContainer()->get('oro_website_search.indexer')->reindex();
    }

    /**
     * @dataProvider getSlugs
     */
    public function testBreadcrumbs(string $reference, int $expectedCount, array $expectedBreadcrumbs): void
    {
        $crawler = $this->client->request('GET', '/'.$reference);
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertStringContainsString(
            $reference,
            $crawler->filter('title')->html()
        );

        self::assertStringContainsString(
            $reference,
            $crawler->filter('h1.category-title')->html()
        );

        $breadcrumbs = [];
        /** @var \DOMElement $item */
        foreach ($crawler->filter('.breadcrumbs__item a') as $key => $item) {
            self::assertEquals($expectedBreadcrumbs[$key], $item->textContent);
            $breadcrumbs[] = trim($item->textContent);
        }

        self::assertCount($expectedCount, $breadcrumbs);
    }

    public function testBreadcrumbsWhenInSubfolder(): void
    {
        // Emulates subfolder request.
        $baseUrl = '/custom/base/url/';
        $crawler = $this->client->request(
            'GET',
            $baseUrl . 'app.php/' . LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1,
            [],
            [],
            [
                'SCRIPT_NAME' => '/custom/base/url/app.php',
                'SCRIPT_FILENAME' => 'app.php'
            ]
        );

        $breadcrumbUrls = [];

        /** @var \DOMElement $item */
        foreach ($crawler->filter('.breadcrumbs__item a') as $key => $item) {
            $breadcrumbUrls[] = $item->getAttribute('href');
        }

        self::assertCount(2, $breadcrumbUrls);
        foreach ($breadcrumbUrls as $url) {
            self::assertStringContainsString($baseUrl . 'app.php/', $url);
        }
    }

    public function getSlugs(): array
    {
        return [
            [
                LoadContentNodesData::CATALOG_1_ROOT,
                0,
                [
                    LoadContentNodesData::CATALOG_1_ROOT,
                ]
            ],
            [
                LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1,
                1,
                [
                    LoadContentNodesData::CATALOG_1_ROOT,
                ]
            ],
            [
                LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1,
                2,
                [
                    LoadContentNodesData::CATALOG_1_ROOT,
                    LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1,
                ]
            ],
            [
                LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2,
                2,
                [
                    LoadContentNodesData::CATALOG_1_ROOT,
                    LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1,
                ]
            ],
        ];
    }
}
