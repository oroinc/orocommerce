<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\Model\DTO\CustomerWebsiteDTO;
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
class PriceListToCustomerRepositoryTest extends WebTestCase
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
    public function testRestrictByPriceList($priceList, array $expectedCustomers)
    {
        $qb = $this->getContainer()->get('doctrine')
            ->getManagerForClass(Customer::class)
            ->getRepository(Customer::class)
            ->createQueryBuilder('customer');

        /** @var BasePriceList $priceList */
        $priceList = $this->getReference($priceList);

        $this->getRepository()->restrictByPriceList($qb, $priceList, 'priceList');

        $result = $qb->getQuery()->getResult();

        $this->assertCount(count($expectedCustomers), $result);

        foreach ($expectedCustomers as $expectedCustomer) {
            $this->assertContains($this->getReference($expectedCustomer), $result);
        }
    }

    public function restrictByPriceListDataProvider(): array
    {
        return [
            [
                'priceList' => 'price_list_2',
                'expectedCustomers' => [
                    'customer.level_1_1',
                    'customer.level_1.2',
                    'customer.level_1.3'
                ]
            ],
            [
                'priceList' => 'price_list_5',
                'expectedCustomers' => [
                    'customer.level_1.1.1'
                ]
            ],
            [
                'priceList' => 'price_list_4',
                'expectedCustomers' => [
                    'customer.level_1.3'
                ]
            ],
            [
                'priceList' => 'price_list_6',
                'expectedCustomers' => [
                    'customer.level_1.3'
                ]
            ],
            [
                'priceList' => 'price_list_1',
                'expectedCustomers' => [
                    'customer.level_1_1'
                ]
            ]
        ];
    }

    public function testFindByPrimaryKey()
    {
        $repository = $this->getRepository();

        /** @var PriceListToCustomer $actualPriceListToCustomer */
        $actualPriceListToCustomer = $repository->findOneBy([]);

        $expectedPriceListToCustomer = $repository->findByPrimaryKey(
            $actualPriceListToCustomer->getPriceList(),
            $actualPriceListToCustomer->getCustomer(),
            $actualPriceListToCustomer->getWebsite()
        );

        $this->assertEquals(spl_object_hash($expectedPriceListToCustomer), spl_object_hash($actualPriceListToCustomer));
    }

    /**
     * @dataProvider getPriceListDataProvider
     */
    public function testGetPriceLists(string $customer, string $website, array $expectedPriceLists)
    {
        /** @var Customer $customer */
        $customer = $this->getReference($customer);
        /** @var Website $website */
        $website = $this->getReference($website);

        $actualPriceListsToCustomer = $this->getRepository()->getPriceLists($customer, $website);

        $actualPriceLists = array_map(
            function (PriceListToCustomer $priceListToCustomer) {
                return $priceListToCustomer->getPriceList()->getName();
            },
            $actualPriceListsToCustomer
        );

        $this->assertEquals($expectedPriceLists, $actualPriceLists);
    }

    public function getPriceListDataProvider(): array
    {
        return [
            [
                'customer' => 'customer.level_1.2',
                'website' => 'US',
                'expectedPriceLists' => [
                    'priceList2'
                ]
            ],
            [
                'customer' => 'customer.orphan',
                'website' => 'US',
                'expectedPriceLists' => [
                ]
            ],
            [
                'customer' => 'customer.level_1_1',
                'website' => 'US',
                'expectedPriceLists' => [
                    'priceList2',
                    'priceList1'
                ]
            ],
            [
                'customer' => 'customer.level_1.1.1',
                'website' => 'Canada',
                'expectedPriceLists' => [
                    'priceList5'
                ]
            ],
        ];
    }

    /**
     * @dataProvider getPriceListIteratorDataProvider
     */
    public function testGetCustomerIteratorWithDefaultFallback(
        string $customerGroup,
        string $website,
        array $expectedCustomers
    ) {
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference($customerGroup);
        /** @var Website $website */
        $website = $this->getReference($website);

        $iterator = $this->getRepository()->getCustomerIteratorWithDefaultFallback($customerGroup, $website);

        $actualSiteMap = [];
        foreach ($iterator as $customer) {
            $actualSiteMap[] = $customer->getName();
        }
        $this->assertSame($expectedCustomers, $actualSiteMap);
    }

    public function getPriceListIteratorDataProvider(): array
    {
        return [
            'group1' => [
                'customerGroup' => 'customer_group.group1',
                'website' => 'US',
                'expectedCustomers' => [
                    'customer.level_1.3'
                ]
            ],
            'group2' => [
                'customerGroup' => 'customer_group.group2',
                'website' => 'US',
                'expectedCustomers' => []
            ],
        ];
    }

    public function testGetCustomerWebsitePairsByCustomerGroupIterator()
    {
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference('customer_group.group1');
        /** @var Customer $customer */
        $customer = $this->getReference('customer.level_1.3');
        /** @var Website $website */
        $website = $this->getReference('US');

        $iterator = $this->getRepository()->getCustomerWebsitePairsByCustomerGroupIterator($customerGroup);
        $result = [];
        foreach ($iterator as $item) {
            $result[] = $item;
        }
        $this->assertEquals(
            [
                [
                    'customer' => $customer->getId(),
                    'website' => $website->getId()
                ]
            ],
            $result
        );
    }

    public function testGetIteratorByPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_6');
        $result1 = iterator_to_array($this->getRepository()->getIteratorByPriceList($priceList));
        $result2 = iterator_to_array($this->getRepository()->getIteratorByPriceLists([$priceList]));
        $this->assertEquals(
            [
                [
                    'customer'  => $this->getReference('customer.level_1.3')->getId(),
                    'customerGroup'  => $this->getReference(LoadGroups::GROUP1)->getId(),
                    'website' => $this->getReference(LoadWebsiteData::WEBSITE1)->getId()
                ]
            ],
            $result1
        );
        $this->assertSame($result1, $result2);
    }

    public function testGetAllCustomerWebsitePairsWithSelfFallback()
    {
        /** @var CustomerWebsiteDTO[] $result */
        $result = $this->getRepository()->getAllCustomerWebsitePairsWithSelfFallback();
        $this->assertCount(3, $result);

        $expected = [
            $this->getReference('customer.level_1_1')->getId() => [
                $this->getReference('Canada')->getId(),
            ],
            $this->getReference('customer.level_1.2')->getId() => [
                $this->getReference('US')->getId(),
                $this->getReference('Canada')->getId()
            ],
        ];

        $actual = [];
        foreach ($result as $item) {
            $actual[$item->getCustomer()->getId()][] = $item->getWebsite()->getId();
        }

        $this->assertEquals($expected, $actual);
    }

    public function testDelete()
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer.level_1_1');
        /** @var Website $website */
        $website = $this->getReference('US');
        $this->assertCount(8, $this->getRepository()->findAll());
        $this->assertCount(2, $this->getRepository()->findBy(['customer' => $customer, 'website' => $website]));
        $this->getRepository()->delete($customer, $website);
        $this->assertCount(6, $this->getRepository()->findAll());
        $this->assertCount(0, $this->getRepository()->findBy(['customer' => $customer, 'website' => $website]));
    }

    /**
     * @dataProvider dataProviderRelationsByCustomer
     */
    public function testGetRelationsByHolders($customers, $expectsResult)
    {
        $customersObjects = [];
        foreach ($customers as $customerName) {
            $customersObjects[] = $this->getReference($customerName);
        }
        $relations = $this->getRepository()->getRelationsByHolders($customersObjects);
        $relations = array_map(function (PriceListToCustomer $relation) {
            return [
                $relation->getCustomer()->getName(),
                $relation->getWebsite()->getName(),
                $relation->getPriceList()->getName()
            ];
        }, $relations);
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
                'customers' => [
                    'customer.level_1.2',
                    'customer.level_1.3',
                ],
                'expectsResult' => [
                    ['customer.level_1.2', 'US', 'priceList2'],
                    ['customer.level_1.3', 'US', 'priceList4'],
                    ['customer.level_1.3', 'US', 'priceList2'],
                    ['customer.level_1.3', 'US', 'priceList6'],
                ],
            ],
        ];
    }

    private function getRepository(): PriceListToCustomerRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(PriceListToCustomer::class);
    }

    /**
     * @dataProvider assignedPriceListsDataProvider
     */
    public function testHasAssignedPriceLists(string $websiteReference, string $customerReference, bool $expected)
    {
        /** @var Website $website */
        $website = $this->getReference($websiteReference);
        /** @var Customer $customerGroup */
        $customer = $this->getReference($customerReference);

        $this->assertEquals($expected, $this->getRepository()->hasAssignedPriceLists($website, $customer));
    }

    public function assignedPriceListsDataProvider(): array
    {
        return [
            ['US', 'customer.level_1_1', true],
            ['CA', 'customer.level_1_1', false]
        ];
    }

    public function testGetAllCustomersWithEmptyGroupAndDefaultFallback()
    {
        /** @var Website $website */
        $website = $this->getReference('US');
        $customers = iterator_to_array(
            $this->getRepository()->getAllCustomersWithEmptyGroupAndDefaultFallback($website)
        );

        $this->assertCount(1, $customers);
        $this->assertEquals('customer.level_1_1', $customers[0]->getName());
    }

    public function testGetCustomersWithAssignedPriceListsNoGroup()
    {
        /** @var Website $website */
        $website = $this->getReference('US');
        /** @var Customer $expectedCustomer */
        $expectedCustomer = $this->getReference('customer.level_1_1');

        $customers = $this->getRepository()->getCustomersWithAssignedPriceLists($website);
        $this->assertCount(1, $customers);
        $this->assertArrayHasKey($expectedCustomer->getId(), $customers);
        $this->assertTrue($customers[$expectedCustomer->getId()]);
    }

    public function testGetCustomersWithAssignedPriceListsWithGroup()
    {
        /** @var Website $website */
        $website = $this->getReference('US');
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference('customer_group.group1');
        /** @var Customer $expectedCustomer */
        $expectedCustomer = $this->getReference('customer.level_1.3');

        $customers = $this->getRepository()->getCustomersWithAssignedPriceLists($website, $customerGroup);
        $this->assertCount(1, $customers);
        $this->assertArrayHasKey($expectedCustomer->getId(), $customers);
        $this->assertTrue($customers[$expectedCustomer->getId()]);
    }

    public function testGetFirstRelation()
    {
        $customer = $this->getReference('customer.level_1_1');
        $website = $this->getReference('US');
        $expectedPriceList = $this->getReference('price_list_2');

        $priceListRelation = $this->getRepository()->getFirstRelation($website, $customer);
        $this->assertInstanceOf(PriceListToCustomer::class, $priceListRelation);
        $this->assertEquals($expectedPriceList->getId(), $priceListRelation->getPriceList()->getId());
    }

    public function testGetCustomerIteratorWithSelfFallback()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE2);
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference('customer_group.group2');

        $customers = $this->getRepository()->getCustomerIteratorWithSelfFallback($customerGroup, $website);

        $customerIds = [];
        foreach ($customers as $customer) {
            $customerIds[] = $customer->getId();
        }

        $expected = [
            $this->getReference('customer.level_1.2')->getId()
        ];
        $this->assertEqualsCanonicalizing($expected, $customerIds);
    }

    public function testGetAllCustomersWithEmptyGroupAndSelfFallback()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE2);

        $customers = $this->getRepository()->getAllCustomersWithEmptyGroupAndSelfFallback($website);

        $customerIds = [];
        foreach ($customers as $customer) {
            $customerIds[] = $customer->getId();
        }

        $expected = [
            $this->getReference('customer.level_1_1')->getId()
        ];
        $this->assertEqualsCanonicalizing($expected, $customerIds);
    }
}
