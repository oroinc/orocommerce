<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListCurrency;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;

class PriceListTest extends EntityTestCase
{
    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            $this->createPriceList(),
            [
                ['id', 42],
                ['name', 'new price list'],
                ['default', false]
            ]
        );
    }

    public function testCurrenciesCollection()
    {
        $priceList = $this->createPriceList();

        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $priceList->getCurrencies()
        );
        $this->assertCount(0, $priceList->getCurrencies());

        $currency = $this->createPriceListCurrency();

        $this->assertInstanceOf(
            'OroB2B\Bundle\PricingBundle\Entity\PriceList',
            $priceList->addCurrency($currency)
        );
        $this->assertCount(1, $priceList->getCurrencies());

        $priceList->addCurrency($currency);
        $this->assertCount(1, $priceList->getCurrencies());

        $priceList->removeCurrency($currency);
        $this->assertCount(0, $priceList->getCurrencies());
    }

    public function testPricesCollection()
    {
        $priceList = $this->createPriceList();

        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $priceList->getPrices()
        );
        $this->assertCount(0, $priceList->getPrices());

        $price = $this->createProductPrice();

        $this->assertInstanceOf(
            'OroB2B\Bundle\PricingBundle\Entity\PriceList',
            $priceList->addPrice($price)
        );
        $this->assertEquals([$price], $priceList->getPrices()->toArray());

        $priceList->addPrice($price);
        $this->assertEquals([$price], $priceList->getPrices()->toArray());

        $priceList->removePrice($price);
        $this->assertCount(0, $priceList->getPrices());
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

    /**
     * @return ProductPrice
     */
    protected function createProductPrice()
    {
        return new ProductPrice();
    }
}
