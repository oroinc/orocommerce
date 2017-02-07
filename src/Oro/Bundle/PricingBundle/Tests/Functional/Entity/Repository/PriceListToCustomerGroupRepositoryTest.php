<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class PriceListToCustomerGroupRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadPriceListRelations::class,
                LoadPriceListFallbackSettings::class,
            ]
        );
    }

    /**
     * @dataProvider restrictByPriceListDataProvider
     * @param $priceList
     * @param array $expectedCustomerGroups
     */
    public function testRestrictByPriceList($priceList, array $expectedCustomerGroups)
    {
        $alias = 'customer_group';
        $qb = $this->getContainer()->get('doctrine')
            ->getRepository(CustomerGroup::class)
            ->createQueryBuilder($alias);

        /** @var BasePriceList $priceList */
        $priceList = $this->getReference($priceList);

        $this->getRepository()->restrictByPriceList($qb, $priceList, 'priceList');

        $result = $qb->getQuery()->getResult();

        $this->assertCount(count($expectedCustomerGroups), $result);

        foreach ($expectedCustomerGroups as $expectedCustomer) {
            $this->assertContains($this->getReference($expectedCustomer), $result);
        }
    }

    /**
     * @return array
     */
    public function restrictByPriceListDataProvider()
    {
        return [
            [
                'priceList' => 'price_list_1',
                'expectedCustomerGroups' => [
                    'customer_group.group1'
                ]
            ],
            [
                'priceList' => 'price_list_2',
                'expectedCustomerGroups' => []
            ],
            [
                'priceList' => 'price_list_4',
                'expectedCustomerGroups' => [
                    'customer_group.group2'
                ]
            ],
            [
                'priceList' => 'price_list_5',
                'expectedCustomerGroups' => [
                    'customer_group.group1',
                    'customer_group.group3'
                ]
            ]
        ];
    }

    public function testFindByPrimaryKey()
    {
        $repository = $this->getRepository();

        /** @var PriceListToCustomerGroup $actualPriceListToCustomerGroup */
        $actualPriceListToCustomerGroup = $repository->findOneBy([]);

        $expectedPriceListToCustomerGroup = $repository->findByPrimaryKey(
            $actualPriceListToCustomerGroup->getPriceList(),
            $actualPriceListToCustomerGroup->getCustomerGroup(),
            $actualPriceListToCustomerGroup->getWebsite()
        );

        $this->assertEquals(
            spl_object_hash($expectedPriceListToCustomerGroup),
            spl_object_hash($actualPriceListToCustomerGroup)
        );
    }

    /**
     * @dataProvider getPriceListDataProvider
     * @param string $customerGroup
     * @param string $website
     * @param array $expectedPriceLists
     */
    public function testGetPriceLists($customerGroup, $website, array $expectedPriceLists)
    {
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference($customerGroup);
        /** @var Website $website */
        $website = $this->getReference($website);

        $actualPriceListsToCustomerGroup = $this->getRepository()->getPriceLists($customerGroup, $website);

        $actualPriceLists = array_map(
            function (PriceListToCustomerGroup $priceListToCustomerGroup) {
                return $priceListToCustomerGroup->getPriceList()->getName();
            },
            $actualPriceListsToCustomerGroup
        );

        $this->assertEquals($expectedPriceLists, $actualPriceLists);
    }

    /**
     * @return array
     */
    public function getPriceListDataProvider()
    {
        return [
            [
                'customer' => 'customer_group.group1',
                'website' => 'US',
                'expectedPriceLists' => [
                    'priceList5',
                    'priceList1'
                ]
            ],
            [
                'customer' => 'customer_group.group2',
                'website' => 'US',
                'expectedPriceLists' => [
                    'priceList4'
                ]
            ],
            [
                'customer' => 'customer_group.group3',
                'website' => 'Canada',
                'expectedPriceLists' => [
                    'priceList5'
                ]
            ],
        ];
    }

    /**
     * @dataProvider getPriceListIteratorDataProvider
     * @param string $website
     * @param int|null $fallback
     * @param array $expectedCustomerGroups
     */
    public function testGetCustomerGroupIteratorByFallback($website, $fallback, $expectedCustomerGroups)
    {
        /** @var $website Website */
        $website = $this->getReference($website);

        $iterator = $this->getRepository()
            ->getCustomerGroupIteratorByDefaultFallback($website, $fallback);

        $actualSiteMap = [];
        foreach ($iterator as $customerGroup) {
            $actualSiteMap[] = $customerGroup->getName();
        }
        $this->assertSame($expectedCustomerGroups, $actualSiteMap);
    }

    /**
     * @return array
     */
    public function getPriceListIteratorDataProvider()
    {
        return [
            'with fallback' => [
                'website' => 'US',
                'fallback' => PriceListCustomerGroupFallback::WEBSITE,
                'expectedCustomerGroups' => ['customer_group.group1']
            ],
            'without fallback' => [
                'website' => 'US',
                'fallback' => null,
                'expectedCustomerGroups' => [
                    'customer_group.group1',
                    'customer_group.group2'
                ]
            ],
        ];
    }

    public function testGetIteratorByPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_5');
        $iterator = $this->getRepository()->getIteratorByPriceList($priceList);
        $result = [];
        foreach ($iterator as $item) {
            $result[] = $item;
        }

        $this->assertEquals(
            [
                [
                    'customerGroup' => $this->getReference(LoadGroups::GROUP1)->getId(),
                    'website' => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                ],
                [
                    'customerGroup' => $this->getReference(LoadGroups::GROUP3)->getId(),
                    'website' => $this->getReference(LoadWebsiteData::WEBSITE2)->getId(),
                ],
            ],
            $result,
            "Iterator should return proper values",
            $delta = 0.0,
            $maxDepth = 10,
            $canonicalize = true
        );
    }

    /**
     * @dataProvider dataProviderRelationsByCustomer
     * @param $customerGroups
     * @param $expectsResult
     */
    public function testGetRelationsByHolders($customerGroups, $expectsResult)
    {
        $customersObjects = [];
        foreach ($customerGroups as $customerGroup) {
            $customersObjects[] = $this->getReference($customerGroup);
        }
        $relations = $this->getRepository()->getRelationsByHolders($customersObjects);
        $relations = array_map(
            function (PriceListToCustomerGroup $relation) {
                return [
                    $relation->getCustomerGroup()->getName(),
                    $relation->getWebsite()->getName(),
                    $relation->getPriceList()->getName(),
                ];
            },
            $relations
        );
        $this->assertEquals($expectsResult, $relations);
    }

    /**
     * @return array
     */
    public function dataProviderRelationsByCustomer()
    {
        return [
            [
                'customers' => [],
                'expectsResult' => [],
            ],
            [
                'customerGroups' => [
                    'customer_group.group1',
                    'customer_group.group2',
                ],
                'expectsResult' => [
                    ['customer_group.group1', 'US', 'priceList5'],
                    ['customer_group.group1', 'US', 'priceList1'],
                    ['customer_group.group1', 'US', 'priceList6'],
                    ['customer_group.group2', 'US', 'priceList4'],
                ],
            ],
        ];
    }


    /**
     * @return PriceListToCustomerGroupRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroPricingBundle:PriceListToCustomerGroup');
    }

    public function testDelete()
    {
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference('customer_group.group1');
        /** @var Website $website */
        $website = $this->getReference('US');
        $this->assertCount(5, $this->getRepository()->findAll());
        $this->assertCount(
            3,
            $this->getRepository()->findBy(['customerGroup' => $customerGroup, 'website' => $website])
        );
        $this->getRepository()->delete($customerGroup, $website);
        $this->assertCount(2, $this->getRepository()->findAll());
        $this->assertCount(
            0,
            $this->getRepository()->findBy(['customerGroup' => $customerGroup, 'website' => $website])
        );
    }
}
