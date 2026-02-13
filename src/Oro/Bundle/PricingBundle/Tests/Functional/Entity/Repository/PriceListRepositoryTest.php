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
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
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

    public function testGetActivePriceListIdsByIds(): void
    {
        $priceList1 = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceList2 = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        $priceList6 = $this->getReference(LoadPriceLists::PRICE_LIST_6);

        // Test with mixed active and inactive price lists
        $priceListIds = [
            $priceList1->getId(),
            $priceList2->getId(),
            $priceList6->getId() // inactive
        ];

        $activePriceListIds = $this->getRepository()->getActivePriceListIdsByIds($priceListIds);

        // Should return only active price lists (1 and 2), not 6
        $this->assertCount(2, $activePriceListIds);
        $this->assertContains($priceList1->getId(), $activePriceListIds);
        $this->assertContains($priceList2->getId(), $activePriceListIds);
        $this->assertNotContains($priceList6->getId(), $activePriceListIds);
    }

    public function testGetActivePriceListIdsByIdsEmpty(): void
    {
        $activePriceListIds = $this->getRepository()->getActivePriceListIdsByIds([]);
        $this->assertEmpty($activePriceListIds);
    }

    public function testGetActivePriceListIdsByIdsOnlyInactive(): void
    {
        $priceList6 = $this->getReference(LoadPriceLists::PRICE_LIST_6);

        $activePriceListIds = $this->getRepository()->getActivePriceListIdsByIds([$priceList6->getId()]);

        $this->assertEmpty($activePriceListIds);
    }

    public function testGetActivePriceListIdsByIdsOnlyActive(): void
    {
        $priceList1 = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceList3 = $this->getReference(LoadPriceLists::PRICE_LIST_3);
        $priceList5 = $this->getReference(LoadPriceLists::PRICE_LIST_5);

        $priceListIds = [
            $priceList1->getId(),
            $priceList3->getId(),
            $priceList5->getId()
        ];

        $activePriceListIds = $this->getRepository()->getActivePriceListIdsByIds($priceListIds);

        $this->assertCount(3, $activePriceListIds);
        $this->assertEqualsCanonicalizing($priceListIds, $activePriceListIds);
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

    public function testGetPriceListsWithRulesByAssignedProducts()
    {
        $products = [
            $this->getReference(LoadProductData::PRODUCT_1)
        ];

        $priceLists = iterator_to_array($this->getRepository()->getPriceListsWithRulesByAssignedProducts($products));
        self::assertCount(2, $priceLists);
        self::assertEqualsCanonicalizing(
            [
                $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId(),
                $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId()
            ],
            array_map(fn (PriceList $pl) => $pl->getId(), $priceLists)
        );
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
