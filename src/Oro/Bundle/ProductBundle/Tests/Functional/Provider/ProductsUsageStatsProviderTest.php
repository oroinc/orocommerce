<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Provider;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductsUsageStatsProviderTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                LoadProductData::class,
            ]
        );
    }

    public function testGetProductsUsageStatsValue(): void
    {
        $provider = $this->getContainer()->get('oro_product.provider.products_usage_stats_provider');

        self::assertSame('9', $provider->getValue());
    }
}
