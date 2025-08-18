<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ProductTest extends FrontendRestJsonApiTestCase
{
    private ?array $initialEnabledLocalizations;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroCatalogBundle/Tests/Functional/ApiFrontend/DataFixtures/category.yml'
        ]);

        $configManager = self::getConfigManager();
        $this->initialEnabledLocalizations = $configManager->get('oro_locale.enabled_localizations');
        $configManager->set(
            'oro_locale.enabled_localizations',
            LoadLocalizationData::getLocalizationIds(self::getContainer())
        );
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_locale.enabled_localizations', $this->initialEnabledLocalizations);
        $configManager->flush();

        parent::tearDown();
    }

    public function testGetListFilteredByCategory(): void
    {
        $response = $this->cget(
            ['entity' => 'products'],
            ['filter[category]' => '<toString(@category1->id)>', 'fields[products]' => 'sku,category']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'products', 'id' => '<toString(@product1->id)>'],
                    ['type' => 'products', 'id' => '<toString(@product2->id)>'],
                    ['type' => 'products', 'id' => '<toString(@configurable_product1->id)>'],
                    ['type' => 'products', 'id' => '<toString(@configurable_product1_variant1->id)>'],
                    ['type' => 'products', 'id' => '<toString(@configurable_product1_variant2->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByRootCategoryIncludingRootCategory(): void
    {
        $response = $this->cget(
            ['entity' => 'products'],
            ['filter[rootCategory][gte]' => '<toString(@category1->id)>', 'fields[products]' => 'sku,category']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'products', 'id' => '<toString(@product1->id)>'],
                    ['type' => 'products', 'id' => '<toString(@product2->id)>'],
                    ['type' => 'products', 'id' => '<toString(@product3->id)>'],
                    ['type' => 'products', 'id' => '<toString(@configurable_product1->id)>'],
                    ['type' => 'products', 'id' => '<toString(@configurable_product1_variant1->id)>'],
                    ['type' => 'products', 'id' => '<toString(@configurable_product1_variant2->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByRootCategoryExcludingRootCategory(): void
    {
        $response = $this->cget(
            ['entity' => 'products'],
            ['filter[rootCategory][gt]' => '<toString(@category1->id)>', 'fields[products]' => 'sku,category']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'products', 'id' => '<toString(@product3->id)>']
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'products', 'id' => '<toString(@product1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'products',
                    'id' => '<toString(@product1->id)>',
                    'relationships' => [
                        'category' => [
                            'data' => [
                                'type' => 'mastercatalogcategories',
                                'id' => '<toString(@category1->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWithInvisibleCategory(): void
    {
        $response = $this->get(
            ['entity' => 'products', 'id' => '<toString(@product5->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'products',
                    'id' => '<toString(@product5->id)>',
                    'relationships' => [
                        'category' => [
                            'data' => null
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetIncludeCategory(): void
    {
        $response = $this->get(
            ['entity' => 'products', 'id' => '<toString(@product1->id)>', 'include' => 'category']
        );

        $this->assertResponseContains('get_product_with_included_category.yml', $response);
    }

    public function testGetIncludeCategoryAndVariantsForConfigurableProduct(): void
    {
        $response = $this->get(
            [
                'entity' => 'products',
                'id' => '<toString(@configurable_product1->id)>',
                'include' => 'category,variantProducts'
            ]
        );

        $this->assertResponseContains(
            'get_configurable_product_with_included_category_and_variants.yml',
            $response
        );
    }

    public function testGetIncludeInvisibleCategory(): void
    {
        $response = $this->get(
            ['entity' => 'products', 'id' => '<toString(@product5->id)>', 'include' => 'category']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'products',
                    'id' => '<toString(@product5->id)>',
                    'relationships' => [
                        'category' => [
                            'data' => null
                        ]
                    ]
                ]
            ],
            $response
        );

        // check that 'included' section is absent in the response
        $responseContent = self::jsonToArray($response->getContent());
        $this->assertFalse(array_key_exists('included', $responseContent));
    }

    public function testGetIncludeVariantsAndInvisibleCategoryForConfigurableProduct(): void
    {
        $response = $this->get(
            [
                'entity' => 'products',
                'id' => '<toString(@configurable_product2->id)>',
                'include' => 'category,variantProducts'
            ]
        );

        $this->assertResponseContains(
            'get_configurable_product_with_included_hidden_category_and_variants.yml',
            $response
        );

        // check that 'included' section have only products in response
        $responseContent = self::jsonToArray($response->getContent());
        $this->assertCount(2, $responseContent['included']);
    }

    public function testGetSubresourceForCategory(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'products', 'id' => '<toString(@product1->id)>', 'association' => 'category']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'mastercatalogcategories',
                    'id' => '<toString(@category1->id)>',
                    'attributes' => [
                        'title' => 'Category 1'
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToGetSubresourceForInvisibleCategory(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'products', 'id' => '<toString(@product5->id)>', 'association' => 'category'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testGetRelationshipForCategory(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'products', 'id' => '<toString(@product1->id)>', 'association' => 'category']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'mastercatalogcategories',
                    'id' => '<toString(@category1->id)>'
                ]
            ],
            $response
        );
    }

    public function testTryToGetRelationshipForInvisibleCategory(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'products', 'id' => '<toString(@product5->id)>', 'association' => 'category'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToUpdateRelationshipForCategory(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'products', 'id' => '<toString(@product1->id)>', 'association' => 'category'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
