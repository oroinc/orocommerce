<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\DataFixtures\LoadCustomerUserRoles;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadCustomerData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;

class ProductImageForVisitorTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableVisitor();
        $this->loadFixtures([
            LoadCustomerData::class,
            LoadCustomerUserRoles::class,
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product.yml'
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'productimages'],
            ['fields[productimages]' => 'id']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'productimages', 'id' => '<toString(@product1_image1->id)>'],
                    ['type' => 'productimages', 'id' => '<toString(@product1_image2->id)>'],
                    ['type' => 'productimages', 'id' => '<toString(@product3_image1->id)>'],
                    ['type' => 'productimages', 'id' => '<toString(@product3_image2->id)>'],
                    ['type' => 'productimages', 'id' => '<toString(@product3_image3->id)>']
                ]
            ],
            $response
        );
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'productimages', 'id' => '<toString(@product1_image1->id)>']
        );

        $this->assertResponseContains('get_product_image.yml', $response);
    }

    public function testTryToUpdate()
    {
        $data = [
            'data' => [
                'type'       => 'productimages',
                'id'         => '<toString(@product1_image1->id)>',
                'attributes' => [
                    'originalImageName' => 'newFileName1.jpg'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'productimages', 'id' => '<toString(@product1_image1->id)>'],
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
                'type'       => 'productimages',
                'attributes' => [
                    'originalImageName' => 'someFileName1.jpg'
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'productimages'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'productimages', 'id' => '<toString(@product1_image1->id)>'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'productimages'],
            ['filter' => ['id' => '<toString(@product1_image1->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForProducts()
    {
        $response = $this->getSubresource(
            ['entity' => 'productimages', 'id' => '<toString(@product1_image1->id)>', 'association' => 'product']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'products',
                    'id'         => '<toString(@product1->id)>',
                    'attributes' => [
                        'sku' => 'PSKU1'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForProducts()
    {
        $response = $this->getRelationship(
            ['entity' => 'productimages', 'id' => '<toString(@product1_image1->id)>', 'association' => 'product']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'products',
                    'id' => '<toString(@product1->id)>'
                ]
            ],
            $response
        );
    }
}
