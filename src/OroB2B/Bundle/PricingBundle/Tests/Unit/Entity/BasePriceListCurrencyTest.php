<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\BasePriceListCurrency;

class BasePriceListCurrencyTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

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
     * @return BasePriceList
     */
    protected function createPriceList()
    {
        return new BasePriceList();
    }

    /**
     * @return BasePriceListCurrency
     */
    protected function createPriceListCurrency()
    {
        return new BasePriceListCurrency();
    }
}
