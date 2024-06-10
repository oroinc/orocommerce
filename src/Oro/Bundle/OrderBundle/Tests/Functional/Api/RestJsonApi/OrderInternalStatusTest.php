<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class OrderInternalStatusTest extends RestJsonApiTestCase
{
    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'orderinternalstatuses']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orderinternalstatuses',
                        'id'         => 'archived',
                        'attributes' => [
                            'name'     => 'Archived',
                            'priority' => 5,
                            'default'  => false
                        ]
                    ],
                    [
                        'type'       => 'orderinternalstatuses',
                        'id'         => 'cancelled',
                        'attributes' => [
                            'name'     => 'Cancelled',
                            'priority' => 2,
                            'default'  => false
                        ]
                    ],
                    [
                        'type'       => 'orderinternalstatuses',
                        'id'         => 'closed',
                        'attributes' => [
                            'name'     => 'Closed',
                            'priority' => 4,
                            'default'  => false
                        ]
                    ],
                    [
                        'type'       => 'orderinternalstatuses',
                        'id'         => 'open',
                        'attributes' => [
                            'name'     => 'Open',
                            'priority' => 1,
                            'default'  => false
                        ]
                    ],
                    [
                        'type'       => 'orderinternalstatuses',
                        'id'         => 'shipped',
                        'attributes' => [
                            'name'     => 'Shipped',
                            'priority' => 3,
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
        $response = $this->cget(['entity' => 'orderinternalstatuses'], ['sort' => 'priority']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orderinternalstatuses',
                        'id'         => 'open',
                        'attributes' => [
                            'name'     => 'Open',
                            'priority' => 1,
                            'default'  => false
                        ]
                    ],
                    [
                        'type'       => 'orderinternalstatuses',
                        'id'         => 'cancelled',
                        'attributes' => [
                            'name'     => 'Cancelled',
                            'priority' => 2,
                            'default'  => false
                        ]
                    ],
                    [
                        'type'       => 'orderinternalstatuses',
                        'id'         => 'shipped',
                        'attributes' => [
                            'name'     => 'Shipped',
                            'priority' => 3,
                            'default'  => false
                        ]
                    ],
                    [
                        'type'       => 'orderinternalstatuses',
                        'id'         => 'closed',
                        'attributes' => [
                            'name'     => 'Closed',
                            'priority' => 4,
                            'default'  => false
                        ]
                    ],
                    [
                        'type'       => 'orderinternalstatuses',
                        'id'         => 'archived',
                        'attributes' => [
                            'name'     => 'Archived',
                            'priority' => 5,
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
        $response = $this->get(['entity' => 'orderinternalstatuses', 'id' => 'open']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orderinternalstatuses',
                    'id'         => 'open',
                    'attributes' => [
                        'name'     => 'Open',
                        'priority' => 1,
                        'default'  => false
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'orderinternalstatuses', 'id' => 'new_status'],
            ['data' => ['type' => 'orderinternalstatuses', 'id' => 'new_status']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'orderinternalstatuses', 'id' => 'open'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'orderinternalstatuses'],
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
            ['entity' => 'orderinternalstatuses']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testOptionsForItem(): void
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'orderinternalstatuses', 'id' => 'open']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }
}
