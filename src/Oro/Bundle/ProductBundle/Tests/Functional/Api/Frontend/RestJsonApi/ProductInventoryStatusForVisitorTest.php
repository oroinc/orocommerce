<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;

class ProductInventoryStatusForVisitorTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableVisitor();
        $this->loadVisitor();
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'productinventorystatuses']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'productinventorystatuses',
                        'id'         => 'in_stock',
                        'attributes' => [
                            'name' => 'In Stock'
                        ]
                    ],
                    [
                        'type'       => 'productinventorystatuses',
                        'id'         => 'out_of_stock',
                        'attributes' => [
                            'name' => 'Out of Stock'
                        ]
                    ],
                    [
                        'type'       => 'productinventorystatuses',
                        'id'         => 'discontinued',
                        'attributes' => [
                            'name' => 'Discontinued'
                        ]
                    ]
                ]
            ],
            $response,
            true
        );
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'productinventorystatuses', 'id' => 'in_stock']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'productinventorystatuses',
                    'id'         => 'in_stock',
                    'attributes' => [
                        'name' => 'In Stock'
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdate()
    {
        $data = [
            'data' => [
                'type'       => 'productinventorystatuses',
                'id'         => 'in_stock',
                'attributes' => [
                    'name' => 'test'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'productinventorystatuses', 'id' => 'in_stock'],
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
                'type'       => 'productinventorystatuses',
                'attributes' => [
                    'name' => 'test'
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'productinventorystatuses'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'productinventorystatuses', 'id' => 'in_stock'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'productinventorystatuses'],
            ['filter' => ['id' => 'in_stock']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
