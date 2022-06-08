<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadBuyerCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductForBuyerTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadBuyerCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product.yml',
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product_prices.yml'
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'products'],
            ['page[size]' => 100]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'products', 'id' => '<toString(@product1->id)>'],
                    ['type' => 'products', 'id' => '<toString(@product3->id)>'],
                    ['type' => 'products', 'id' => '<toString(@configurable_product1->id)>'],
                    ['type' => 'products', 'id' => '<toString(@configurable_product2->id)>'],
                    ['type' => 'products', 'id' => '<toString(@configurable_product3->id)>'],
                    ['type' => 'products', 'id' => '<toString(@configurable_product1_variant1->id)>'],
                    ['type' => 'products', 'id' => '<toString(@configurable_product1_variant2->id)>'],
                    ['type' => 'products', 'id' => '<toString(@configurable_product2_variant1->id)>'],
                    ['type' => 'products', 'id' => '<toString(@configurable_product2_variant2->id)>'],
                    ['type' => 'products', 'id' => '<toString(@configurable_product3_variant1->id)>'],
                    ['type' => 'products', 'id' => '<toString(@configurable_product3_variant2->id)>'],
                    ['type' => 'products', 'id' => '<toString(@product_kit1->id)>'],
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

        $this->assertResponseContains('get_product_for_buyer.yml', $response);
    }

    public function testTryToGetDisabled()
    {
        $response = $this->get(
            ['entity' => 'products', 'id' => '<toString(@product2->id)>'],
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

    public function testTryToUpdate()
    {
        $data = [
            'data' => [
                'type'       => 'products',
                'id'         => '<toString(@product1->id)>',
                'attributes' => [
                    'name' => 'Updated Product Name'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'products', 'id' => '<toString(@product1->id)>'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToCreate()
    {
        $data = [
            'data' => [
                'type'       => 'products',
                'attributes' => [
                    'name' => 'New Product'
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'products'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'products', 'id' => '<toString(@product1->id)>'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'products'],
            ['filter' => ['id' => '<toString(@product1->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
