<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceListCurrency;

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
