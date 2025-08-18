<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\SearchBundle\Engine\Orm;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\ApiFrontend\RestJsonApi\WebCatalogTreeTestCase;
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

    private ?array $initialEnabledLocalizations;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/ApiFrontend/DataFixtures/product_collection.yml'
        ]);

        $configManager = self::getConfigManager();
        $this->initialEnabledLocalizations = $configManager->get('oro_locale.enabled_localizations');
        $configManager->set(
            'oro_locale.enabled_localizations',
            LoadLocalizationData::getLocalizationIds(self::getContainer())
        );
        $configManager->flush();

        $this->switchToWebCatalog();

        self::reindexProductData();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_locale.enabled_localizations', $this->initialEnabledLocalizations);
        $configManager->flush();

        parent::tearDown();
    }

    public function testGetWithoutSearchQueryFilter(): void
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'id' => '<toString(@catalog1_node11_variant1->id)>',
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

    public function testGetWithoutSearchQueryFilterAndWithIncludeFilter(): void
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant1->id)>'],
            ['include' => 'products']
        );
        $this->assertResponseContains('get_product_collection.yml', $response);
    }

    public function testGetWithSearchQueryFilter(): void
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant1->id)>'],
            ['filter' => ['searchQuery' => 'sku = PSKU3']]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'id' => '<toString(@catalog1_node11_variant1->id)>',
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

    public function testGetForAnotherLocalizationAndWithIncludeFilter(): void
    {
        $this->getReferenceRepository()->setReference('current_localization', $this->getCurrentLocalization());
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant2_es->id)>'],
            ['include' => 'products.product'],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );
        $this->assertResponseContains('get_product_collection_localization.yml', $response);
    }

    public function testGetWithFieldsAndIncludeFilters(): void
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant1->id)>'],
            ['fields[productsearch]' => 'name', 'include' => 'products']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'id' => '<toString(@catalog1_node11_variant1->id)>',
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
                        'type' => 'productsearch',
                        'id' => '<toString(@product4->id)>',
                        'attributes' => [
                            'name' => 'Product 4'
                        ]
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product3->id)>',
                        'attributes' => [
                            'name' => 'Product 3'
                        ]
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product1->id)>',
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

    public function testGetWithFieldsFilterButWithoutIncludeFilterShouldNotReturnIncludedEntities(): void
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant1->id)>'],
            ['fields[productsearch]' => 'name']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'id' => '<toString(@catalog1_node11_variant1->id)>',
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

    public function testTryToGetForNotExistingContentVariant(): void
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '9999999'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToGetForContentVariantThatIsNotProductCollection(): void
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant3_system_page->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'Access Denied.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetForContentVariantThatNotApplicableForCurrentLocalization(): void
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant2_es->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'Access Denied.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testGetWithAggregationsFilter(): void
    {
        $searchEngine = self::getContainer()->get('oro_website_search.engine.parameters')->getEngineName();
        if (Orm::ENGINE_NAME !== $searchEngine) {
            $this->markTestSkipped('This test works only with ORM search engine.');
        }

        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant1->id)>'],
            ['filter[aggregations]' => 'minimalPrice sum']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'id' => '<toString(@catalog1_node11_variant1->id)>',
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

    public function testPaginationLinksForFirstPage(): void
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant1->id)>'],
            ['filter' => ['searchQuery' => 'isVariant = 0'], 'page' => ['size' => 2]],
            ['HTTP_HATEOAS' => true]
        );

        $url = '{baseUrl}/productcollection/' . $this->getReference('catalog1_node11_variant1')->getId();
        $urlWithFilter = $url . '?filter%5BsearchQuery%5D=isVariant%20%3D%200';
        $this->assertResponseContains(
            [
                'data' => [
                    'relationships' => [
                        'products' => [
                            'links' => [
                                'next' => $urlWithFilter . '&page%5Bsize%5D=2&page%5Bnumber%5D=2'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        self::assertCount(2, self::jsonToArray($response->getContent())['data']['relationships']['products']['data']);
    }

    public function testPaginationLinksForLastPage(): void
    {
        $response = $this->get(
            ['entity' => 'productcollection', 'id' => '<toString(@catalog1_node11_variant1->id)>'],
            ['filter' => ['searchQuery' => 'isVariant = 0'], 'page' => ['size' => 2, 'number' => 2]],
            ['HTTP_HATEOAS' => true]
        );

        $url = '{baseUrl}/productcollection/' . $this->getReference('catalog1_node11_variant1')->getId();
        $urlWithFilter = $url . '?filter%5BsearchQuery%5D=isVariant%20%3D%200';
        $this->assertResponseContains(
            [
                'data' => [
                    'relationships' => [
                        'products' => [
                            'links' => [
                                'first' => $urlWithFilter . '&page%5Bsize%5D=2',
                                'prev' => $urlWithFilter . '&page%5Bsize%5D=2'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        self::assertCount(1, self::jsonToArray($response->getContent())['data']['relationships']['products']['data']);
    }
}
