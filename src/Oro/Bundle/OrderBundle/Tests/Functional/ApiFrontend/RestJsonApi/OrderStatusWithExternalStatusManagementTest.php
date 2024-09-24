<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderStatuses;

/**
 * @dbIsolationPerTest
 */
class OrderStatusWithExternalStatusManagementTest extends FrontendRestJsonApiTestCase
{
    use ConfigManagerAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadOrderStatuses::class
        ]);
        self::getConfigManager()->set('oro_order.order_enable_external_status_management', true);
    }

    #[\Override]
    protected function tearDown(): void
    {
        self::getConfigManager()->set('oro_order.order_enable_external_status_management', false);
        parent::tearDown();
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
                    ],
                    [
                        'type'       => 'orderstatuses',
                        'id'         => 'wait_for_approval',
                        'attributes' => ['name' => 'Wait For Approval']
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(['entity' => 'orderstatuses', 'id' => 'wait_for_approval']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orderstatuses',
                    'id'         => 'wait_for_approval',
                    'attributes' => ['name' => 'Wait For Approval']
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
