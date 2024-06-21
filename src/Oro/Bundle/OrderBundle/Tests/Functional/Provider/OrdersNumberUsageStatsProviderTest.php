<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Provider;

use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OrdersNumberUsageStatsProviderTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                LoadOrders::class,
            ]
        );
    }

    public function testGetOrdersNumberUsageStatsValue(): void
    {
        $provider = $this->getContainer()->get('oro_order.provider.orders_number_usage_stats_provider');

        self::assertSame('7', $provider->getValue());
    }
}
