<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadFrontendCategoryProductData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @property \Oro\Bundle\FrontendTestFrameworkBundle\Test\Client $client
 */
class ProductControllerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([LoadFrontendCategoryProductData::class]);
    }

    /**
     * @dataProvider viewDataProvider
     */
    public function testView(bool $includeSubcategories, array $expected)
    {
        /** @var Category $secondLevelCategory */
        $secondLevelCategory = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $response = $this->client->requestFrontendGrid(
            [
                'gridName' => 'frontend-product-search-grid',
                RequestProductHandler::CATEGORY_ID_KEY => $secondLevelCategory->getId(),
                RequestProductHandler::INCLUDE_SUBCATEGORIES_KEY => $includeSubcategories,
            ],
            [],
            true
        );
        $result = $this->getJsonResponseContent($response, 200);
        $count = count($expected);
        $this->assertCount($count, $result['data']);
        foreach ($result['data'] as $data) {
            $this->assertContains($data['sku'], $expected);
        }
    }

    public function viewDataProvider(): array
    {
        return [
            'includeSubcategories' => [
                'includeSubcategories' => true,
                'expected' => [
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                ],
            ],
            'excludeSubcategories' => [
                'includeSubcategories' => false,
                'expected' => [
                    LoadProductData::PRODUCT_2,
                ],
            ],
        ];
    }

    /**
     * Test if the category id as a parameter in query don't cause any exceptions,
     * as the SearchCategoryFilteringEventListener is triggered.
     */
    public function testControllerActionWithCategoryId()
    {
        /** @var Category $secondLevelCategory */
        $secondLevelCategory = $this->getReference(LoadCategoryData::SECOND_LEVEL1);

        $this->client->request('GET', $this->getUrl(
            'oro_product_frontend_product_index',
            [
                RequestProductHandler::CATEGORY_ID_KEY => $secondLevelCategory->getId()
            ]
        ));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();
        $this->assertNotEmpty($content);
    }

    /**
     * @dataProvider navigationBarTestDataProvider
     */
    public function testNavigationBar(string $category, array $expectedParts)
    {
        $category = $this->getReference($category);

        $requestParams = [
            'includeSubcategories' => 1,
            'categoryId' => $category->getId()
        ];

        $gridParams = [
            'i' => 1,
            'p' => 25,
            'f' => [],
            'v' => '__all__',
            'a' => 'grid'
        ];

        $gridUrlPart = urlencode(http_build_query($gridParams));

        $url = $this->getUrl(
            'oro_product_frontend_product_index',
            $requestParams
        ).'&grid[frontend-product-search-grid]='.$gridUrlPart;

        $crawler = $this->client->request('GET', $url);

        $navigationBarNode = $crawler->filter('.breadcrumbs')->first()->getNode(0);

        $this->assertObjectHasAttribute('textContent', $navigationBarNode);
        $text = $navigationBarNode->textContent;

        $foundParts = [];
        foreach ($expectedParts as $expectedPart) {
            if (str_contains($text, $expectedPart)) {
                $foundParts[] = $expectedPart;
            }
        }

        $this->assertEquals($expectedParts, $foundParts);
    }

    public function navigationBarTestDataProvider(): array
    {
        return [
            [
                'category' => LoadCategoryData::SECOND_LEVEL1,
                'expectedParts' => [
                    LoadCategoryData::FIRST_LEVEL,
                ],
            ],
            [
                'category' => LoadCategoryData::THIRD_LEVEL1,
                'expectedParts' => [
                    LoadCategoryData::FIRST_LEVEL,
                    LoadCategoryData::SECOND_LEVEL1,
                ],
            ],
            [
                'category' => LoadCategoryData::FIRST_LEVEL,
                'expectedParts' => [
                ],
            ]
        ];
    }
}
