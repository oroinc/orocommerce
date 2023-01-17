<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\Api\Frontend\RestJsonApi\WebCatalogTreeTestCase;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ProductCollectionTest extends WebCatalogTreeTestCase
{
    use WebsiteSearchExtensionTrait;
    use ProductSearchEngineCheckTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product_collection.yml'
        ]);
        $this->switchToWebCatalog();
    }

    protected function postFixtureLoad()
    {
        parent::postFixtureLoad();

        $this->reindexProductData();
    }

    public function testGetWithoutSearchQueryFilter()
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'id'            => '<toString(@catalog1_node11_variant1->id)>',
                    'relationships' => [
                        'products' => [
                            'data' => [
                                ['type' => 'productsearch', 'id' => '<toString(@product4->id)>'],
                                ['type' => 'productsearch', 'id' => '<toString(@product3->id)>'],
                                ['type' => 'productsearch', 'id' => '<toString(@product1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        self::assertArrayNotHasKey('included', self::jsonToArray($response->getContent()));
    }

    public function testGetWithoutSearchQueryFilterAndWithIncludeFilter()
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant1->id)>'],
            ['include' => 'products']
        );
        $this->assertResponseContains('get_product_collection.yml', $response);
    }

    public function testGetWithSearchQueryFilter()
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant1->id)>'],
            ['filter' => ['searchQuery' => 'sku = PSKU3']]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'id'            => '<toString(@catalog1_node11_variant1->id)>',
                    'relationships' => [
                        'products' => [
                            'data' => [
                                ['type' => 'productsearch', 'id' => '<toString(@product3->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        self::assertArrayNotHasKey('included', self::jsonToArray($response->getContent()));
    }

    public function testGetForAnotherLocalizationAndWithIncludeFilter()
    {
        $this->getReferenceRepository()->setReference('current_localization', $this->getCurrentLocalization());
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant2_es->id)>'],
            ['include' => 'products.product'],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );
        $this->assertResponseContains('get_product_collection_localization.yml', $response);
    }

    public function testGetWithFieldsAndIncludeFilters()
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant1->id)>'],
            ['fields[productsearch]' => 'name', 'include' => 'products']
        );
        $this->assertResponseContains(
            [
                'data'     => [
                    'id'            => '<toString(@catalog1_node11_variant1->id)>',
                    'relationships' => [
                        'products' => [
                            'data' => [
                                ['type' => 'productsearch', 'id' => '<toString(@product4->id)>'],
                                ['type' => 'productsearch', 'id' => '<toString(@product3->id)>'],
                                ['type' => 'productsearch', 'id' => '<toString(@product1->id)>']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'productsearch',
                        'id'         => '<toString(@product4->id)>',
                        'attributes' => [
                            'name' => 'Product 4'
                        ]
                    ],
                    [
                        'type'       => 'productsearch',
                        'id'         => '<toString(@product3->id)>',
                        'attributes' => [
                            'name' => 'Product 3'
                        ]
                    ],
                    [
                        'type'       => 'productsearch',
                        'id'         => '<toString(@product1->id)>',
                        'attributes' => [
                            'name' => 'Product 1'
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        for ($i = 0; $i < 3; $i++) {
            self::assertArrayNotHasKey('sku', $responseContent['included'][$i]['attributes']);
            self::assertArrayNotHasKey('relationships', $responseContent['included'][$i]);
        }
    }

    public function testGetWithFieldsFilterButWithoutIncludeFilterShouldNotReturnIncludedEntities()
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant1->id)>'],
            ['fields[productsearch]' => 'name']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'id'            => '<toString(@catalog1_node11_variant1->id)>',
                    'relationships' => [
                        'products' => [
                            'data' => [
                                ['type' => 'productsearch', 'id' => '<toString(@product4->id)>'],
                                ['type' => 'productsearch', 'id' => '<toString(@product3->id)>'],
                                ['type' => 'productsearch', 'id' => '<toString(@product1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        self::assertArrayNotHasKey('included', self::jsonToArray($response->getContent()));
    }

    public function testTryToGetForNotExistingContentVariant()
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '9999999'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToGetForContentVariantThatIsNotProductCollection()
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant3_system_page->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'Access Denied.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetForContentVariantThatNotApplicableForCurrentLocalization()
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant2_es->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'Access Denied.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testGetWithAggregationsFilter()
    {
        if (!$this->isOrmEngine()) {
            $this->markTestSkipped('This test works only with ORM search engine.');
        }

        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant1->id)>'],
            ['filter[aggregations]' => 'minimalPrice sum']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'id'            => '<toString(@catalog1_node11_variant1->id)>',
                    'relationships' => [
                        'products' => [
                            'meta' => [
                                'aggregatedData' => [
                                    'minimalPriceSum' => 26
                                ]
                            ],
                            'data' => [
                                ['type' => 'productsearch', 'id' => '<toString(@product4->id)>'],
                                ['type' => 'productsearch', 'id' => '<toString(@product3->id)>'],
                                ['type' => 'productsearch', 'id' => '<toString(@product1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testPaginationLinksForFirstPage()
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant1->id)>'],
            ['filter' => ['searchQuery' => 'isVariant = 0'], 'page' => ['size' => 2]],
            ['HTTP_HATEOAS' => true]
        );

        $url = '{baseUrl}/productcollection/' . $this->getReference('catalog1_node11_variant1')->getId();
        $urlWithFilter = $url . '?filter%5BsearchQuery%5D=isVariant%20%3D%200';
        $expectedLinks = $this->getExpectedContentWithPaginationLinks([
            'data' => [
                'relationships' => [
                    'products' => [
                        'links' => [
                            'next' => $urlWithFilter . '&page%5Bsize%5D=2&page%5Bnumber%5D=2'
                        ]
                    ]
                ]
            ]
        ]);
        $this->assertResponseContains($expectedLinks, $response);
        self::assertCount(2, self::jsonToArray($response->getContent())['data']['relationships']['products']['data']);
    }

    public function testPaginationLinksForLastPage()
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant1->id)>'],
            ['filter' => ['searchQuery' => 'isVariant = 0'], 'page' => ['size' => 2, 'number' => 2]],
            ['HTTP_HATEOAS' => true]
        );

        $url = '{baseUrl}/productcollection/' . $this->getReference('catalog1_node11_variant1')->getId();
        $urlWithFilter = $url . '?filter%5BsearchQuery%5D=isVariant%20%3D%200';
        $expectedLinks = $this->getExpectedContentWithPaginationLinks([
            'data' => [
                'relationships' => [
                    'products' => [
                        'links' => [
                            'first' => $urlWithFilter . '&page%5Bsize%5D=2',
                            'prev'  => $urlWithFilter . '&page%5Bsize%5D=2'
                        ]
                    ]
                ]
            ]
        ]);
        $this->assertResponseContains($expectedLinks, $response);
        self::assertCount(1, self::jsonToArray($response->getContent())['data']['relationships']['products']['data']);
    }
}
