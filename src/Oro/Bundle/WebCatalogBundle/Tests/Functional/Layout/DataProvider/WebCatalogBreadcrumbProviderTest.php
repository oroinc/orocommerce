<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogCategoryVariantsData;

class WebCatalogBreadcrumbProviderTest extends WebTestCase
{
    use SearchExtensionTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->client->useHashNavigation(false);

        $this->loadFixtures(
            [
                LoadWebCatalogCategoryVariantsData::class
            ]
        );
    }

    /**
     * @dataProvider getSlugs
     * @param $reference string
     * @param $expectedCount int
     * @param $expectedBreadcrumbs array
     */
    public function testBreadcrumbs($reference, $expectedCount, $expectedBreadcrumbs)
    {
        $crawler = $this->client->request('GET', '/'.$reference);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains(
            $reference,
            $crawler->filter('title')->html()
        );

        $this->assertContains(
            $reference,
            $crawler->filter('h1.category-title')->html()
        );

        $breadcrumbs = [];
        /**
         * @var $item \DOMElement
         */
        foreach ($crawler->filter('.breadcrumbs__item a') as $key => $item) {
            $this->assertEquals($expectedBreadcrumbs[$key], $item->textContent);
            $breadcrumbs[] = trim($item->textContent);
        }

        $this->assertCount($expectedCount, $breadcrumbs);
    }

    /**
     * @return array
     */
    public function getSlugs()
    {
        return [
            [
                LoadContentNodesData::CATALOG_1_ROOT,
                1,
                [
                    'Products categories'
                ]
            ],
            [
                LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1,
                2,
                [
                    'Products categories',
                    LoadCategoryData::FIRST_LEVEL
                ]
            ],
            [
                LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1,
                3,
                [
                    'Products categories',
                    LoadCategoryData::FIRST_LEVEL,
                    LoadCategoryData::SECOND_LEVEL1,
                ]
            ],
            [
                LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2,
                3,
                [
                    'Products categories',
                    LoadCategoryData::FIRST_LEVEL,
                    LoadCategoryData::SECOND_LEVEL2,
                ]
            ],
        ];
    }
}
