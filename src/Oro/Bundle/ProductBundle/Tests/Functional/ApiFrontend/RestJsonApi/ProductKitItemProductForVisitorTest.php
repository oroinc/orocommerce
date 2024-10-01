<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;

class ProductKitItemProductForVisitorTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();

        // guard
        self::assertEquals(
            ['prod_inventory_status.in_stock', 'prod_inventory_status.out_of_stock'],
            self::getConfigManager()->get('oro_product.general_frontend_product_visibility')
        );

        $this->loadFixtures([
            LoadCustomerData::class,
            '@OroProductBundle/Tests/Functional/ApiFrontend/DataFixtures/product.yml',
            '@OroProductBundle/Tests/Functional/ApiFrontend/DataFixtures/product_prices.yml',
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'productkititemproducts'],
            ['page[size]' => 100]
        );

        $this->assertResponseContains('cget_product_kit_item_products.yml', $response);
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
}
