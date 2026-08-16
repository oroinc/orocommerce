<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadBuyerCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;

class BrandForBuyerTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadBuyerCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/ApiFrontend/DataFixtures/brand.yml'
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'brands']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'brands',
                        'id' => '<toString(@brand1->id)>',
                        'attributes' => [
                            'name' => 'Brand 1',
                            'shortDescription' => 'Brand 1 Short Description',
                            'description' => 'Brand 1 Description',
                            'status' => 'enabled'
                        ]
                    ]
                ]
            ],
            $response,
            true
        );
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'brands', 'id' => '<toString(@brand1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'brands',
                    'id' => '<toString(@brand1->id)>',
                    'attributes' => [
                        'name' => 'Brand 1',
                        'shortDescription' => 'Brand 1 Short Description',
                        'description' => 'Brand 1 Description',
                        'status' => 'enabled'
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdate()
    {
        $response = $this->patch(
            ['entity' => 'brands', 'id' => '<toString(@brand1->id)>'],
            [
                'data' => [
                    'type' => 'brands',
                    'id' => '<toString(@brand1->id)>',
                    'attributes' => [
                        'name' => 'Updated Brand 1'
                    ]
                ]
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToCreate()
    {
        $response = $this->post(
            ['entity' => 'brands'],
            [
                'data' => [
                    'type' => 'brands',
                    'attributes' => [
                        'name' => 'New Brand'
                    ]
                ]
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'brands', 'id' => '<toString(@brand1->id)>'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'brands'],
            ['filter' => ['id' => '<toString(@brand1->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
