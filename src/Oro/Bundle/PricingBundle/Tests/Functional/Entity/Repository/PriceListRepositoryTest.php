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
    /**
     * @var PriceList
     */
    protected $defaultPriceList;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadPriceRules::class,
            LoadProductPrices::class,
            LoadPriceListRelations::class
        ]);

        $this->defaultPriceList = $this->getDefaultPriceList();
    }

    protected function tearDown(): void
    {
        $this->getRepository()->setDefault($this->defaultPriceList);
        parent::tearDown();
    }

    public function testDefaultState()
    {
        $repository = $this->getRepository();

        /** @var PriceList $priceList1 */
        $priceList1 = $this->getReference('price_list_1');
        $repository->setDefault($priceList1);
        $this->assertEquals($priceList1->getId(), $this->getDefaultPriceList()->getId());

        /** @var PriceList $priceList2 */
        $priceList2 = $this->getReference('price_list_2');
        $repository->setDefault($priceList2);
        $this->assertEquals($priceList2->getId(), $this->getDefaultPriceList()->getId());
    }

    public function testGetDefault()
    {
        $this->assertEquals($this->getDefaultPriceList()->getId(), $this->getRepository()->getDefault()->getId());
    }

    public function testGetCurrenciesIndexedByPriceListIds()
    {
        /** @var PriceList $defaultPriceList */
        $defaultPriceList = $this->getRepository()->findOneBy(['name' => LoadPriceListData::DEFAULT_PRICE_LIST_NAME]);

        $expectedCurrencies = [
            $defaultPriceList->getId() => $defaultPriceList->getCurrencies(),
        ];

        foreach (LoadPriceLists::getPriceListData() as $priceListData) {
            $priceList = $this->getReference($priceListData['reference']);
            $expectedCurrencies[$priceList->getId()] = $priceList->getCurrencies();
        }

        $this->assertEquals($expectedCurrencies, $this->getRepository()->getCurrenciesIndexedByPricelistIds());
    }

    /**
     * @return PriceList
     */
    public function getDefaultPriceList()
    {
        $defaultPriceLists = $this->getRepository()->findBy(['default' => true]);

        $this->assertCount(1, $defaultPriceLists);

        return reset($defaultPriceLists);
    }

    public function testGetPriceListsWithRules()
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

    public function testGetInvalidCurrenciesByPriceList()
    {
        $shardManager = $this->getContainer()->get('oro_pricing.shard_manager');
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_6);
        $currencies = $this->getRepository()->getInvalidCurrenciesByPriceList($shardManager, $priceList);

        $this->assertEquals(['EUR'], $currencies);
    }

    public function testUpdatePriceListsActuality()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_6);
        $this->getRepository()->updatePriceListsActuality([$priceList], false);
        $priceList = $this->getRepository()->find($priceList->getId());
        $this->getManager()->refresh($priceList);
        $this->assertFalse($priceList->isActual());
    }

    public function testGetActivePriceListById()
    {
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $this->assertSame($priceList, $this->getRepository()->getActivePriceListById($priceList->getId()));
    }

    public function testGetActivePriceListByIdForInactive()
    {
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_6);
        $this->assertNull($this->getRepository()->getActivePriceListById($priceList->getId()));
    }

    public function testGetPriceListByCustomer()
    {
        $customer = $this->getReference('customer.level_1_1');
        $website = $this->getReference('US');

        $priceList = $this->getRepository()->getPriceListByCustomer($customer, $website);
        $expectedPriceList = $this->getReference('price_list_2');

        $this->assertSame($expectedPriceList->getId(), $priceList->getId());
    }

    public function testGetPriceListByCustomerGroup()
    {
        $customerGroup = $this->getReference('customer_group.group1');
        $website = $this->getReference('US');

        $priceList = $this->getRepository()->getPriceListByCustomerGroup($customerGroup, $website);
        $expectedPriceList = $this->getReference('price_list_5');

        $this->assertSame($expectedPriceList->getId(), $priceList->getId());
    }

    /**
     * @return PriceListRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(PriceList::class);
    }

    /**
     * @return EntityManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(PriceList::class);
    }
}
