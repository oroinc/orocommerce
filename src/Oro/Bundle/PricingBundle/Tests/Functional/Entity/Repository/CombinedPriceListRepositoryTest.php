<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceListsActivationRulesForRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @dbIsolationPerTest
 */
class CombinedPriceListRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadCombinedPriceListsActivationRulesForRepository::class]);
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

    public function testCustomerPriceList()
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer.level_1.2');

        /** @var CombinedPriceList $priceList */
        $priceList = $this->getReference('2t_3f_1t');

        /** @var Website $websiteUs */
        $websiteUs = $this->getReference(LoadWebsiteData::WEBSITE1);

        /** @var Website $websiteCa */
        $websiteCa = $this->getReference(LoadWebsiteData::WEBSITE3);

        $this->assertEquals(
            $priceList->getId(),
            $this->getRepository()->getPriceListByCustomer($customer, $websiteUs)->getId()
        );
        $this->assertNull($this->getRepository()->getPriceListByCustomer($customer, $websiteCa));
    }

    public function testCustomerGroupPriceList()
    {
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference('customer_group.group1');

        /** @var CombinedPriceList $priceList */
        $priceList = $this->getReference('1t_2t_3t');

        /** @var Website $websiteUs */
        $websiteUs = $this->getReference(LoadWebsiteData::WEBSITE1);

        /** @var Website $websiteCa */
        $websiteCa = $this->getReference(LoadWebsiteData::WEBSITE2);

        $this->assertEquals(
            $priceList->getId(),
            $this->getRepository()->getPriceListByCustomerGroup($customerGroup, $websiteUs)->getId()
        );
        $this->assertNull($this->getRepository()->getPriceListByCustomerGroup($customerGroup, $websiteCa));
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

    public function testDeleteUnusedPriceLists()
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

        $exceptPriceLists = $combinedPriceListRepository->findBy(['name' => 'test_cpl']);
        $this->assertNotEmpty($exceptPriceLists);

        $helper = $this->getContainer()->get('oro_entity.orm.native_query_executor_helper');

        // Check that there are no CPLs planned for removal
        $this->assertFalse($combinedPriceListRepository->hasPriceListsScheduledForRemoval());
        $combinedPriceListRepository->scheduleUnusedPriceListsRemoval($helper, $exceptPriceLists);
        // Check that second call will same PLs will not fail insert
        $combinedPriceListRepository->scheduleUnusedPriceListsRemoval($helper, $exceptPriceLists);
        // Check that there are CPLs planned for removal after scheduleUnusedPriceListsRemoval call
        $this->assertTrue($combinedPriceListRepository->hasPriceListsScheduledForRemoval());

        $requestedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        // Check that clearUnusedPriceListRemovalSchedule actually clears scheduled removals.
        $combinedPriceListRepository->clearUnusedPriceListRemovalSchedule($requestedAt);
        $this->assertFalse($combinedPriceListRepository->hasPriceListsScheduledForRemoval());

        // Schedule price lists for removal again to check their actual deletion
        $combinedPriceListRepository->scheduleUnusedPriceListsRemoval($helper, $exceptPriceLists);

        $priceListsForDelete = $combinedPriceListRepository
            ->getPriceListsScheduledForRemoval($helper, $requestedAt, $exceptPriceLists);
        $combinedPriceListRepository->deletePriceLists($priceListsForDelete);
        $priceLists = $combinedPriceListRepository->findBy(['name' => 'test_cpl']);
        $this->assertNotEmpty($priceLists);

        $priceLists = $combinedPriceListRepository->findBy(['name' => 'test_cpl2']);
        $this->assertEmpty($priceLists);
    }

    /**
     * @dataProvider updateCombinedPriceListConnectionDataProvider
     */
    public function testUpdateCombinedPriceListConnection(
        string $priceList,
        string $website,
        callable $getActual,
        string $targetEntity = null
    ) {
        /** @var CombinedPriceList $priceList */
        $priceList = $this->getReference($priceList);
        /** @var Website $website */
        $website = $this->getReference($website);

        if ($targetEntity) {
            /** @var Customer|CustomerGroup $targetEntity */
            $targetEntity = $this->getReference($targetEntity);
        }

        $this->getRepository()->updateCombinedPriceListConnection($priceList, $priceList, $website, 0, $targetEntity);
        /** @var BasePriceListRelation $actual */
        $actual = $getActual($website, $targetEntity);
        $this->assertEquals($priceList->getId(), $actual->getPriceList()->getId());
    }

    public function updateCombinedPriceListConnectionDataProvider(): array
    {
        $getConnection = function ($relationEntityClass, Website $website, array $additionalCriteria = []) {
            return $this->getContainer()->get('doctrine')
                ->getRepository($relationEntityClass)
                ->findOneBy(array_merge(['website' => $website], $additionalCriteria));
        };

        $getCustomerConnection = function (Website $website, Customer $targetEntity) use ($getConnection) {
            return $getConnection(
                CombinedPriceListToCustomer::class,
                $website,
                ['customer' => $targetEntity]
            );
        };

        $getCustomerGroupConnection = function (Website $website, CustomerGroup $targetEntity) use ($getConnection) {
            return $getConnection(
                CombinedPriceListToCustomerGroup::class,
                $website,
                ['customerGroup' => $targetEntity]
            );
        };

        $getWebsiteConnection = function (Website $website) use ($getConnection) {
            return $getConnection(
                CombinedPriceListToWebsite::class,
                $website
            );
        };

        return [
            'not changed for customer' => [
                '2t_3f_1t',
                LoadWebsiteData::WEBSITE1,
                $getCustomerConnection,
                'customer.level_1.2'
            ],
            'changed for customer' => [
                '2f_1t_3t',
                LoadWebsiteData::WEBSITE1,
                $getCustomerConnection,
                'customer.level_1.2'
            ],
            'new for customer' => [
                '2f_1t_3t',
                LoadWebsiteData::WEBSITE1,
                $getCustomerConnection,
                'customer.level_1'
            ],
            'not changed for customer group' => [
                '1t_2t_3t',
                LoadWebsiteData::WEBSITE1,
                $getCustomerGroupConnection,
                'customer_group.group1'
            ],
            'changed for customer group' => [
                '2f_1t_3t',
                LoadWebsiteData::WEBSITE1,
                $getCustomerGroupConnection,
                'customer_group.group1'
            ],
            'new for customer group' => [
                '2f_1t_3t',
                LoadWebsiteData::WEBSITE2,
                $getCustomerGroupConnection,
                'customer_group.group1'
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
     */
    public function testGetCombinedPriceListsByPriceListProduct(string $priceList, int $result, ?bool $calculatedPrices)
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceList);

        $cPriceLists = $this->getRepository()->getCombinedPriceListsByPriceList($priceList, $calculatedPrices);
        $this->assertCount($result, $cPriceLists);
    }

    public function cplByPriceListProductDataProvider(): array
    {
        return [
            [
                'priceList' => 'price_list_1',
                'result' => 4,
                'calculatedPrices' => null,
            ],
            [
                'priceList' => 'price_list_3',
                'result' => 4,
                'calculatedPrices' => false,
            ],
            [
                'priceList' => 'price_list_3',
                'result' => 0,
                'calculatedPrices' => true,
            ],
            [
                'priceList' => 'price_list_4',
                'result' => 0,
                'calculatedPrices' => true,
            ],
        ];
    }

    public function testGetCombinedPriceListsByPriceLists()
    {
        /** @var PriceList $priceList */
        $priceLists[] = $this->getReference('price_list_1');
        $priceLists[] = $this->getReference('price_list_3');
        $priceLists[] = $this->getReference('price_list_4');

        $cPriceLists = $this->getRepository()->getCombinedPriceListsByPriceLists($priceLists);
        $this->assertCount(8, $cPriceLists);
    }

    public function testGetCPLsForPriceCollectByTimeOffsetCount()
    {
        $cPriceLists = $this->getRepository()->getCPLsForPriceCollectByTimeOffsetCount(24);
        $this->assertEquals(1, $cPriceLists);
    }

    /**
     * @dataProvider getCPLsForPriceCollectByTimeOffsetDataProvider
     */
    public function testGetCPLsForPriceCollectByTimeOffset($offsetHours, $result)
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getReference('2f');
        $cpl->setPricesCalculated(true);
        $this->getManager()->flush();

        $cpl = $this->getReference('1f');
        $cpl->setPricesCalculated(false);
        $this->getManager()->flush();

        $cPriceLists = $this->getRepository()->getCPLsForPriceCollectByTimeOffset($offsetHours);
        $this->assertCount($result, $cPriceLists);
    }

    public function testGetCPLsForPriceCollectByTimeOffsetCheckActivationRuleActivity()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getReference('2f');
        $cpl->setPricesCalculated(false);
        $this->getManager()->flush();

        $cPriceLists = $this->getRepository()->getCPLsForPriceCollectByTimeOffset(24);
        $this->assertCount(1, $cPriceLists);
    }

    public function getCPLsForPriceCollectByTimeOffsetDataProvider(): array
    {
        return [
            [
                'offsetHours' => 11,
                'result' => 0
            ],
            [
                'offsetHours' => 7 * 24,
                'result' => 1
            ]
        ];
    }

    public function testHasOtherRelations()
    {
        $priceList = $this->getReference('1f');
        $relation3 = $this->getManager()->getRepository(CombinedPriceListToWebsite::class)
            ->findOneBy(['priceList' => $priceList]);
        $this->assertFalse($this->getRepository()->hasOtherRelations($relation3));
        $cplToCustomer = new CombinedPriceListToCustomer();
        $cplToCustomer->setWebsite($relation3->getWebsite());
        $cplToCustomer->setPriceList($priceList);
        $cplToCustomer->setCustomer($this->getReference('customer.level_1.2'));
        $this->getManager()->persist($cplToCustomer);
        $this->getManager()->flush();
        $this->assertTrue($this->getRepository()->hasOtherRelations($relation3));
    }

    private function getRepository(): CombinedPriceListRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(CombinedPriceList::class);
    }

    private function getManager(): ObjectManager
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(CombinedPriceList::class);
    }
}
