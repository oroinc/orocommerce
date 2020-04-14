<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ProductTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroCatalogBundle/Tests/Functional/Api/Frontend/DataFixtures/category.yml'
        ]);
    }

    public function testGetListFilteredByCategory()
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

    public function testGetListFilteredByRootCategoryIncludingRootCategory()
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

    public function testGetListFilteredByRootCategoryExcludingRootCategory()
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

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'products', 'id' => '<toString(@product1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'products',
                    'id'            => '<toString(@product1->id)>',
                    'relationships' => [
                        'category' => [
                            'data' => [
                                'type' => 'mastercatalogcategories',
                                'id'   => '<toString(@category1->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWithInvisibleCategory()
    {
        $response = $this->get(
            ['entity' => 'products', 'id' => '<toString(@product5->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'products',
                    'id'            => '<toString(@product5->id)>',
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

    public function testGetIncludeCategory()
    {
        $response = $this->get(
            ['entity' => 'products', 'id' => '<toString(@product1->id)>', 'include' => 'category']
        );

        $this->assertResponseContains('get_product_with_included_category.yml', $response);
    }

    public function testGetIncludeCategoryAndVariantsForConfigurableProduct()
    {
        $response = $this->get(
            [
                'entity'  => 'products',
                'id'      => '<toString(@configurable_product1->id)>',
                'include' => 'category,variantProducts'
            ]
        );

        $this->assertResponseContains(
            'get_configurable_product_with_included_category_and_variants.yml',
            $response
        );
    }

    public function testGetIncludeInvisibleCategory()
    {
        $response = $this->get(
            ['entity' => 'products', 'id' => '<toString(@product5->id)>', 'include' => 'category']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'products',
                    'id'            => '<toString(@product5->id)>',
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

    public function testGetIncludeVariantsAndInvisibleCategoryForConfigurableProduct()
    {
        $response = $this->get(
            [
                'entity'  => 'products',
                'id'      => '<toString(@configurable_product2->id)>',
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

    public function testGetSubresourceForCategory()
    {
        $response = $this->getSubresource(
            ['entity' => 'products', 'id' => '<toString(@product1->id)>', 'association' => 'category']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'mastercatalogcategories',
                    'id'         => '<toString(@category1->id)>',
                    'attributes' => [
                        'title' => 'Category 1'
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToGetSubresourceForInvisibleCategory()
    {
        $response = $this->getSubresource(
            ['entity' => 'products', 'id' => '<toString(@product5->id)>', 'association' => 'category'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testGetRelationshipForCategory()
    {
        $response = $this->getRelationship(
            ['entity' => 'products', 'id' => '<toString(@product1->id)>', 'association' => 'category']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'mastercatalogcategories',
                    'id'   => '<toString(@category1->id)>'
                ]
            ],
            $response
        );
    }

    public function testTryToGetRelationshipForInvisibleCategory()
    {
        $response = $this->getRelationship(
            ['entity' => 'products', 'id' => '<toString(@product5->id)>', 'association' => 'category'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToUpdateRelationshipForCategory()
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
