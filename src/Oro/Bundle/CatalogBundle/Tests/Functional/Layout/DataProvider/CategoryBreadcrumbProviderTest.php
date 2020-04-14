<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadMasterCatalogLocalizedTitles;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CategoryBreadcrumbProviderTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(
                LoadCustomerUserData::AUTH_USER,
                LoadCustomerUserData::AUTH_PW
            )
        );

        $this->loadFixtures(
            [
                LoadMasterCatalogLocalizedTitles::class,
                LoadCategoryData::class
            ]
        );
    }

    /**
     * @dataProvider dataProvider
     * @param string $category
     * @param array $urlParts
     */
    public function testHaveCategoriesInBreadcrumbs($category, array $urlParts)
    {
        $this->markTestSkipped('Due to BB-18904');
        $url = $this->getUrl(
            'oro_product_frontend_product_index',
            [
                RequestProductHandler::CATEGORY_ID_KEY           => $this->getCategory($category),
                RequestProductHandler::INCLUDE_SUBCATEGORIES_KEY => 1,
            ]
        );

        $crawler = $this->client->request(
            'GET',
            $url
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $breadCrumbsNodes = $crawler->filter('.breadcrumbs__item a');

        foreach ($breadCrumbsNodes as $key => $node) {
            $this->assertNotNull($node->getAttribute('href'));
            $this->assertNotNull($node->textContent);
            $this->assertEquals($urlParts[$key], trim($node->textContent));
        }
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            [
                'category' => LoadCategoryData::SECOND_LEVEL1,
                'urlParts' => [
                    'All Products',
                    LoadCategoryData::FIRST_LEVEL,
                    LoadCategoryData::SECOND_LEVEL1,
                ]
            ],
            [
                'categoryId' => LoadCategoryData::THIRD_LEVEL1,
                'urlParts'   => [
                    'All Products',
                    LoadCategoryData::FIRST_LEVEL,
                    LoadCategoryData::SECOND_LEVEL1,
                    LoadCategoryData::THIRD_LEVEL1
                ]
            ],
            [
                'categoryId' => LoadCategoryData::SECOND_LEVEL1,
                'urlParts'   => [
                    'All Products',
                    LoadCategoryData::FIRST_LEVEL,
                    LoadCategoryData::SECOND_LEVEL1,
                ]
            ]
        ];
    }

    /**
     * @param string $category
     * @return object
     */
    private function getCategory($category)
    {
        return $this->getReference($category);
    }
}
