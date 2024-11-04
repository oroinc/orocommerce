<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderShippingStatuses;

class OrderShippingStatusTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadOrderShippingStatuses::class]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'ordershippingstatuses']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'ordershippingstatuses',
                        'id'         => 'not_shipped',
                        'attributes' => [
                            'name'     => 'Not Shipped',
                            'priority' => 1,
                            'default'  => true
                        ]
                    ],
                    [
                        'type'       => 'ordershippingstatuses',
                        'id'         => 'partially_shipped',
                        'attributes' => [
                            'name'     => 'Partially Shipped',
                            'priority' => 3,
                            'default'  => false
                        ]
                    ],
                    [
                        'type'       => 'ordershippingstatuses',
                        'id'         => 'shipped',
                        'attributes' => [
                            'name'     => 'Shipped',
                            'priority' => 2,
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
        $response = $this->cget(['entity' => 'ordershippingstatuses'], ['sort' => 'priority']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'ordershippingstatuses',
                        'id'         => 'not_shipped',
                        'attributes' => [
                            'name'     => 'Not Shipped',
                            'priority' => 1,
                            'default'  => true
                        ]
                    ],
                    [
                        'type'       => 'ordershippingstatuses',
                        'id'         => 'shipped',
                        'attributes' => [
                            'name'     => 'Shipped',
                            'priority' => 2,
                            'default'  => false
                        ]
                    ],
                    [
                        'type'       => 'ordershippingstatuses',
                        'id'         => 'partially_shipped',
                        'attributes' => [
                            'name'     => 'Partially Shipped',
                            'priority' => 3,
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
        $response = $this->get(['entity' => 'ordershippingstatuses', 'id' => 'not_shipped']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'ordershippingstatuses',
                    'id'         => 'not_shipped',
                    'attributes' => [
                        'name'     => 'Not Shipped',
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
            ['entity' => 'ordershippingstatuses', 'id' => 'new_status'],
            ['data' => ['type' => 'ordershippingstatuses', 'id' => 'new_status']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'ordershippingstatuses', 'id' => 'not_shipped'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'ordershippingstatuses'],
            ['filter[id]' => 'not_shipped'],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetOptionsForList(): void
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'ordershippingstatuses']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testOptionsForItem(): void
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'ordershippingstatuses', 'id' => 'not_shipped']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }
}
