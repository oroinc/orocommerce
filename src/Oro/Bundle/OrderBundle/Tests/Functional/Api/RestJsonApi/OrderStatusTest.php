<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderStatuses;

class OrderStatusTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadOrderStatuses::class]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'orderstatuses']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orderstatuses',
                        'id'         => 'cancelled',
                        'attributes' => [
                            'name'     => 'Cancelled',
                            'priority' => 2,
                            'default'  => false
                        ]
                    ],
                    [
                        'type'       => 'orderstatuses',
                        'id'         => 'closed',
                        'attributes' => [
                            'name'     => 'Closed',
                            'priority' => 3,
                            'default'  => false
                        ]
                    ],
                    [
                        'type'       => 'orderstatuses',
                        'id'         => 'open',
                        'attributes' => [
                            'name'     => 'Open',
                            'priority' => 1,
                            'default'  => true
                        ]
                    ],
                    [
                        'type'       => 'orderstatuses',
                        'id'         => 'wait_for_approval',
                        'attributes' => [
                            'name'     => 'Wait For Approval',
                            'priority' => 4,
                            'default'  => false
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListSortedByPriority(): void
    {
        $response = $this->cget(['entity' => 'orderstatuses'], ['sort' => 'priority']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orderstatuses',
                        'id'         => 'open',
                        'attributes' => [
                            'name'     => 'Open',
                            'priority' => 1,
                            'default'  => true
                        ]
                    ],
                    [
                        'type'       => 'orderstatuses',
                        'id'         => 'cancelled',
                        'attributes' => [
                            'name'     => 'Cancelled',
                            'priority' => 2,
                            'default'  => false
                        ]
                    ],
                    [
                        'type'       => 'orderstatuses',
                        'id'         => 'closed',
                        'attributes' => [
                            'name'     => 'Closed',
                            'priority' => 3,
                            'default'  => false
                        ]
                    ],
                    [
                        'type'       => 'orderstatuses',
                        'id'         => 'wait_for_approval',
                        'attributes' => [
                            'name'     => 'Wait For Approval',
                            'priority' => 4,
                            'default'  => false
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(['entity' => 'orderstatuses', 'id' => 'open']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orderstatuses',
                    'id'         => 'open',
                    'attributes' => [
                        'name'     => 'Open',
                        'priority' => 1,
                        'default'  => true
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'orderstatuses', 'id' => 'new_status'],
            ['data' => ['type' => 'orderstatuses', 'id' => 'new_status']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'orderstatuses', 'id' => 'open'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'orderstatuses'],
            ['filter[id]' => 'open'],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetOptionsForList(): void
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'orderstatuses']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testOptionsForItem(): void
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'orderstatuses', 'id' => 'open']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }
}
