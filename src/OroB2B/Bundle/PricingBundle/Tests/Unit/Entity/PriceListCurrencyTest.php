<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListCurrency;

class PriceListCurrencyTest extends EntityTestCase
{
    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            $this->createPriceListCurrency(),
            [
                ['id', 42],
                ['priceList', $this->createPriceList()],
                ['currency', 'USD']
            ]
        );
    }

    /**
     * @return PriceList
     */
    protected function createPriceList()
    {
        return new PriceList();
    }

    /**
     * @return PriceListCurrency
     */
    protected function createPriceListCurrency()
    {
        return new PriceListCurrency();
    }
}
