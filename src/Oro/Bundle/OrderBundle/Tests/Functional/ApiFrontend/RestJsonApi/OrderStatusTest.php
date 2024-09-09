<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderStatuses;

class OrderStatusTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadOrderStatuses::class
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'orderstatuses'], ['filter[id]' => 'open,closed,cancelled']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orderstatuses',
                        'id'         => 'cancelled',
                        'attributes' => ['name' => 'Cancelled']
                    ],
                    [
                        'type'       => 'orderstatuses',
                        'id'         => 'closed',
                        'attributes' => ['name' => 'Closed']
                    ],
                    [
                        'type'       => 'orderstatuses',
                        'id'         => 'open',
                        'attributes' => ['name' => 'Open']
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(['entity' => 'orderstatuses', 'id' => 'closed']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orderstatuses',
                    'id'         => 'closed',
                    'attributes' => ['name' => 'Closed']
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

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'orderstatuses', 'id' => 'open'],
            [
                'data' => [
                    'type'       => 'orderstatuses',
                    'id'         => 'open',
                    'attributes' => ['name' => 'Open']
                ]
            ],
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
