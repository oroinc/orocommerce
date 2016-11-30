<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToAccount;
use Oro\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
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
            'priceListsToAccounts' => [
                'account.level_1_1' => [ // No group
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
                'account.level_1.3' => [// Assigned to group1
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
                'account.level_1.2' => [ // Assigned to group2
                    [
                        'priceList' => 'price_list_2',
                        'priority' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
            ],
            'priceListsToAccountGroups' => [
                'account_group.group1' => [
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
                'account_group.group2' => [
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
            'priceListsToAccounts' => [
                'account.level_1_1' => [ // No group
                    [
                        'priceList' => 'price_list_1',
                        'priority' => 100,
                        'mergeAllowed' => true,
                    ]
                ],
                'account.level_1.1.1' => [
                    [
                        'priceList' => 'price_list_5',
                        'priority' => 100,
                        'mergeAllowed' => true,
                    ]
                ]
            ],
            'priceListsToAccountGroups' => [
                'account_group.group3' => [
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
            'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccounts',
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

            foreach ($priceListsData['priceListsToAccounts'] as $accountReference => $priceLists) {
                /** @var Account $account */
                $account = $this->getReference($accountReference);
                foreach ($priceLists as $priceListData) {
                    $priceListToAccount = new PriceListToAccount();
                    $priceListToAccount->setAccount($account);
                    $this->fillRelationData($priceListToAccount, $website, $priceListData);

                    $manager->persist($priceListToAccount);
                }
            }

            foreach ($priceListsData['priceListsToAccountGroups'] as $accountGroupReference => $priceLists) {
                /** @var AccountGroup $accountGroup */
                $accountGroup = $this->getReference($accountGroupReference);
                foreach ($priceLists as $priceListData) {
                    $priceListToAccountGroup = new PriceListToAccountGroup();
                    $priceListToAccountGroup->setAccountGroup($accountGroup);
                    $this->fillRelationData($priceListToAccountGroup, $website, $priceListData);

                    $manager->persist($priceListToAccountGroup);
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
        $priceListToWebsite->setPriority($priceListData['priority']);
        $priceListToWebsite->setMergeAllowed($priceListData['mergeAllowed']);
        $priceListToWebsite->setWebsite($website);
        $priceListToWebsite->setPriceList($priceList);
    }
}
