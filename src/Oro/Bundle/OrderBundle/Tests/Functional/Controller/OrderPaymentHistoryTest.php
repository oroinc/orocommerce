<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Controller;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadPaymentHistoryUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OrderPaymentHistoryTest extends WebTestCase
{
    /** @internal */
    const PAYMENT_HISTORY_SECTION_NAME = 'Payment History';

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadOrders::class,
            LoadPaymentHistoryUserData::class,
        ]);
    }

    public function testOrderViewPageForUserWithPermission()
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
    }

    public function testOrderViewPagePaymentTransactionSectionNotVisible()
    {
        $this->initClient(
            [],
            static::generateBasicAuthHeader(
                LoadPaymentHistoryUserData::USER_ORDER_VIEWER,
                LoadPaymentHistoryUserData::USER_ORDER_VIEWER
            )
        );

        $order = $this->getOrder();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_order_view', ['id' => $order->getId()])
        );

        static::assertStringNotContainsString(self::PAYMENT_HISTORY_SECTION_NAME, $crawler->html());
    }

    /**
     * @return Order
     */
    private function getOrder()
    {
        return $this->getReference(LoadOrders::ORDER_1);
    }
}
