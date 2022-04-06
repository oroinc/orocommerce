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
            ->getRepository(PriceAttributePriceList::class);
    }
}
