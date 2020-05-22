<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Provider;

use Oro\Bundle\PricingBundle\Provider\PriceListProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PriceListProviderTest extends WebTestCase
{
    const DEFAULT_PRICE_LIST = 1;
    /**
     * @var PriceListProvider
     */
    protected $priceListProvider;

    protected function setUp(): void
    {
        $this->initClient([]);
        $this->priceListProvider = $this->getContainer()->get('oro_pricing.provider.price_list_provider');
        $this->priceListProvider;
    }

    public function testGetDefaultPriceListId()
    {
        $actualDefaultPriceList = $this->priceListProvider->getDefaultPriceListId();
        $this->assertEquals(self::DEFAULT_PRICE_LIST, $actualDefaultPriceList);
    }
}
