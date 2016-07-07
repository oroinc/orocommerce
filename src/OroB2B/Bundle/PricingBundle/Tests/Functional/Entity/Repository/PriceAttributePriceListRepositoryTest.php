<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributePriceLists;

/**
 * @dbIsolation
 */
class PriceAttributePriceListRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
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
        $this->assertEquals(
            [
                'priceAttributePriceList1' => 'price_attribute_price_list_1',
                'priceAttributePriceList2' => 'price_attribute_price_list_2',
                'priceAttributePriceList3' => 'price_attribute_price_list_3',
                'priceAttributePriceList4' => 'price_attribute_price_list_4',
                'priceAttributePriceList5' => 'price_attribute_price_list_5',
                'priceAttributePriceList6' => 'price_attribute_price_list_6'
            ],
            $actual
        );
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
            ->getRepository('OroB2BPricingBundle:PriceAttributePriceList');
    }
}
