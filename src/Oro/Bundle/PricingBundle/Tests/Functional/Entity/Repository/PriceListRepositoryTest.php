<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Migrations\Data\ORM\LoadPriceListData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRules;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;

/**
 * @dbIsolation
 */
class PriceListRepositoryTest extends WebTestCase
{
    /**
     * @var PriceList
     */
    protected $defaultPriceList;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadPriceRules::class,
            LoadProductPrices::class,
        ]);

        $this->defaultPriceList = $this->getDefaultPriceList();
    }

    protected function tearDown()
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
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_6);
        $currencies = $this->getRepository()->getInvalidCurrenciesByPriceList($priceList);

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

    /**
     * @return PriceListRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroPricingBundle:PriceList');
    }

    /**
     * @return EntityManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass('OroPricingBundle:PriceList');
    }
}
