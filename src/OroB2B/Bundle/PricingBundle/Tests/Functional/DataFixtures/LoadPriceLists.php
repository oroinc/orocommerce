<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class LoadPriceLists extends AbstractFixture implements DependentFixtureInterface
{
    const DEFAULT_PRIORITY = 10;

    /**
     * @var array
     */
    protected $data = [
        [
            'name' => 'priceList1',
            'reference' => 'price_list_1',
            'default' => false,
            'priceListsToAccounts' => [],
            'priceListsToAccountGroups' => [
                [
                    'group' => 'account_group.group1',
                    'website' => 'US',
                ]
            ],
            'websites' => ['US'],
            'currencies' => ['USD', 'EUR', 'AUD', 'CAD']
        ],
        [
            'name' => 'priceList2',
            'reference' => 'price_list_2',
            'default' => false,
            'priceListsToAccounts' => [
                [
                    'account' => 'account.level_1.2',
                    'website' => 'US',
                ],
                [
                    'account' => 'account.level_1.2',
                    'website' => 'Canada',
                ],
            ],
            'priceListsToAccountGroups' => [],
            'websites' => [],
            'currencies' => ['USD']
        ],
        [
            'name' => 'priceList3',
            'reference' => 'price_list_3',
            'default' => false,
            'priceListsToAccounts' => [
                [
                    'account' => 'account.orphan',
                    'website' => 'US',
                ]
            ],
            'priceListsToAccountGroups' => [],
            'websites' => ['Canada'],
            'currencies' => ['CAD']
        ],
        [
            'name' => 'priceList4',
            'reference' => 'price_list_4',
            'default' => false,
            'priceListsToAccounts' => [
                [
                    'account' => 'account.level_1.1',
                    'website' => 'US',
                ]
            ],
            'priceListsToAccountGroups' => [
                [
                    'group' => 'account_group.group2',
                    'website' => 'US',
                ]
            ],
            'websites' => [],
            'currencies' => ['GBP']
        ],
        [
            'name' => 'priceList5',
            'reference' => 'price_list_5',
            'default' => false,
            'priceListsToAccounts' => [
                [
                    'account' => 'account.level_1.1.1',
                    'website' => 'Canada',
                ]
            ],
            'priceListsToAccountGroups' => [
                [
                    'group' => 'account_group.group3',
                    'website' => 'Canada',
                ]
            ],
            'websites' => [],
            'currencies' => ['GBP', 'EUR']
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $now = new \DateTime();

        foreach ($this->data as $priceListData) {
            $priceList = new PriceList();

            $priceList
                ->setName($priceListData['name'])
                ->setDefault($priceListData['default'])
                ->setCurrencies(['USD'])
                ->setCreatedAt($now)
                ->setUpdatedAt($now);

            foreach ($priceListData['priceListsToAccounts'] as $priceListsToAccount) {
                /** @var Account $account */
                $account = $this->getReference($priceListsToAccount['account']);
                /** @var Website $website */
                $website = $this->getReference($priceListsToAccount['website']);

                $priceListToAccount = new PriceListToAccount();
                $priceListToAccount->setPriority(static::DEFAULT_PRIORITY);
                $priceListToAccount->setAccount($account);
                $priceListToAccount->setWebsite($website);
                $priceListToAccount->setPriceList($priceList);
                $manager->persist($priceListToAccount);
            }

            foreach ($priceListData['priceListsToAccountGroups'] as $priceListsToAccountGroup) {
                /** @var AccountGroup $accountGroup */
                $accountGroup = $this->getReference($priceListsToAccountGroup['group']);
                /** @var Website $website */
                $website = $this->getReference($priceListsToAccountGroup['website']);

                $priceListToAccountGroup = new PriceListToAccountGroup();
                $priceListToAccountGroup->setPriority(static::DEFAULT_PRIORITY);
                $priceListToAccountGroup->setAccountGroup($accountGroup);
                $priceListToAccountGroup->setWebsite($website);
                $priceListToAccountGroup->setPriceList($priceList);
                $manager->persist($priceListToAccountGroup);
            }

            foreach ($priceListData['websites'] as $websiteReference) {
                /** @var Website $website */
                $website = $this->getReference($websiteReference);

                $priceListToWebsite = new PriceListToWebsite();
                $priceListToWebsite->setPriority(static::DEFAULT_PRIORITY);
                $priceListToWebsite->setWebsite($website);
                $priceListToWebsite->setPriceList($priceList);
                $manager->persist($priceListToWebsite);
            }

            foreach ($priceListData['currencies'] as $currencyCode) {
                $priceList->addCurrencyByCode($currencyCode);
            }

            $manager->persist($priceList);
            $this->setReference($priceListData['reference'], $priceList);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups'
        ];
    }
}
