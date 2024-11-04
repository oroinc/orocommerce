<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderShippingStatuses;

class OrderShippingStatusTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadOrderShippingStatuses::class
        ]);
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
                        'attributes' => ['name' => 'Not Shipped']
                    ],
                    [
                        'type'       => 'ordershippingstatuses',
                        'id'         => 'partially_shipped',
                        'attributes' => ['name' => 'Partially Shipped']
                    ],
                    [
                        'type'       => 'ordershippingstatuses',
                        'id'         => 'shipped',
                        'attributes' => ['name' => 'Shipped']
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
                    'attributes' => ['name' => 'Not Shipped']
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

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'ordershippingstatuses', 'id' => 'not_shipped'],
            [
                'data' => [
                    'type'       => 'ordershippingstatuses',
                    'id'         => 'not_shipped',
                    'attributes' => ['name' => 'Not Shipped']
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
