<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CombinedPriceListRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceListsActivationRules',
            ]
        );
    }

    public function testGetPriceListRelations()
    {
        /** @var CombinedPriceList $priceList */
        $priceList = $this->getReference('2t_3f_1t');

        $relations = $this->getRepository()->getPriceListRelations($priceList);
        $this->assertNotEmpty($relations);
        $this->assertCount(3, $relations);

        $expected = [
            $this->getReference('price_list_2')->getId() => true,
            $this->getReference('price_list_3')->getId() => false,
            $this->getReference('price_list_1')->getId() => true,
        ];
        $actual = [];
        foreach ($relations as $relation) {
            $actual[$relation->getPriceList()->getId()] = $relation->isMergeAllowed();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAccountPriceList()
    {
        /** @var Account $account */
        $account = $this->getReference('account.level_1.2');

        /** @var CombinedPriceList $priceList */
        $priceList = $this->getReference('2t_3f_1t');

        /** @var Website $websiteUs */
        $websiteUs = $this->getReference(LoadWebsiteData::WEBSITE1);

        /** @var Website $websiteCa */
        $websiteCa = $this->getReference(LoadWebsiteData::WEBSITE3);

        $this->assertEquals(
            $priceList->getId(),
            $this->getRepository()->getPriceListByAccount($account, $websiteUs)->getId()
        );
        $this->assertNull($this->getRepository()->getPriceListByAccount($account, $websiteCa));
    }

    public function testAccountGroupPriceList()
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference('account_group.group1');

        /** @var CombinedPriceList $priceList */
        $priceList = $this->getReference('1t_2t_3t');

        /** @var Website $websiteUs */
        $websiteUs = $this->getReference(LoadWebsiteData::WEBSITE1);

        /** @var Website $websiteCa */
        $websiteCa = $this->getReference(LoadWebsiteData::WEBSITE2);

        $this->assertEquals(
            $priceList->getId(),
            $this->getRepository()->getPriceListByAccountGroup($accountGroup, $websiteUs)->getId()
        );
        $this->assertNull($this->getRepository()->getPriceListByAccountGroup($accountGroup, $websiteCa));
    }

    public function testWebsitePriceList()
    {
        /** @var CombinedPriceList $priceList */
        $priceList = $this->getReference('1t_2t_3t');

        /** @var Website $websiteUs */
        $websiteUs = $this->getReference(LoadWebsiteData::WEBSITE1);

        /** @var Website $websiteCa */
        $websiteCa = $this->getReference(LoadWebsiteData::WEBSITE3);

        $this->assertEquals(
            $priceList->getId(),
            $this->getRepository()->getPriceListByWebsite($websiteUs)->getId()
        );
        $this->assertNull($this->getRepository()->getPriceListByWebsite($websiteCa));
    }


    public function testDeleteUnusedPriceListsWithIgnore()
    {
        $combinedPriceList = new CombinedPriceList();
        $combinedPriceList->setEnabled(true);
        $combinedPriceList->setName('test_cpl');
        $this->getManager()->persist($combinedPriceList);
        $this->getManager()->flush();

        $combinedPriceList2 = new CombinedPriceList();
        $combinedPriceList2->setEnabled(true);
        $combinedPriceList2->setName('test_cpl2');
        $this->getManager()->persist($combinedPriceList2);
        $this->getManager()->flush();

        $combinedPriceListRepository = $this->getRepository();

        $priceLists = $combinedPriceListRepository->findBy(['name' => 'test_cpl']);
        $this->assertNotEmpty($priceLists);

        $combinedPriceListRepository->deleteUnusedPriceLists($priceLists, null);
        $priceLists = $combinedPriceListRepository->findBy(['name' => 'test_cpl']);
        $this->assertNotEmpty($priceLists);

        $priceLists = $combinedPriceListRepository->findBy(['name' => 'test_cpl2']);
        $this->assertEmpty($priceLists);
    }

    public function testDeleteUnusedPriceLists()
    {
        $combinedPriceList = new CombinedPriceList();
        $combinedPriceList->setEnabled(false);
        $combinedPriceList->setName('test_cpl2');
        $this->getManager()->persist($combinedPriceList);
        $this->getManager()->flush();

        $combinedPriceListRepository = $this->getRepository();

        $combinedPriceListRepository->deleteUnusedPriceLists([], false);

        $priceLists = $combinedPriceListRepository->findBy(['name' => 'test_cpl']);
        $this->assertNotEmpty($priceLists);

        $priceLists = $combinedPriceListRepository->findBy(['name' => 'test_cpl2']);
        $this->assertEmpty($priceLists);

    }

    public function testDeleteUnusedDisabledPriceLists()
    {
        $combinedPriceListRepository = $this->getRepository();
        $combinedPriceListRepository->deleteUnusedPriceLists();
        $priceLists = $combinedPriceListRepository->findBy(['name' => 'test_cpl']);
        $this->assertEmpty($priceLists);
    }

    /**
     * @dataProvider updateCombinedPriceListConnectionDataProvider
     * @param string $priceList
     * @param string $website
     * @param callable $getActual
     * @param string|null $targetEntity
     */
    public function testUpdateCombinedPriceListConnection(
        $priceList,
        $website,
        callable $getActual,
        $targetEntity = null
    ) {
        /** @var CombinedPriceList $priceList */
        $priceList = $this->getReference($priceList);
        /** @var Website $website */
        $website = $this->getReference($website);

        if ($targetEntity) {
            /** @var Account|AccountGroup $targetEntity */
            $targetEntity = $this->getReference($targetEntity);
        }

        $this->getRepository()->updateCombinedPriceListConnection($priceList, $priceList, $website, $targetEntity);
        /** @var BasePriceListRelation $actual */
        $actual = call_user_func($getActual, $website, $targetEntity);
        $this->assertEquals($priceList->getId(), $actual->getPriceList()->getId());
    }

    /**
     * @return array
     */
    public function updateCombinedPriceListConnectionDataProvider()
    {
        $getConnection = function ($relationEntityClass, Website $website, array $additionalCriteria = []) {
            return $this->getContainer()->get('doctrine')
                ->getManagerForClass($relationEntityClass)
                ->getRepository($relationEntityClass)
                ->findOneBy(array_merge(['website' => $website], $additionalCriteria));
        };

        $getAccountConnection = function (Website $website, Account $targetEntity) use ($getConnection) {
            return call_user_func(
                $getConnection,
                'OroB2BPricingBundle:CombinedPriceListToAccount',
                $website,
                ['account' => $targetEntity]
            );
        };

        $getAccountGroupConnection = function (Website $website, AccountGroup $targetEntity) use ($getConnection) {
            return call_user_func(
                $getConnection,
                'OroB2BPricingBundle:CombinedPriceListToAccountGroup',
                $website,
                ['accountGroup' => $targetEntity]
            );
        };

        $getWebsiteConnection = function (Website $website) use ($getConnection) {
            return call_user_func(
                $getConnection,
                'OroB2BPricingBundle:CombinedPriceListToWebsite',
                $website
            );
        };

        return [
            'not changed for account' => [
                '2t_3f_1t',
                LoadWebsiteData::WEBSITE1,
                $getAccountConnection,
                'account.level_1.2'
            ],
            'changed for account' => [
                '2f_1t_3t',
                LoadWebsiteData::WEBSITE1,
                $getAccountConnection,
                'account.level_1.2'
            ],
            'new for account' => [
                '2f_1t_3t',
                LoadWebsiteData::WEBSITE1,
                $getAccountConnection,
                'account.level_1'
            ],
            'not changed for account group' => [
                '1t_2t_3t',
                LoadWebsiteData::WEBSITE1,
                $getAccountGroupConnection,
                'account_group.group1'
            ],
            'changed for account group' => [
                '2f_1t_3t',
                LoadWebsiteData::WEBSITE1,
                $getAccountGroupConnection,
                'account_group.group1'
            ],
            'new for account group' => [
                '2f_1t_3t',
                LoadWebsiteData::WEBSITE2,
                $getAccountGroupConnection,
                'account_group.group1'
            ],
            'not changed for website' => [
                '1t_2t_3t',
                LoadWebsiteData::WEBSITE1,
                $getWebsiteConnection
            ],
            'changed for website' => [
                '2f_1t_3t',
                LoadWebsiteData::WEBSITE1,
                $getWebsiteConnection
            ],
            'new for website' => [
                '1t_2t_3t',
                LoadWebsiteData::WEBSITE2,
                $getWebsiteConnection
            ],
        ];
    }

    /**
     * @dataProvider cplByPriceListProductDataProvider
     * @param string $priceList
     * @param int $result
     * @param bool $calculatedPrices
     */
    public function testGetCombinedPriceListsByPriceListProduct($priceList, $result, $calculatedPrices)
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceList);

        $cPriceLists = $this->getRepository()->getCombinedPriceListsByPriceList($priceList, $calculatedPrices);
        $this->assertCount($result, $cPriceLists);
    }

    /**
     * @return array
     */
    public function cplByPriceListProductDataProvider()
    {
        return [
            [
                'priceList' => 'price_list_1',
                'result' => 4,
                'calculatedPrices' => null,
            ],
            [
                'priceList' => 'price_list_3',
                'result' => 0,
                'calculatedPrices' => false,
            ],
            [
                'priceList' => 'price_list_4',
                'result' => 0,
                'calculatedPrices' => true,
            ],
        ];
    }

    /**
     * @dataProvider getCPLsForPriceCollectByTimeOffsetDataProvider
     * @param $offsetHours
     * @param $result
     */
    public function testGetCPLsForPriceCollectByTimeOffset($offsetHours, $result)
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getReference('2f');
        $cpl->setPricesCalculated(false);
        $this->getManager()->flush();
        $cpl = $this->getReference('1f');
        $cpl->setPricesCalculated(false);
        $this->getManager()->flush();
        $cPriceLists = $this->getRepository()->getCPLsForPriceCollectByTimeOffset($offsetHours);
        $this->assertCount($result, $cPriceLists);
    }

    /**
     * @return array
     */
    public function getCPLsForPriceCollectByTimeOffsetDataProvider()
    {
        return [
            [
                'offsetHours' => 11,
                'result' => 1
            ],
            [
                'offsetHours' => 7 * 24,
                'result' => 2
            ]
        ];
    }

    /**
     * @return CombinedPriceListRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:CombinedPriceList');
    }

    /**
     * @return ObjectManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BPricingBundle:CombinedPriceList');
    }
}
