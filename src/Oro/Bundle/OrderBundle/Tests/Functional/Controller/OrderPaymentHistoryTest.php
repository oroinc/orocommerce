<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Controller;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadPaymentHistoryUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OrderPaymentHistoryTest extends WebTestCase
{
    private const PAYMENT_HISTORY_SECTION_NAME = 'Payments';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadOrders::class, LoadPaymentHistoryUserData::class]);
    }

    private function getOrder(): Order
    {
        return $this->getReference(LoadOrders::ORDER_1);
    }

    public function testOrderViewPageForUserWithPermission()
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(
                LoadPaymentHistoryUserData::USER_PAYMENT_HISTORY_VIEWER,
                LoadPaymentHistoryUserData::USER_PAYMENT_HISTORY_VIEWER
            )
        );

        $order = $this->getOrder();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_order_view', ['id' => $order->getId()])
        );

        self::assertStringContainsString(self::PAYMENT_HISTORY_SECTION_NAME, $crawler->html());
    }

    public function testOrderViewPagePaymentTransactionSectionNotVisible()
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(
                LoadPaymentHistoryUserData::USER_ORDER_VIEWER,
                LoadPaymentHistoryUserData::USER_ORDER_VIEWER
            )
        );

        $order = $this->getOrder();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_order_view', ['id' => $order->getId()])
        );

        self::assertStringNotContainsString(self::PAYMENT_HISTORY_SECTION_NAME, $crawler->html());
    }
}
