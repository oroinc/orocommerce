<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Provider;

use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OrdersNumberUsageStatsProviderTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->loadFixtures([LoadOrders::class]);
    }

    public function testGetOrdersNumberUsageStatsValue(): void
    {
        $provider = self::getContainer()->get('oro_order.provider.orders_number_usage_stats_provider');

        self::assertSame('7', $provider->getValue());
    }
}
