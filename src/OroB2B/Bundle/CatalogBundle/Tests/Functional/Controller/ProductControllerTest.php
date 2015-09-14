<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Handler\RequestProductHandler;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;

/**
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));
        $this->loadFixtures(['OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData']);
    }

    /**
     * @dataProvider viewDataProvider
     *
     * @param bool $includeSubcategories
     * @param int $expectedCount
     */
    public function testView($includeSubcategories, $expectedCount)
    {
        /** @var Category $secondLevelCategory */
        $secondLevelCategory = $this->getReference(LoadCategoryData::SECOND_LEVEL1);

        $response = $this->client->requestGrid(
            [
                'gridName' => 'products-grid',
                RequestProductHandler::CATEGORY_ID_KEY => $secondLevelCategory->getId(),
                RequestProductHandler::INCLUDE_SUBCATEGORIES_KEY => $includeSubcategories,
            ]
        );
        $result = $this->getJsonResponseContent($response, 200);
        $this->assertCount($expectedCount, $result['data']);
        foreach ($result['data'] as $data) {
            $this->assertEquals($data['productName'], LoadCategoryProductData::getRelations()[$data['category_name']]);
        }
    }

    /**
     * @return array
     */
    public function viewDataProvider()
    {
        return [
            'includeSubcategories' => [
                'includeSubcategories' => true,
                'expectedCount' => 3,
            ],
            'excludeSubcategories' => [
                'includeSubcategories' => false,
                'expectedCount' => 1,
            ],
        ];
    }

    public function testSidebarAction()
    {
        $categoryId = 2;
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_catalog_category_product_sidebar',
                [RequestProductHandler::CATEGORY_ID_KEY => $categoryId]
            )
        );
        $json = $crawler->filterXPath('//*[@data-page-component-options]')->attr('data-page-component-options');
        $this->assertJson($json);
        $arr = json_decode($json, true);
        $this->assertEquals($arr['defaultCategoryId'], $categoryId);
        $this->assertCount(5, $arr['data']);
    }
}
