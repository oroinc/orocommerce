<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributePriceLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PriceAttributePriceListRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadPriceAttributePriceLists::class]);
    }

    public function testGetAttributesWithoutCurrencies()
    {
        $qb = $this->getRepository()->getAttributesWithCurrenciesQueryBuilder([]);
        $priceAttributesWithCurrencies = $qb->getQuery()->getResult();
        $this->assertCount(0, $priceAttributesWithCurrencies);
    }

    /**
     * @dataProvider currencyDataProvider
     */
    public function testGetAttributesWithCurrencies(array $currencies, array $expectedPriceAttributes)
    {
        $qb = $this->getRepository()->getAttributesWithCurrenciesQueryBuilder($currencies);
        $priceAttributesWithCurrencies = $qb->getQuery()->getResult();
        $this->assertCount(count($expectedPriceAttributes), $priceAttributesWithCurrencies);
        foreach ($priceAttributesWithCurrencies as $attribute) {
            $this->assertTrue($this->checkExistPair($attribute, $expectedPriceAttributes));
        }
    }

    public function currencyDataProvider(): array
    {
        return [
            [
                'currencies' => ['USD'],
                'expectedPriceAttributes' => [
                    ['name' => 'Shipping Cost', 'currency' => 'USD'],
                    ['name' => 'priceAttributePriceList1', 'currency' => 'USD'],
                    ['name' => 'priceAttributePriceList2', 'currency' => 'USD'],
                    ['name' => 'priceAttributePriceList6', 'currency' => 'USD'],
                ],
            ]
        ];
    }

    public function testGetFieldNames()
    {
        $actual = $this->getRepository()->getFieldNames();

        /** @var PriceAttributePriceList[] $priceAttributePriceLists */
        $priceAttributePriceLists = $this->getContainer()->get('doctrine')
            ->getManagerForClass(PriceAttributePriceList::class)
            ->getRepository(PriceAttributePriceList::class)
            ->findAll();

        $expected = [];

        foreach ($priceAttributePriceLists as $priceAttributePriceList) {
            $expected[] = [
                'id' => $priceAttributePriceList->getId(),
                'name' => $priceAttributePriceList->getName(),
                'fieldName' => $priceAttributePriceList->getFieldName()
            ];
        }

        $this->assertEquals($expected, $actual);
    }

    private function checkExistPair(array $attributeCurrency, array $expectedAttributeCurrencies): bool
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

    private function getRepository(): PriceAttributePriceListRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(PriceAttributePriceList::class);
    }
}
