<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class LoadPriceListRelations extends AbstractFixture implements DependentFixtureInterface
{
    const PRICE_LIST_TO_WEBSITE_1 = 'price_list_6_US';
    const PRICE_LIST_TO_WEBSITE_2 = 'price_list_1_US';
    const PRICE_LIST_TO_WEBSITE_3 = 'price_list_3_US';
    const PRICE_LIST_TO_WEBSITE_4 = 'price_list_3_Canada';

    const PRICE_LIST_TO_CUSTOMER_GROUP_1 = 'price_list_6_US_customer_group1';
    const PRICE_LIST_TO_CUSTOMER_GROUP_2 = 'price_list_1_US_customer_group1';
    const PRICE_LIST_TO_CUSTOMER_GROUP_3 = 'price_list_5_US_customer_group1';
    const PRICE_LIST_TO_CUSTOMER_GROUP_4 = 'price_list_4_US_customer_group2';
    const PRICE_LIST_TO_CUSTOMER_GROUP_5 = 'price_list_5_Canada_customer_group3';

    const PRICE_LIST_TO_CUSTOMER_US_1 = 'price_list_to_customer_US_1';
    const PRICE_LIST_TO_CUSTOMER_US_2 = 'price_list_to_customer_US_2';
    const PRICE_LIST_TO_CUSTOMER_US_3 = 'price_list_to_customer_US_3';
    const PRICE_LIST_TO_CUSTOMER_US_4 = 'price_list_to_customer_US_4';
    const PRICE_LIST_TO_CUSTOMER_US_5 = 'price_list_to_customer_US_5';
    const PRICE_LIST_TO_CUSTOMER_US_6 = 'price_list_to_customer_US_6';
    const PRICE_LIST_TO_CUSTOMER_CANADA_1 = 'price_list_to_customer_canada_1';
    const PRICE_LIST_TO_CUSTOMER_CANADA_2 = 'price_list_to_customer_canada_2';

    /**
     * @var array
     */
    protected $data = [
        'US' => [
            'priceLists' => [
                [
                    'reference' => self::PRICE_LIST_TO_WEBSITE_1,
                    'priceList' => 'price_list_6',
                    'sort_order' => 200,
                    'mergeAllowed' => false,
                ],
                [
                    'reference' => self::PRICE_LIST_TO_WEBSITE_2,
                    'priceList' => 'price_list_1',
                    'sort_order' => 100,
                    'mergeAllowed' => true,
                ],
                [
                    'reference' => self::PRICE_LIST_TO_WEBSITE_3,
                    'priceList' => 'price_list_3',
                    'sort_order' => 50,
                    'mergeAllowed' => false,
                ],
            ],
            'priceListsToCustomers' => [
                'customer.level_1_1' => [ // No group
                    [
                        'reference' => self::PRICE_LIST_TO_CUSTOMER_US_1,
                        'priceList' => 'price_list_1',
                        'sort_order' => 300,
                        'mergeAllowed' => true,
                    ],
                    [
                        'reference' => self::PRICE_LIST_TO_CUSTOMER_US_2,
                        'priceList' => 'price_list_2',
                        'sort_order' => 100,
                        'mergeAllowed' => false,
                    ]
                ],
                'customer.level_1.3' => [// Assigned to group1
                    [
                        'reference' => self::PRICE_LIST_TO_CUSTOMER_US_3,
                        'priceList' => 'price_list_6',
                        'sort_order' => 100,
                        'mergeAllowed' => false,
                    ],
                    [
                        'reference' => self::PRICE_LIST_TO_CUSTOMER_US_4,
                        'priceList' => 'price_list_4',
                        'sort_order' => 50,
                        'mergeAllowed' => true,
                    ],
                    [
                        'reference' => self::PRICE_LIST_TO_CUSTOMER_US_5,
                        'priceList' => 'price_list_2',
                        'sort_order' => 80,
                        'mergeAllowed' => true,
                    ]
                ],
                'customer.level_1.2' => [ // Assigned to group2
                    [
                        'reference' => self::PRICE_LIST_TO_CUSTOMER_US_6,
                        'priceList' => 'price_list_2',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
            ],
            'priceListsToCustomerGroups' => [
                'customer_group.group1' => [
                    [
                        'reference' => self::PRICE_LIST_TO_CUSTOMER_GROUP_1,
                        'priceList' => 'price_list_6',
                        'sort_order' => 500,
                        'mergeAllowed' => false,
                    ],
                    [
                        'reference' => self::PRICE_LIST_TO_CUSTOMER_GROUP_2,
                        'priceList' => 'price_list_1',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ],
                    [
                        'reference' => self::PRICE_LIST_TO_CUSTOMER_GROUP_3,
                        'priceList' => 'price_list_5',
                        'sort_order' => 50,
                        'mergeAllowed' => false,
                    ],
                ],
                'customer_group.group2' => [
                    [
                        'reference' => self::PRICE_LIST_TO_CUSTOMER_GROUP_4,
                        'priceList' => 'price_list_4',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ],
                ]
            ],
        ],
        'Canada' => [
            'priceLists' => [
                [
                    'reference' => self::PRICE_LIST_TO_WEBSITE_4,
                    'priceList' => 'price_list_3',
                    'sort_order' => 100,
                    'mergeAllowed' => true,
                ]
            ],
            'priceListsToCustomers' => [
                'customer.level_1_1' => [ // No group
                    [
                        'reference' => self::PRICE_LIST_TO_CUSTOMER_CANADA_1,
                        'priceList' => 'price_list_1',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
                'customer.level_1.1.1' => [
                    [
                        'reference' => self::PRICE_LIST_TO_CUSTOMER_CANADA_2,
                        'priceList' => 'price_list_5',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ]
                ]
            ],
            'priceListsToCustomerGroups' => [
                'customer_group.group3' => [
                    [
                        'reference' => self::PRICE_LIST_TO_CUSTOMER_GROUP_5,
                        'priceList' => 'price_list_5',
                        'sort_order' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
            ],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
            'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers',
            'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups',
            'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $websiteReference => $priceListsData) {
            /** @var Website $website */
            $website = $this->getReference($websiteReference);
            foreach ($priceListsData['priceLists'] as $priceListData) {
                $priceListToWebsite = new PriceListToWebsite();
                $this->fillRelationData($priceListToWebsite, $website, $priceListData);

                $manager->persist($priceListToWebsite);
                $this->setReference($priceListData['reference'], $priceListToWebsite);
            }

            foreach ($priceListsData['priceListsToCustomers'] as $customerReference => $priceLists) {
                /** @var Customer $customer */
                $customer = $this->getReference($customerReference);
                foreach ($priceLists as $priceListData) {
                    $priceListToCustomer = new PriceListToCustomer();
                    $priceListToCustomer->setCustomer($customer);
                    $this->fillRelationData($priceListToCustomer, $website, $priceListData);

                    $manager->persist($priceListToCustomer);
                    $this->setReference($priceListData['reference'], $priceListToCustomer);
                }
            }

            foreach ($priceListsData['priceListsToCustomerGroups'] as $customerGroupReference => $priceLists) {
                /** @var CustomerGroup $customerGroup */
                $customerGroup = $this->getReference($customerGroupReference);
                foreach ($priceLists as $priceListData) {
                    $priceListToCustomerGroup = new PriceListToCustomerGroup();
                    $priceListToCustomerGroup->setCustomerGroup($customerGroup);
                    $this->fillRelationData($priceListToCustomerGroup, $website, $priceListData);

                    $manager->persist($priceListToCustomerGroup);
                    $this->setReference($priceListData['reference'], $priceListToCustomerGroup);
                }
            }
        }

        $manager->flush();
    }

    protected function fillRelationData(
        BasePriceListRelation $priceListToWebsite,
        Website $website,
        array $priceListData
    ) {
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceListData['priceList']);
        $priceListToWebsite->setSortOrder($priceListData['sort_order']);
        $priceListToWebsite->setMergeAllowed($priceListData['mergeAllowed']);
        $priceListToWebsite->setWebsite($website);
        $priceListToWebsite->setPriceList($priceList);
    }
}
