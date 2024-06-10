<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadPaymentOrderStatuses;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadPaymentTransactions;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadShippingMethods;

/**
 * @dbIsolationPerTest
 */
class OrderWithExternalStatusManagementTest extends FrontendRestJsonApiTestCase
{
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/ApiFrontend/DataFixtures/orders.yml',
            LoadShippingMethods::class,
            LoadPaymentOrderStatuses::class,
            LoadPaymentTransactions::class,
            LoadPaymentTermData::class
        ]);
        self::getConfigManager()->set('oro_order.order_enable_external_status_management', true);
    }

    protected function tearDown(): void
    {
        self::getConfigManager()->set('oro_order.order_enable_external_status_management', false);
        parent::tearDown();
    }

    protected function postFixtureLoad(): void
    {
        parent::postFixtureLoad();
        self::getContainer()->get('oro_payment_term.provider.payment_term_association')
            ->setPaymentTerm($this->getReference('customer'), $this->getReference('payment_term_net_10'));
        $this->getEntityManager()->flush();
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'orders']);

        $responseContent = $this->getResponseData('cget_order.yml');
        $responseContent['data'][0]['relationships']['status']['data'] = [
            'type' => 'orderstatuses',
            'id'   => 'wait_for_approval'
        ];
        $responseContent['data'][1]['relationships']['status']['data'] = null;
        $responseContent['data'][2]['relationships']['status']['data'] = [
            'type' => 'orderstatuses',
            'id'   => 'open'
        ];
        $responseContent['data'][3]['relationships']['status']['data'] = null;
        $responseContent['data'][4]['relationships']['status']['data'] = null;
        $this->assertResponseContains($responseContent, $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>']
        );
        $responseContent = $this->getResponseData('get_order.yml');
        $responseContent['data']['relationships']['status']['data'] = [
            'type' => 'orderstatuses',
            'id'   => 'wait_for_approval'
        ];
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreate(): void
    {
        $data = $this->getRequestData('create_order.yml');
        $data['data']['relationships']['status']['data'] = [
            'type' => 'orderstatuses',
            'id'   => 'wait_for_approval'
        ];
        $response = $this->post(['entity' => 'orders'], $data);

        $orderId = (int)$this->getResourceId($response);

        $responseContent = $this->getResponseData('create_order.yml');
        $responseContent['data']['relationships']['status']['data'] = [
            'type' => 'orderstatuses',
            'id'   => 'wait_for_approval'
        ];
        $responseContent = $this->updateResponseContent($responseContent, $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var Order $item */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertEquals('wait_for_approval', $order->getStatus()->getId());
    }

    public function testGetSubresourceForStatus(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'status']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderstatuses', 'id' => 'wait_for_approval']],
            $response
        );
    }

    public function testGetRelationshipForStatus(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'status']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orderstatuses', 'id' => 'wait_for_approval']],
            $response
        );
    }

    public function testTryToUpdateRelationshipForStatus(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'status'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
