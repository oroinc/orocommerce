<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ProductKitItemsTest extends FrontendRestJsonApiTestCase
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

    public function testGetProductKitItems(): void
    {
        $response = $this->cget(
            ['entity' => 'productkititems'],
            ['page[size]' => 100]
        );

        $this->assertResponseContains('cget_product_kit_item.yml', $response);
    }

    public function testGetListFilterByProduct(): void
    {
        $response = $this->cget(
            ['entity' => 'productkititems'],
            ['filter' => ['kitItemProducts.product' => ['@product3->id']]]
        );

        $this->assertResponseContains('cget_product_filter_by_product.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'productkititems', 'id' => '<toString(@product_kit1_item1->id)>']
        );

        $this->assertResponseContains('get_product_kit_item.yml', $response);
    }

    public function testGetForAnotherLocalization(): void
    {
        $response = $this->get(
            ['entity' => 'productkititems', 'id' => '<toString(@product_kit1_item1->id)>'],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'productkititems',
                    'id' => '<toString(@product_kit1_item1->id)>',
                    'attributes' => [
                        'label' => 'Product Kit 1 Item 1 ES',
                    ],
                ],
            ],
            $response
        );
    }

    public function testTryToUpdate(): void
    {
        $data = [
            'data' => [
                'type' => 'productkititems',
                'id' => '<toString(@product_kit1_item1->id)>',
                'attributes' => [
                    'label' => 'Updated Label',
                ],
            ],
        ];

        $response = $this->patch(
            ['entity' => 'productkititems', 'id' => '<toString(@product_kit1_item1->id)>'],
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
                'type' => 'productkititems',
                'attributes' => [
                    'label' => 'New Product Kit Item',
                ],
            ],
        ];

        $response = $this->post(
            ['entity' => 'productkititems'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'productkititems', 'id' => '<toString(@product_kit1_item1->id)>'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'productkititems'],
            ['filter' => ['id' => '<toString(@product_kit1_item1->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForProductKit(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'productkititems',
                'id' => '<toString(@product_kit1_item1->id)>',
                'association' => 'productKit',
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'products',
                    'id' => '<toString(@product_kit1->id)>',
                    'attributes' => [
                        'name' => 'Product Kit 1',
                        'createdAt' => '@product_kit1->createdAt->format("Y-m-d\TH:i:s\Z")',
                        'updatedAt' => '@product_kit1->updatedAt->format("Y-m-d\TH:i:s\Z")',
                    ],
                ],
            ],
            $response
        );
    }

    public function testGetRelationshipForProductKit(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'productkititems',
                'id' => '<toString(@product_kit1_item1->id)>',
                'association' => 'productKit',
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'products',
                    'id' => '<toString(@product_kit1->id)>',
                ],
            ],
            $response
        );
    }

    public function testTryToUpdateRelationshipForProductKit(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'productkititems',
                'id' => '<toString(@product_kit1_item1->id)>',
                'association' => 'productKit',
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForKitItemProducts(): void
    {
        $response = $this->getSubresource([
            'entity' => 'productkititems',
            'id' => '<toString(@product_kit1_item1->id)>',
            'association' => 'kitItemProducts',
        ]);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productkititemproducts',
                        'id' => '<toString(@product_kit1_item1_product1->id)>',
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                    'id' => '<toString(@product1->id)>',
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'productkititemproducts',
                        'id' => '<toString(@product_kit1_item1_product3->id)>',
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                    'id' => '<toString(@product3->id)>',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $response
        );
    }

    public function testGetRelationshipForKitItemProducts(): void
    {
        $response = $this->getRelationship([
            'entity' => 'productkititems',
            'id' => '<toString(@product_kit1_item1->id)>',
            'association' => 'kitItemProducts',
        ]);
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'productkititemproducts', 'id' => '<toString(@product_kit1_item1_product1->id)>'],
                    ['type' => 'productkititemproducts', 'id' => '<toString(@product_kit1_item1_product3->id)>'],
                ],
            ],
            $response
        );
    }

    public function testTryToUpdateRelationshipForKitItemProducts(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'productkititems',
                'id' => '<toString(@product_kit1_item1->id)>',
                'association' => 'kitItemProducts',
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddRelationshipForKitItemProducts(): void
    {
        $response = $this->postRelationship(
            [
                'entity' => 'productkititems',
                'id' => '<toString(@product_kit1_item1->id)>',
                'association' => 'kitItemProducts',
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteRelationshipForKitItemProducts(): void
    {
        $response = $this->deleteRelationship(
            [
                'entity' => 'productkititems',
                'id' => '<toString(@product_kit1_item1->id)>',
                'association' => 'kitItemProducts',
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForProductUnit(): void
    {
        $response = $this->getSubresource([
            'entity' => 'productkititems',
            'id' => '<toString(@product_kit1_item1->id)>',
            'association' => 'productUnit',
        ]);
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'productunits',
                    'id' => '<toString(@item->code)>',
                    'attributes' => [
                        'label' => 'item',
                    ],
                ],
            ],
            $response
        );
    }

    public function testGetRelationshipForProductUnit(): void
    {
        $response = $this->getRelationship([
            'entity' => 'productkititems',
            'id' => '<toString(@product_kit1_item1->id)>',
            'association' => 'productUnit',
        ]);
        $this->assertResponseContains(
            [
                'data' => ['type' => 'productunits', 'id' => '<toString(@item->code)>'],
            ],
            $response
        );
    }

    public function testTryToUpdateRelationshipForProductUnit(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'productkititems',
                'id' => '<toString(@product_kit1_item1->id)>',
                'association' => 'productUnit',
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddRelationshipForProductUnit(): void
    {
        $response = $this->postRelationship(
            [
                'entity' => 'productkititems',
                'id' => '<toString(@product_kit1_item1->id)>',
                'association' => 'productUnit',
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteRelationshipForProductUnit(): void
    {
        $response = $this->deleteRelationship(
            [
                'entity' => 'productkititems',
                'id' => '<toString(@product_kit1_item1->id)>',
                'association' => 'productUnit',
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
