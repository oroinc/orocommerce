<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ProductKitItemProductsTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // guard
        self::assertEquals(
            ['in_stock', 'out_of_stock'],
            self::getConfigManager()->get('oro_product.general_frontend_product_visibility')
        );

        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product.yml',
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product_prices.yml',
        ]);
    }

    public function testGetProductKitItemProducts(): void
    {
        $response = $this->cget(
            ['entity' => 'productkititemproducts'],
            ['page[size]' => 100]
        );

        $this->assertResponseContains('cget_product_kit_item_products.yml', $response);
    }

    public function testGetListFilterByProduct(): void
    {
        $response = $this->cget(
            ['entity' => 'productkititemproducts'],
            ['filter' => ['product' => ['@product3->id']]]
        );

        $this->assertResponseContains('cget_product_kit_item_product_filter_by_product.yml', $response);
    }

    public function testGetListFilterByKitItem(): void
    {
        $response = $this->cget(
            ['entity' => 'productkititemproducts'],
            ['filter' => ['kitItem' => ['@product_kit1_item1->id']]]
        );

        $this->assertResponseContains('cget_product_kit_item_product_filter_by_kit_item.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'productkititemproducts', 'id' => '<toString(@product_kit1_item1_product1->id)>']
        );

        $this->assertResponseContains('get_product_kit_item_product.yml', $response);
    }

    public function testTryToUpdate(): void
    {
        $data = [
            'data' => [
                'type' => 'productkititemproducts',
                'id' => '<toString(@product_kit1_item1_product1->id)>',
                'attributes' => [
                    'sortOrder' => 42,
                ],
            ],
        ];

        $response = $this->patch(
            ['entity' => 'productkititemproducts', 'id' => '<toString(@product_kit1_item1_product1->id)>'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToCreate(): void
    {
        $data = [
            'data' => [
                'type' => 'productkititemproducts',
                'attributes' => [
                    'sortOrder' => 1,
                ],
                'relationships' => [
                    'kitItem' => [
                        'data' => [
                            'type' => 'productkititems',
                            'id' => '<toString(@product_kit1_item1->id)>',
                        ],
                    ],
                    'product' => [
                        'data' => [
                            'type' => 'products',
                            'id' => '<toString(@product1->id)>',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->post(
            ['entity' => 'productkititemproducts'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'productkititemproducts', 'id' => '<toString(@product_kit1_item1_product1->id)>'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'productkititemproducts'],
            ['filter' => ['id' => '<toString(@product_kit1_item1_product1->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForProductKit(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'productkititemproducts',
                'id' => '<toString(@product_kit1_item1_product1->id)>',
                'association' => 'product',
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'products',
                    'id' => '<toString(@product1->id)>',
                ],
            ],
            $response
        );
    }

    public function testGetRelationshipForProductKit(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'productkititemproducts',
                'id' => '<toString(@product_kit1_item1_product1->id)>',
                'association' => 'product',
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'products',
                    'id' => '<toString(@product1->id)>',
                ],
            ],
            $response
        );
    }

    public function testTryToUpdateRelationshipForProduct(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'productkititemproducts',
                'id' => '<toString(@product_kit1_item1_product1->id)>',
                'association' => 'product',
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForKitItem(): void
    {
        $response = $this->getSubresource([
            'entity' => 'productkititemproducts',
            'id' => '<toString(@product_kit1_item1_product1->id)>',
            'association' => 'kitItem',
        ]);
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'productkititems',
                    'id' => '<toString(@product_kit1_item1->id)>',
                ],
            ],
            $response
        );
    }

    public function testGetRelationshipForKitItem(): void
    {
        $response = $this->getRelationship([
            'entity' => 'productkititemproducts',
            'id' => '<toString(@product_kit1_item1_product1->id)>',
            'association' => 'kitItem',
        ]);
        $this->assertResponseContains(
            [
                'data' => ['type' => 'productkititems', 'id' => '<toString(@product_kit1_item1->id)>'],
            ],
            $response
        );
    }

    public function testTryToUpdateRelationshipForKitItem(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'productkititemproducts',
                'id' => '<toString(@product_kit1_item1_product1->id)>',
                'association' => 'kitItem',
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddRelationshipForKitItem(): void
    {
        $response = $this->postRelationship(
            [
                'entity' => 'productkititemproducts',
                'id' => '<toString(@product_kit1_item1_product1->id)>',
                'association' => 'kitItem',
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteRelationshipForKitItem(): void
    {
        $response = $this->deleteRelationship(
            [
                'entity' => 'productkititemproducts',
                'id' => '<toString(@product_kit1_item1_product1->id)>',
                'association' => 'kitItem',
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
