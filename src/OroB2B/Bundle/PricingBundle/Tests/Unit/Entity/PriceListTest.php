<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

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

    /**
     * @dataProvider relationsDataProvider
     *
     * @param Customer|CustomerGroup|Website $entity
     */
    public function testRelations($entity)
    {
        $priceList = $this->createPriceList();

        $entityClassName = (new \ReflectionClass($entity))->getShortName();
        $getCollectionMethod = 'get' . $entityClassName . 's';
        $addMethod = 'add' . $entityClassName;
        $removeMethod = 'remove' . $entityClassName;

        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $priceList->$getCollectionMethod()
        );
        $this->assertCount(0, $priceList->$getCollectionMethod());

        $this->assertInstanceOf(
            'OroB2B\Bundle\PricingBundle\Entity\PriceList',
            $priceList->$addMethod($entity)
        );
        $this->assertCount(1, $priceList->$getCollectionMethod());

        $priceList->$addMethod($entity);
        $this->assertCount(1, $priceList->$getCollectionMethod());

        $priceList->$removeMethod($entity);
        $this->assertCount(0, $priceList->$getCollectionMethod());
    }

    /**
     * @return array
     */
    public function relationsDataProvider()
    {
        return [
            'customer' => [new Customer()],
            'customerGroup' => [new CustomerGroup()],
            'website' => [new Website()],
        ];
    }

    /**
     * @return PriceList
     */
    protected function createPriceList()
    {
        return new PriceList();
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
