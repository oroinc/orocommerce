<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

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
    /**
     * @var array
     */
    protected $data = [
        'US' => [
            'priceLists' => [
                [
                    'priceList' => 'price_list_6',
                    'priority' => 200,
                    'mergeAllowed' => false,
                ],
                [
                    'priceList' => 'price_list_1',
                    'priority' => 100,
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => 'price_list_3',
                    'priority' => 50,
                    'mergeAllowed' => false,
                ],
            ],
            'priceListsToCustomers' => [
                'customer.level_1_1' => [ // No group
                    [
                        'priceList' => 'price_list_1',
                        'priority' => 300,
                        'mergeAllowed' => true,
                    ],
                    [
                        'priceList' => 'price_list_2',
                        'priority' => 100,
                        'mergeAllowed' => false,
                    ]
                ],
                'customer.level_1.3' => [// Assigned to group1
                    [
                        'priceList' => 'price_list_6',
                        'priority' => 100,
                        'mergeAllowed' => false,
                    ],
                    [
                        'priceList' => 'price_list_4',
                        'priority' => 50,
                        'mergeAllowed' => true,
                    ],
                    [
                        'priceList' => 'price_list_2',
                        'priority' => 80,
                        'mergeAllowed' => true,
                    ]
                ],
                'customer.level_1.2' => [ // Assigned to group2
                    [
                        'priceList' => 'price_list_2',
                        'priority' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
            ],
            'priceListsToCustomerGroups' => [
                'customer_group.group1' => [
                    [
                        'priceList' => 'price_list_6',
                        'priority' => 500,
                        'mergeAllowed' => false,
                    ],
                    [
                        'priceList' => 'price_list_1',
                        'priority' => 100,
                        'mergeAllowed' => true,
                    ],
                    [
                        'priceList' => 'price_list_5',
                        'priority' => 50,
                        'mergeAllowed' => false,
                    ],
                ],
                'customer_group.group2' => [
                    [
                        'priceList' => 'price_list_4',
                        'priority' => 100,
                        'mergeAllowed' => true,
                    ],
                ]
            ],
        ],
        'Canada' => [
            'priceLists' => [
                [
                    'priceList' => 'price_list_3',
                    'priority' => 100,
                    'mergeAllowed' => true,
                ]
            ],
            'priceListsToCustomers' => [
                'customer.level_1_1' => [ // No group
                    [
                        'priceList' => 'price_list_1',
                        'priority' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
                'customer.level_1.1.1' => [
                    [
                        'priceList' => 'price_list_5',
                        'priority' => 100,
                        'mergeAllowed' => true,
                    ]
                ]
            ],
            'priceListsToCustomerGroups' => [
                'customer_group.group3' => [
                    [
                        'priceList' => 'price_list_5',
                        'priority' => 100,
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
            }

            foreach ($priceListsData['priceListsToCustomers'] as $customerReference => $priceLists) {
                /** @var Customer $customer */
                $customer = $this->getReference($customerReference);
                foreach ($priceLists as $priceListData) {
                    $priceListToCustomer = new PriceListToCustomer();
                    $priceListToCustomer->setCustomer($customer);
                    $this->fillRelationData($priceListToCustomer, $website, $priceListData);

                    $manager->persist($priceListToCustomer);
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
                }
            }
        }

        $manager->flush();
    }

    /**
     * @param BasePriceListRelation $priceListToWebsite
     * @param Website $website
     * @param array $priceListData
     */
    protected function fillRelationData(
        BasePriceListRelation $priceListToWebsite,
        Website $website,
        array $priceListData
    ) {
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceListData['priceList']);
        $priceListToWebsite ->setSortOrder($priceListData['sort_order']);
        $priceListToWebsite->setMergeAllowed($priceListData['mergeAllowed']);
        $priceListToWebsite->setWebsite($website);
        $priceListToWebsite->setPriceList($priceList);
    }
}
