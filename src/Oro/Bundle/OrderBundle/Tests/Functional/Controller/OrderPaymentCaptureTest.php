<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Controller;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadChargeAuthorizedPaymentsPermissionUserData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadPaymentHistoryUserData;
use Oro\Bundle\PayPalBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OrderPaymentCaptureTest extends WebTestCase
{
    private const PAYMENT_HISTORY_SECTION_NAME = 'Payment History';

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadChargeAuthorizedPaymentsPermissionUserData::class,
            LoadPaymentTransactionData::class,
        ]);
    }

    public function testOrderViewPageForUserWithPermission()
    {
        $this->initClient(
            [],
            static::generateBasicAuthHeader(
                LoadChargeAuthorizedPaymentsPermissionUserData::USER_WITH_CHARGE_AUTHORIZED_PAYMENTS_PERMISSION,
                LoadChargeAuthorizedPaymentsPermissionUserData::USER_WITH_CHARGE_AUTHORIZED_PAYMENTS_PERMISSION
            )
        );

        $order = $this->getOrder();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_order_view', ['id' => $order->getId()])
        );

        static::assertStringContainsString(self::PAYMENT_HISTORY_SECTION_NAME, $crawler->html());

        $response = $this->client->requestGrid(
            [
                'gridName' => 'order-payment-transactions-grid',
                'order-payment-transactions-grid[order_id]' => $order->getId(),
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);

        $captureAction = $result['data'][0]['action_configuration']['oro_order_payment_transaction_capture'];
        $this->assertNotFalse($captureAction);
    }

    public function testOrderViewPagePaymentTransactionSectionNotVisible()
    {
        $this->initClient(
            [],
            static::generateBasicAuthHeader(
                LoadPaymentHistoryUserData::USER_PAYMENT_HISTORY_VIEWER,
                LoadPaymentHistoryUserData::USER_PAYMENT_HISTORY_VIEWER
            )
        );

        $order = $this->getOrder();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_order_view', ['id' => $order->getId()])
        );

        static::assertStringContainsString(self::PAYMENT_HISTORY_SECTION_NAME, $crawler->html());

        $response = $this->client->requestGrid(
            [
                'gridName' => 'order-payment-transactions-grid',
                'order-payment-transactions-grid[order_id]' => $order->getId(),
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);

        $captureAction = $result['data'][0]['action_configuration']['oro_order_payment_transaction_capture'];
        $this->assertFalse($captureAction);
    }

    /**
     * @return Order
     */
    private function getOrder()
    {
        return $this->getReference(LoadOrders::ORDER_1);
    }
}
