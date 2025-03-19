<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Provider;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OrdersVolumeUsageStatsProviderTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                LoadOrders::class,
            ]
        );

        $configManager = $this->getConfigManager();
        $configManager->set('oro_multi_currency.allowed_currencies', ['USD']);
        $configManager->flush();
    }

    protected function tearDown(): void
    {
        $configManager = $this->getConfigManager();
        $configManager->reset('oro_multi_currency.allowed_currencies');
        $configManager->flush();
    }

    public function testIsApplicableForSingleCurrency(): void
    {
        $provider = $this->getContainer()->get('oro_order.provider.orders_volume_usage_stats_provider');

        self::assertTrue($provider->isApplicable());
    }

    public function testGetOrdersVolumeUsageStatsValue(): void
    {
        $provider = $this->getContainer()->get('oro_order.provider.orders_volume_usage_stats_provider');

        // Sum orders in USD and EUR (with conversation to USD).
        self::assertSame('$9,104.00', $provider->getValue());
    }
}
