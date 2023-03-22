<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Migrations\Data\ORM\LoadPriceListData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRules;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PriceListRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadPriceRules::class,
            LoadProductPrices::class,
            LoadPriceListRelations::class
        ]);
    }

    public function testGetCurrenciesIndexedByPriceListIds(): void
    {
        /** @var PriceList $priceList */
        $priceList = $this->getRepository()->findOneBy(['name' => LoadPriceListData::PRICE_LIST_NAME]);

        $expectedCurrencies = [
            $priceList->getId() => $priceList->getCurrencies(),
        ];

        foreach (LoadPriceLists::getPriceListData() as $priceListData) {
            $priceList = $this->getReference($priceListData['reference']);
            $expectedCurrencies[$priceList->getId()] = $priceList->getCurrencies();
        }

        $this->assertEquals($expectedCurrencies, $this->getRepository()->getCurrenciesIndexedByPricelistIds());
    }

    public function testGetPriceListsWithRules(): void
    {
        $priceListsIterator = $this->getRepository()->getPriceListsWithRules();
        $expectedPriceLists = [
            $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId(),
            $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId(),
            $this->getReference(LoadPriceLists::PRICE_LIST_4)->getId(),
            $this->getReference(LoadPriceLists::PRICE_LIST_5)->getId(),
        ];
        foreach ($priceListsIterator as $priceList) {
            $this->assertContains($priceList->getId(), $expectedPriceLists);
        }
    }

    public function testGetInvalidCurrenciesByPriceList(): void
    {
        $shardManager = $this->getContainer()->get('oro_pricing.shard_manager');
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_6);
        $currencies = $this->getRepository()->getInvalidCurrenciesByPriceList($shardManager, $priceList);

        $this->assertEquals(['EUR'], $currencies);
    }

    public function testUpdatePriceListsActuality(): void
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_6);
        $this->getRepository()->updatePriceListsActuality([$priceList], false);
        $priceList = $this->getRepository()->find($priceList->getId());
        $this->getManager()->refresh($priceList);
        $this->assertFalse($priceList->isActual());
    }

    public function testGetActivePriceListById(): void
    {
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $this->assertSame($priceList, $this->getRepository()->getActivePriceListById($priceList->getId()));
    }

    public function testGetActivePriceListByIdForInactive(): void
    {
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_6);
        $this->assertNull($this->getRepository()->getActivePriceListById($priceList->getId()));
    }

    public function testGetPriceListByCustomer(): void
    {
        $customer = $this->getReference('customer.level_1_1');
        $website = $this->getReference('US');

        $priceList = $this->getRepository()->getPriceListByCustomer($customer, $website);
        $expectedPriceList = $this->getReference('price_list_2');

        $this->assertSame($expectedPriceList->getId(), $priceList->getId());
    }

    public function testGetPriceListByCustomerGroup(): void
    {
        $customerGroup = $this->getReference('customer_group.group1');
        $website = $this->getReference('US');

        $priceList = $this->getRepository()->getPriceListByCustomerGroup($customerGroup, $website);
        $expectedPriceList = $this->getReference('price_list_5');

        $this->assertSame($expectedPriceList->getId(), $priceList->getId());
    }

    private function getRepository(): PriceListRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(PriceList::class);
    }

    private function getManager(): EntityManager
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(PriceList::class);
    }
}
