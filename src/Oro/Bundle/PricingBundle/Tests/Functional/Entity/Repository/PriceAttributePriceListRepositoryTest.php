<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributePriceLists;

/**
 * @dbIsolation
 */
class PriceAttributePriceListRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadPriceAttributePriceLists::class]);
    }

    public function testGetAttributesWithoutCurrencies()
    {
        $priceAttributesWithCurrencies = $this->getRepository()->getAttributesWithCurrencies([]);
        $this->assertCount(0, $priceAttributesWithCurrencies);
    }

    /**
     * @dataProvider currencyDataProvider
     * @param string[] $currencies
     * @param string[] $expectedPriceAttributes
     */
    public function testGetAttributesWithCurrencies($currencies, $expectedPriceAttributes)
    {
        $priceAttributesWithCurrencies = $this->getRepository()->getAttributesWithCurrencies($currencies);
        $this->assertCount(count($expectedPriceAttributes), $priceAttributesWithCurrencies);
        foreach ($priceAttributesWithCurrencies as $attribute) {
            $this->assertTrue($this->checkExistPair($attribute, $expectedPriceAttributes));
        }
    }

    /**
     * @return array
     */
    public function currencyDataProvider()
    {
        return [
            [
                'currencies' => ['USD'],
                'expectedPriceAttributes' => [
                    ['name' => 'priceAttributePriceList1', 'currency' => 'USD'],
                    ['name' => 'priceAttributePriceList2', 'currency' => 'USD'],
                    ['name' => 'priceAttributePriceList6', 'currency' => 'USD'],
                ],
            ],
            [
                'currencies' => ['USD', 'EUR'],
                'expectedPriceAttributes' => [
                    ['name' => 'priceAttributePriceList1', 'currency' => 'USD'],
                    ['name' => 'priceAttributePriceList2', 'currency' => 'USD'],
                    ['name' => 'priceAttributePriceList6', 'currency' => 'USD'],
                    ['name' => 'priceAttributePriceList5', 'currency' => 'EUR'],
                    ['name' => 'priceAttributePriceList1', 'currency' => 'EUR'],
                ],
            ],
        ];
    }

    public function testGetFieldNames()
    {
        $actual = $this->getRepository()->getFieldNames();

        $priceAttributePriceLists = $this->getContainer()->get('doctrine')
            ->getManagerForClass(PriceAttributePriceList::class)
            ->getRepository(PriceAttributePriceList::class)
            ->findAll();

        $expected = [];

        foreach ($priceAttributePriceLists as $priceAttributePriceList) {
            $item['id'] = $priceAttributePriceList->getId();
            $item['name'] = $priceAttributePriceList->getName();
            $item['fieldName'] = $priceAttributePriceList->getFieldName();

            array_push($expected, $item);
        }

        $this->assertEquals($expected, $actual);
    }

    /**
     * @param array $attributeCurrency
     * @param array $expectedAttributeCurrencies
     * @return bool
     */
    protected function checkExistPair($attributeCurrency, $expectedAttributeCurrencies)
    {
        foreach ($expectedAttributeCurrencies as $expectedAttributeCurrency) {
            if ($expectedAttributeCurrency['name'] === $attributeCurrency['name']
                && $expectedAttributeCurrency['currency'] === $attributeCurrency['currency']
            ) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * @return PriceAttributePriceListRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroPricingBundle:PriceAttributePriceList');
    }
}
