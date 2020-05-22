<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListSchedules;

/**
 * @dbIsolationPerTest
 */
class PriceListScheduleUpdateListTest extends RestJsonApiUpdateListTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadPriceListSchedules::class]);
    }

    public function testCreateEntities()
    {
        $data = [
            'data' => [
                [
                    'type'          => 'pricelistschedules',
                    'attributes'    => [
                        'activeAt'     => '2017-04-12T14:11:39Z',
                        'deactivateAt' => '2017-04-24T14:11:39Z'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => ['type' => 'pricelists', 'id' => '<toString(@price_list_1->id)>']
                        ]
                    ]
                ],
                [
                    'type'          => 'pricelistschedules',
                    'attributes'    => [
                        'activeAt'     => '2017-03-12T14:11:39Z',
                        'deactivateAt' => '2017-03-24T14:11:39Z'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => ['type' => 'pricelists', 'id' => '<toString(@price_list_1->id)>']
                        ]
                    ]
                ]
            ]
        ];
        $this->processUpdateList(PriceListSchedule::class, $data);

        $response = $this->cget(['entity' => 'pricelistschedules'], ['filter[id][gt]' => '@schedule.6->id']);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type'          => 'pricelistschedules',
                        'id'            => 'new',
                        'attributes'    => [
                            'activeAt'     => '2017-04-12T14:11:39Z',
                            'deactivateAt' => '2017-04-24T14:11:39Z'
                        ],
                        'relationships' => [
                            'priceList' => [
                                'data' => ['type' => 'pricelists', 'id' => '<toString(@price_list_1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'          => 'pricelistschedules',
                        'id'            => 'new',
                        'attributes'    => [
                            'activeAt'     => '2017-03-12T14:11:39Z',
                            'deactivateAt' => '2017-03-24T14:11:39Z'
                        ],
                        'relationships' => [
                            'priceList' => [
                                'data' => ['type' => 'pricelists', 'id' => '<toString(@price_list_1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);
    }

    public function testUpdateEntities()
    {
        $priceListSchedule1Id = $this->getReference('schedule.5')->getId();
        $priceListSchedule2Id = $this->getReference('schedule.6')->getId();

        $data = [
            'data' => [
                [
                    'meta'          => ['update' => true],
                    'type'          => 'pricelistschedules',
                    'id'            => (string)$priceListSchedule1Id,
                    'attributes'    => [
                        'activeAt' => '2012-04-12T14:11:39Z'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => ['type' => 'pricelists', 'id' => '<toString(@price_list_1->id)>']
                        ]
                    ]
                ],
                [
                    'meta'       => ['update' => true],
                    'type'       => 'pricelistschedules',
                    'id'         => (string)$priceListSchedule2Id,
                    'attributes' => [
                        'activeAt' => '2013-03-12T14:11:39Z'
                    ]
                ]
            ],
        ];
        $this->processUpdateList(PriceListSchedule::class, $data);

        $response = $this->cget(
            ['entity' => 'pricelistschedules'],
            ['filter' => ['id' => [(string)$priceListSchedule1Id, (string)$priceListSchedule2Id]]]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'pricelistschedules',
                        'id'            => (string)$priceListSchedule1Id,
                        'attributes'    => [
                            'activeAt' => '2012-04-12T14:11:39Z'
                        ],
                        'relationships' => [
                            // price list should not be changed as it is mapped as false for update action
                            'priceList' => [
                                'data' => ['type' => 'pricelists', 'id' => '<toString(@price_list_3->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'       => 'pricelistschedules',
                        'id'         => (string)$priceListSchedule2Id,
                        'attributes' => [
                            'activeAt' => '2013-03-12T14:11:39Z'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreateAndUpdateEntities()
    {
        $updatedPriceListScheduleId = $this->getReference('schedule.6')->getId();

        $data = [
            'data' => [
                [
                    'meta'       => ['update' => true],
                    'type'       => 'pricelistschedules',
                    'id'         => (string)$updatedPriceListScheduleId,
                    'attributes' => [
                        'activeAt' => '2012-04-12T14:11:39Z'
                    ]
                ],
                [
                    'type'          => 'pricelistschedules',
                    'attributes'    => [
                        'activeAt'     => '2017-03-12T14:11:39Z',
                        'deactivateAt' => '2017-03-24T14:11:39Z'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => ['type' => 'pricelists', 'id' => '<toString(@price_list_1->id)>']
                        ]
                    ]
                ]
            ]
        ];
        $this->processUpdateList(PriceListSchedule::class, $data);

        $response = $this->cget(['entity' => 'pricelistschedules'], ['filter[id][gte]' => '@schedule.6->id']);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type'       => 'pricelistschedules',
                        'id'         => (string)$updatedPriceListScheduleId,
                        'attributes' => [
                            'activeAt' => '2012-04-12T14:11:39Z'
                        ]
                    ],
                    [
                        'type'          => 'pricelistschedules',
                        'id'            => 'new',
                        'attributes'    => [
                            'activeAt'     => '2017-03-12T14:11:39Z',
                            'deactivateAt' => '2017-03-24T14:11:39Z'
                        ],
                        'relationships' => [
                            'priceList' => [
                                'data' => ['type' => 'pricelists', 'id' => '<toString(@price_list_1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateEntitiesWithIncludes()
    {
        $data = [
            'data'     => [
                [
                    'type'          => 'pricelistschedules',
                    'attributes'    => [
                        'activeAt'     => '2017-04-12T14:11:39Z',
                        'deactivateAt' => '2017-04-24T14:11:39Z'
                    ],
                    'relationships' => [
                        'priceList' => ['data' => ['type' => 'pricelists', 'id' => 'price1']]
                    ]
                ],
                [
                    'type'          => 'pricelistschedules',
                    'attributes'    => [
                        'activeAt'     => '2018-04-12T14:11:39Z',
                        'deactivateAt' => '2018-04-24T14:11:39Z'
                    ],
                    'relationships' => [
                        'priceList' => ['data' => ['type' => 'pricelists', 'id' => 'price2']]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => 'pricelists',
                    'id'         => 'price1',
                    'attributes' => [
                        'name'                  => 'New Price List 1',
                        'priceListCurrencies'   => ['USD'],
                        'productAssignmentRule' => 'product.category.id == 1',
                        'active'                => true
                    ]
                ],
                [
                    'type'       => 'pricelists',
                    'id'         => 'price2',
                    'attributes' => [
                        'name'                  => 'New Price List 2',
                        'priceListCurrencies'   => ['USD'],
                        'productAssignmentRule' => 'product.category.id == 1',
                        'active'                => false
                    ]
                ]
            ]
        ];
        $this->processUpdateList(PriceListSchedule::class, $data);

        $response = $this->cget(['entity' => 'pricelistschedules'], ['filter[id][gt]' => '@schedule.6->id']);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type'          => 'pricelistschedules',
                        'id'            => 'new',
                        'attributes'    => [
                            'activeAt'     => '2017-04-12T14:11:39Z',
                            'deactivateAt' => '2017-04-24T14:11:39Z'
                        ],
                        'relationships' => [
                            'priceList' => ['data' => ['type' => 'pricelists', 'id' => 'new']]
                        ]
                    ],
                    [
                        'type'          => 'pricelistschedules',
                        'id'            => 'new',
                        'attributes'    => [
                            'activeAt'     => '2018-04-12T14:11:39Z',
                            'deactivateAt' => '2018-04-24T14:11:39Z'
                        ],
                        'relationships' => [
                            'priceList' => ['data' => ['type' => 'pricelists', 'id' => 'new']]
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);
    }
}
