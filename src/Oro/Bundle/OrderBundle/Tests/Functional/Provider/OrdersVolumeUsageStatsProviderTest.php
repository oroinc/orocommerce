<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Provider;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OrdersVolumeUsageStatsProviderTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->loadFixtures([LoadOrders::class]);

        $configManager = self::getConfigManager();
        $configManager->set('oro_multi_currency.allowed_currencies', ['USD']);
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->reset('oro_multi_currency.allowed_currencies');
        $configManager->flush();
    }

    public function testIsApplicableForSingleCurrency(): void
    {
        $provider = self::getContainer()->get('oro_order.provider.orders_volume_usage_stats_provider');

        self::assertTrue($provider->isApplicable());
    }

    public function testGetOrdersVolumeUsageStatsValue(): void
    {
        $provider = self::getContainer()->get('oro_order.provider.orders_volume_usage_stats_provider');

        self::assertSame('$7,404.00', $provider->getValue());
    }
}
