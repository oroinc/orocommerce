<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PriceListToCustomerGroupRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadPriceListRelations::class,
            LoadPriceListFallbackSettings::class,
        ]);
    }

    /**
     * @dataProvider restrictByPriceListDataProvider
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

    public function restrictByPriceListDataProvider(): array
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
     */
    public function testGetPriceLists(string $customerGroup, string $website, array $expectedPriceLists)
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

    public function getPriceListDataProvider(): array
    {
        return [
            [
                'customer' => 'customer_group.group1',
                'website' => 'US',
                'expectedPriceLists' => [
                    'priceList5',
                    'priceList1',
                    'priceList6' // Not active
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

    public function testGetCustomerGroupIteratorWithDefaultFallback()
    {
        /** @var Website $website */
        $website = $this->getReference('US');
        $expectedCustomerGroups = [
            'customer_group.group1'
        ];

        $iterator = $this->getRepository()->getCustomerGroupIteratorWithDefaultFallback($website);

        $actualSiteMap = [];
        foreach ($iterator as $customerGroup) {
            $actualSiteMap[] = $customerGroup->getName();
        }
        $this->assertSame($expectedCustomerGroups, $actualSiteMap);
    }

    public function testGetCustomerGroupIteratorWithSelfFallback()
    {
        /** @var Website $website */
        $website = $this->getReference('US');
        $expectedCustomerGroups = [
            'customer_group.group2',
        ];

        $iterator = $this->getRepository()->getCustomerGroupIteratorWithSelfFallback($website);

        $actualSiteMap = [];
        foreach ($iterator as $customerGroup) {
            $actualSiteMap[] = $customerGroup->getName();
        }
        $this->assertSame($expectedCustomerGroups, $actualSiteMap);
    }

    public function testGetAllWebsiteIds()
    {
        self::assertEqualsCanonicalizing(
            [
                $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                $this->getReference(LoadWebsiteData::WEBSITE2)->getId(),
            ],
            $this->getRepository()->getAllWebsiteIds(),
        );
    }

    public function testGetIteratorByPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_5');
        $result1 = iterator_to_array($this->getRepository()->getIteratorByPriceList($priceList));
        $result2 = iterator_to_array($this->getRepository()->getIteratorByPriceLists([$priceList]));

        self::assertEqualsCanonicalizing(
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
            $result1,
            'Iterator should return proper values',
        );
        $this->assertSame($result1, $result2);
    }

    /**
     * @dataProvider dataProviderRelationsByCustomer
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

    public function dataProviderRelationsByCustomer(): array
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

    private function getRepository(): PriceListToCustomerGroupRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(PriceListToCustomerGroup::class);
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

    /**
     * @dataProvider assignedPriceListsDataProvider
     */
    public function testHasAssignedPriceLists(string $websiteReference, string $customerGroupReference, bool $expected)
    {
        /** @var Website $website */
        $website = $this->getReference($websiteReference);
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference($customerGroupReference);

        $this->assertEquals($expected, $this->getRepository()->hasAssignedPriceLists($website, $customerGroup));
    }

    public function assignedPriceListsDataProvider(): array
    {
        return [
            ['US', 'customer_group.group1', true],
            ['CA', 'customer_group.group1', false]
        ];
    }

    public function testGetFirstRelation()
    {
        $customerGroup = $this->getReference('customer_group.group1');
        $website = $this->getReference('US');
        $expectedPriceList = $this->getReference('price_list_5');

        $priceListRelation = $this->getRepository()->getFirstRelation($website, $customerGroup);
        $this->assertInstanceOf(PriceListToCustomerGroup::class, $priceListRelation);
        $this->assertEquals($expectedPriceList->getId(), $priceListRelation->getPriceList()->getId());
    }
}
