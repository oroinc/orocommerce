<?php

namespace Functional\ApiFrontend\RestJsonApi;

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
        $responseContent['data'][2]['relationships']['status']['data'] = [
            'type' => 'orderstatuses',
            'id'   => 'open'
        ];
        $responseContent['data'][3]['relationships']['status']['data'] = [
            'type' => 'orderstatuses',
            'id'   => 'wait_for_approval'
        ];
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreate(): void
    {
        $response = $this->post(
            ['entity' => 'orders'],
            'create_order.yml'
        );

        $orderId = (int)$this->getResourceId($response);

        $responseContent = $this->getResponseData('create_order.yml');
        $responseContent['data']['relationships']['status']['data'] = [
            'type' => 'orderstatuses',
            'id'   => 'open'
        ];
        $responseContent = $this->updateResponseContent($responseContent, $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var Order $item */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertEquals('open', $order->getStatus()->getId());
    }
}
