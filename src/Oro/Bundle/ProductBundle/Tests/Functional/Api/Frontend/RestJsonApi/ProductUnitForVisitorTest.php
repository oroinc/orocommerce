<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Tests\Functional\Api\DataFixtures\LoadProductUnits;

class ProductUnitForVisitorTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableVisitor();
        $this->loadFixtures([
            LoadProductUnits::class
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'productunits'],
            ['filter' => ['id' => 'piece,set']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'productunits',
                        'id'         => 'piece',
                        'attributes' => [
                            'defaultPrecision' => 0,
                            'label'            => 'piece',
                            'shortLabel'       => 'pc',
                            'pluralLabel'      => 'pieces',
                            'shortPluralLabel' => 'pcs'
                        ]
                    ],
                    [
                        'type'       => 'productunits',
                        'id'         => 'set',
                        'attributes' => [
                            'defaultPrecision' => 0,
                            'label'            => 'set',
                            'shortLabel'       => 'set',
                            'pluralLabel'      => 'sets',
                            'shortPluralLabel' => 'sets'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'productunits', 'id' => 'item']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'productunits',
                    'id'         => 'item',
                    'attributes' => [
                        'defaultPrecision' => 0,
                        'label'            => 'item',
                        'shortLabel'       => 'item',
                        'pluralLabel'      => 'items',
                        'shortPluralLabel' => 'items'
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
                'type'       => 'productunits',
                'id'         => 'item',
                'attributes' => [
                    'defaultPrecision' => 1
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'productunits', 'id' => 'item'],
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
                'type'       => 'productunits',
                'attributes' => [
                    'defaultPrecision' => 1
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'productunits'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'productunits', 'id' => 'item'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'productunits'],
            ['filter' => ['id' => 'item']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
