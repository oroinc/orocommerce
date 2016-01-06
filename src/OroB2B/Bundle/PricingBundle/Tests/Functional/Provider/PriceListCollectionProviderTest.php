<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Provider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class PriceListCollectionProviderTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([]);

        $this->loadFixtures(
            [
            ]
        );
    }

    public function testGetPriceListsByConfig()
    {
        $provider = $this->getContainer()->get('orob2b_pricing.provider.price_list_collection');
        $pricesChain = $provider->getPriceListsByConfig();
        $this->assertCount(1, $pricesChain);
        $this->assertTrue($pricesChain[0]->isAllowMerge());
        $this->assertTrue($pricesChain[0]->getPriceList()->isDefault());
    }


}

