<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Provider;

use Oro\Bundle\PricingBundle\Provider\PriceListProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PriceListProviderTest extends WebTestCase
{
    private const DEFAULT_PRICE_LIST = 1;

    protected function setUp(): void
    {
        $this->initClient([]);
    }

    public function testGetDefaultPriceListId()
    {
        /** @var PriceListProvider $priceListProvider */
        $priceListProvider = $this->getContainer()->get('oro_pricing.provider.price_list_provider');
        $actualDefaultPriceList = $priceListProvider->getDefaultPriceListId();
        $this->assertEquals(self::DEFAULT_PRICE_LIST, $actualDefaultPriceList);
    }
}
