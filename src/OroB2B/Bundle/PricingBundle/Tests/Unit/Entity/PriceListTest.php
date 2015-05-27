<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;

class PriceListTest extends EntityTestCase
{
    public function testAccessors()
    {
        $now = new \DateTime('now');
        $this->assertPropertyAccessors(
            $this->createPriceList(),
            [
                ['id', 42],
                ['name', 'new price list'],
                ['default', false],
                ['createdAt', $now, false],
                ['updatedAt', $now, false]
            ]
        );
    }

    public function testCurrenciesCollection()
    {
        $priceList = $this->createPriceList();

        $this->assertInternalType('array', $priceList->getCurrencies());
        $this->assertCount(0, $priceList->getCurrencies());

        $this->assertInstanceOf(
            'OroB2B\Bundle\PricingBundle\Entity\PriceList',
            $priceList->setCurrencies(['EUR', 'USD'])
        );
        $this->assertCount(2, $priceList->getCurrencies());
        $this->assertEquals(['EUR', 'USD'], $priceList->getCurrencies());

        $this->assertInstanceOf(
            'OroB2B\Bundle\PricingBundle\Entity\PriceList',
            $priceList->setCurrencies(['EUR', 'PLN'])
        );
        $currentCurrencies = $priceList->getCurrencies();
        $this->assertCount(2, $currentCurrencies);
        $this->assertEquals(['EUR', 'PLN'], array_values($currentCurrencies));

        $this->assertTrue($priceList->hasCurrencyCode('EUR'));
        $this->assertFalse($priceList->hasCurrencyCode('USD'));

        $priceListCurrency = $priceList->getPriceListCurrencyByCode('EUR');
        $this->assertInstanceOf(
            'OroB2B\Bundle\PricingBundle\Entity\PriceListCurrency',
            $priceListCurrency
        );
        $this->assertEquals($priceList, $priceListCurrency->getPriceList());
        $this->assertEquals('EUR', $priceListCurrency->getCurrency());
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
     * @return ProductPrice
     */
    protected function createProductPrice()
    {
        return new ProductPrice();
    }

    public function testPrePersist()
    {
        $priceList = $this->createPriceList();
        $priceList->prePersist();
        $this->assertInstanceOf('\DateTime', $priceList->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $priceList->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $priceList = $this->createPriceList();
        $priceList->preUpdate();
        $this->assertInstanceOf('\DateTime', $priceList->getUpdatedAt());
    }
}
