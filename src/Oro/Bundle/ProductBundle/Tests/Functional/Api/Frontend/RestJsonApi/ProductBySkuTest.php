<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;

/**
 * Tests "products" API resource when SKU is used as the product identifier.
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ProductBySkuTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // guard
        self::assertEquals(
            ['in_stock', 'out_of_stock'],
            $this->getConfigManager()->get('oro_product.general_frontend_product_visibility')
        );

        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product.yml',
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product_prices.yml'
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'products'],
            ['page[size]' => 100, 'sort' => 'productId'],
            ['HTTP_X-Product-ID' => 'sku']
        );

        $this->assertResponseContains('cget_product_by_sku.yml', $response);
    }

    public function testGetListFilterBySeveralIds()
    {
        $response = $this->cget(
            ['entity' => 'products'],
            ['filter' => ['id' => 'PSKU1,PSKU2,PSKU3'], 'sort' => '-id'],
            ['HTTP_X-Product-ID' => 'sku']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'products', 'id' => 'PSKU3'],
                    ['type' => 'products', 'id' => 'PSKU1']
                ]
            ],
            $response
        );
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'products', 'id' => '@product1->sku'],
            [],
            ['HTTP_X-Product-ID' => 'sku']
        );

        $expectedData = $this->getResponseData('get_product.yml');
        $expectedData['data']['attributes']['productId'] = (int)$expectedData['data']['id'];
        $expectedData['data']['id'] = $expectedData['data']['attributes']['sku'];
        unset($expectedData['data']['attributes']['sku']);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testGetOnlyUpcomingAttribute()
    {
        $response = $this->get(
            ['entity' => 'products', 'id' => '@product1->sku'],
            ['fields[products]' => 'upcoming'],
            ['HTTP_X-Product-ID' => 'sku']
        );

        $this->assertResponseContains(['data' => ['attributes' => ['upcoming' => true]]], $response);
    }

    public function testGetOnlyAvailabilityDateAttribute()
    {
        $response = $this->get(
            ['entity' => 'products', 'id' => '@product1->sku'],
            ['fields[products]' => 'availabilityDate'],
            ['HTTP_X-Product-ID' => 'sku']
        );

        $this->assertResponseContains(
            ['data' => ['attributes' => ['availabilityDate' => '2119-01-20T20:30:00Z']]],
            $response
        );
    }

    public function testGetOnlyLowInventoryAttribute()
    {
        $response = $this->get(
            ['entity' => 'products', 'id' => '@product1->sku'],
            ['fields[products]' => 'lowInventory'],
            ['HTTP_X-Product-ID' => 'sku']
        );

        $this->assertResponseContains(['data' => ['attributes' => ['lowInventory' => true]]], $response);
    }

    public function testGetWithIncludeVariantProducts()
    {
        $response = $this->get(
            ['entity' => 'products', 'id' => '@configurable_product1->sku'],
            ['include' => 'variantProducts'],
            ['HTTP_X-Product-ID' => 'sku']
        );

        $this->assertResponseContains('get_configurable_product_with_variants_by_sku.yml', $response);
    }

    public function testGetWithIncludeParentProducts()
    {
        $response = $this->get(
            ['entity' => 'products', 'id' => '@configurable_product1_variant1->sku'],
            ['include' => 'parentProducts'],
            ['HTTP_X-Product-ID' => 'sku']
        );

        $this->assertResponseContains('get_product_with_parent_products_by_sku.yml', $response);
    }

    public function testGetSubresourceForVariantProducts()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'products',
                'id'          => '@configurable_product3->sku',
                'association' => 'variantProducts'
            ],
            [],
            ['HTTP_X-Product-ID' => 'sku']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'products',
                        'id'         => '@configurable_product1_variant1->sku',
                        'attributes' => [
                            'productId' => '@configurable_product1_variant1->id'
                        ]
                    ],
                    [
                        'type'       => 'products',
                        'id'         => '@configurable_product3_variant1->sku',
                        'attributes' => [
                            'productId' => '@configurable_product3_variant1->id'
                        ]
                    ],
                    [
                        'type'       => 'products',
                        'id'         => '@configurable_product3_variant2->sku',
                        'attributes' => [
                            'productId' => '@configurable_product3_variant2->id'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForVariantProducts()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'products',
                'id'          => '@configurable_product3->sku',
                'association' => 'variantProducts'
            ],
            [],
            ['HTTP_X-Product-ID' => 'sku']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'products', 'id' => '@configurable_product1_variant1->sku'],
                    ['type' => 'products', 'id' => '@configurable_product3_variant1->sku'],
                    ['type' => 'products', 'id' => '@configurable_product3_variant2->sku']
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForParentProducts()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'products',
                'id'          => '@configurable_product1_variant1->sku',
                'association' => 'parentProducts'
            ],
            [],
            ['HTTP_X-Product-ID' => 'sku']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'products',
                        'id'         => '@configurable_product1->sku',
                        'attributes' => [
                            'productId' => '@configurable_product1->id'
                        ]
                    ],
                    [
                        'type'       => 'products',
                        'id'         => '@configurable_product3->sku',
                        'attributes' => [
                            'productId' => '@configurable_product3->id'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForParentProducts()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'products',
                'id'          => '@configurable_product1_variant1->sku',
                'association' => 'parentProducts'
            ],
            [],
            ['HTTP_X-Product-ID' => 'sku']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'products', 'id' => '@configurable_product1->sku'],
                    ['type' => 'products', 'id' => '@configurable_product3->sku']
                ]
            ],
            $response
        );
    }
}
