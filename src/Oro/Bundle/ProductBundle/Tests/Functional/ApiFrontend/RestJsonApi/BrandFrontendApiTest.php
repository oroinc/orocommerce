<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;

final class BrandFrontendApiTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
        $this->loadFixtures([
            '@OroProductBundle/Tests/Functional/ApiFrontend/DataFixtures/brand.yml'
        ]);
    }

    public function testGetBrand(): void
    {
        $response = $this->get(
            ['entity' => 'brands', 'id' => '<toString(@brand1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'brands',
                    'id'         => '<toString(@brand1->id)>',
                    'attributes' => [
                        'name'             => 'Brand 1',
                        'shortDescription' => 'Brand 1 Short Description',
                        'description'      => 'Brand 1 Description',
                        'status'           => 'enabled'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListBrands(): void
    {
        $response = $this->cget(
            ['entity' => 'brands']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'brands',
                        'id'         => '<toString(@brand1->id)>',
                        'attributes' => [
                            'name'             => 'Brand 1',
                            'shortDescription' => 'Brand 1 Short Description',
                            'description'      => 'Brand 1 Description',
                            'status'           => 'enabled'
                        ]
                    ]
                ]
            ],
            $response,
            true
        );
    }
}
