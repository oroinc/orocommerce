<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;

class ProductKitItemForVisitorTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();

        // guard
        self::assertEquals(
            ['in_stock', 'out_of_stock'],
            self::getConfigManager()->get('oro_product.general_frontend_product_visibility')
        );

        $this->loadFixtures([
            LoadCustomerData::class,
            '@OroProductBundle/Tests/Functional/ApiFrontend/DataFixtures/product.yml',
            '@OroProductBundle/Tests/Functional/ApiFrontend/DataFixtures/product_prices.yml'
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'productkititems'],
            ['page[size]' => 100]
        );

        $this->assertResponseContains('cget_product_kit_item.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'productkititems', 'id' => '<toString(@product_kit1_item1->id)>']
        );

        $this->assertResponseContains('get_product_kit_item.yml', $response);
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'productkititems', 'id' => '<toString(@product_kit1_item1->id)>'],
            [
                'data' => [
                    'type' => 'productkititems',
                    'id' => '<toString(@product_kit1_item1->id)>',
                    'attributes' => [
                        'label' => 'Updated Label'
                    ]
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'productkititems'],
            [
                'data' => [
                    'type' => 'productkititems',
                    'attributes' => [
                        'label' => 'New Product Kit Item'
                    ]
                ]
            ],
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
}
