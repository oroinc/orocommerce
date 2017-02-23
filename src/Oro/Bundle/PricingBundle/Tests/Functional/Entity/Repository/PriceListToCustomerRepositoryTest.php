<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\Model\DTO\CustomerWebsiteDTO;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PriceListToCustomerRepositoryTest extends WebTestCase
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
     * @param array $expectedCustomers
     */
    public function testRestrictByPriceList($priceList, array $expectedCustomers)
    {
        $qb = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroCustomerBundle:Customer')
            ->getRepository('OroCustomerBundle:Customer')
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

    /**
     * @return array
     */
    public function restrictByPriceListDataProvider()
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
     * @param string $customer
     * @param string $website
     * @param array $expectedPriceLists
     */
    public function testGetPriceLists($customer, $website, array $expectedPriceLists)
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

    /**
     * @return array
     */
    public function getPriceListDataProvider()
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
     * @param string $customerGroup
     * @param string $website
     * @param array $expectedCustomers
     * @param int $fallback
     */
    public function testGetCustomerIteratorByFallback($customerGroup, $website, $expectedCustomers, $fallback = null)
    {
        /** @var $customerGroup  CustomerGroup */
        $customerGroup = $this->getReference($customerGroup);
        /** @var $website Website */
        $website = $this->getReference($website);

        $iterator = $this->getRepository()
            ->getCustomerIteratorByDefaultFallback($customerGroup, $website, $fallback);

        $actualSiteMap = [];
        foreach ($iterator as $customer) {
            $actualSiteMap[] = $customer->getName();
        }
        $this->assertSame($expectedCustomers, $actualSiteMap);
    }

    /**
     * @return array
     */
    public function getPriceListIteratorDataProvider()
    {
        return [
            'with fallback group1' => [
                'customerGroup' => 'customer_group.group1',
                'website' => 'US',
                'expectedCustomers' => ['customer.level_1.3'],
                'fallback' => PriceListCustomerFallback::ACCOUNT_GROUP
            ],
            'without fallback group1' => [
                'customerGroup' => 'customer_group.group1',
                'website' => 'US',
                'expectedCustomers' => ['customer.level_1.3']
            ],
            'with fallback group2' => [
                'customerGroup' => 'customer_group.group2',
                'website' => 'US',
                'expectedCustomers' => [],
                'fallback' => PriceListCustomerFallback::ACCOUNT_GROUP
            ],
            'without fallback group2' => [
                'customerGroup' => 'customer_group.group2',
                'website' => 'US',
                'expectedCustomers' => ['customer.level_1.2'],
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
        $iterator = $this->getRepository()->getIteratorByPriceList($priceList);
        $result = [];
        foreach ($iterator as $item) {
            $result[] = $item;
        }

        $this->assertEquals(
            [
                [
                    'customer'  => $this->getReference('customer.level_1.3')->getId(),
                    'customerGroup'  => $this->getReference(LoadGroups::GROUP1)->getId(),
                    'website' => $this->getReference(LoadWebsiteData::WEBSITE1)->getId()
                ]
            ],
            $result
        );
    }

    public function testGetCustomerWebsitePairsByCustomer()
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer.level_1_1');

        /** @var CustomerWebsiteDTO[] $result */
        $result = $this->getRepository()->getCustomerWebsitePairsByCustomer($customer);
        $this->assertCount(2, $result);

        $expected = [
            $customer->getId() => [
                $this->getReference('US')->getId(),
                $this->getReference('Canada')->getId()
            ]
        ];

        $actual = [];
        foreach ($result as $item) {
            $actual[$item->getCustomer()->getId()][] = $item->getWebsite()->getId();
        }

        foreach ($actual as $customerId => $websites) {
            $this->assertEquals($customer->getId(), $customerId);
            foreach ($websites as $website) {
                $this->assertContains($website, $expected[$customerId]);
            }
        }
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
     * @param $customers
     * @param $expectsResult
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

    /**
     * @return PriceListToCustomerRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroPricingBundle:PriceListToCustomer');
    }
}
