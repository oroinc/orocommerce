<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToAccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadCombinedPriceLists extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'name' => '1t_2t_3t',
            'enabled' => true,
            'priceListsToAccounts' => [],
            'priceListsToAccountGroups' => [
                [
                    'group' => 'account_group.group1',
                    'website' => LoadWebsiteData::WEBSITE1,
                ],
            ],
            'websites' => [LoadWebsiteData::WEBSITE1],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => 'price_list_2',
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => 'price_list_3',
                    'mergeAllowed' => true,
                ],
            ],
        ],
        [
            'name' => '2t_3f_1t',
            'enabled' => true,
            'priceListsToAccounts' => [
                [
                    'account' => 'account.level_1.2',
                    'website' => LoadWebsiteData::WEBSITE1,
                ]
            ],
            'priceListsToAccountGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_2',
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => 'price_list_3',
                    'mergeAllowed' => false,
                ],
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ],
            ],
        ],
        [
            'name' => '2f_1t_3t',
            'enabled' => true,
            'priceListsToAccounts' => [
                [
                    'account' => 'account.level_1.2',
                    'website' => LoadWebsiteData::WEBSITE2,
                ]
            ],
            'priceListsToAccountGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_2',
                    'mergeAllowed' => false,
                ],
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => 'price_list_3',
                    'mergeAllowed' => true,
                ],
            ],
        ],
        [
            'name' => '1f',
            'enabled' => true,
            'priceListsToAccounts' => [],
            'priceListsToAccountGroups' => [],
            'websites' => ['default'],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => false,
                ],
            ],
        ],
        [
            'name' => '2f',
            'enabled' => true,
            'priceListsToAccounts' => [],
            'priceListsToAccountGroups' => [],
            'websites' => [LoadWebsiteData::WEBSITE2],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_2',
                    'mergeAllowed' => false,
                ],
            ],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $now = new \DateTime();

        foreach ($this->data as $priceListData) {
            $combinedPriceList = new CombinedPriceList();
            $combinedPriceList->setPricesCalculated(true);
            $combinedPriceList
                ->setName(md5($priceListData['name']))
                ->setCreatedAt($now)
                ->setUpdatedAt($now)
                ->setEnabled($priceListData['enabled']);

            $this->loadCombinedPriceListToPriceList($manager, $priceListData, $combinedPriceList);
            $this->loadCombinedPriceListToAccount($manager, $priceListData, $combinedPriceList);
            $this->loadCombinedPriceListToAccountGroup($manager, $priceListData, $combinedPriceList);
            $this->loadCombinedPriceListToWebsite($manager, $priceListData, $combinedPriceList);

            $manager->persist($combinedPriceList);
            $this->setReference($priceListData['name'], $combinedPriceList);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists',
            'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
        ];
    }

    /**
     * @param ObjectManager $manager
     * @param array $priceListData
     * @param CombinedPriceList $combinedPriceList
     */
    protected function loadCombinedPriceListToPriceList(
        ObjectManager $manager,
        array $priceListData,
        CombinedPriceList $combinedPriceList
    ) {
        $currencies = [];
        for ($i = 0; $i < count($priceListData['priceListRelations']); $i++) {
            $priceListRelation = $priceListData['priceListRelations'][$i];
            /** @var PriceList $priceList */
            $priceList = $this->getReference($priceListRelation['priceList']);
            $currencies = array_merge($currencies, $priceList->getCurrencies());

            $relation = new CombinedPriceListToPriceList();
            $relation->setCombinedPriceList($combinedPriceList);
            $relation->setPriceList($priceList);
            $relation->setMergeAllowed($priceListRelation['mergeAllowed']);
            $relation->setSortOrder($i);

            $manager->persist($relation);
        }

        $currencies = array_unique($currencies);
        $combinedPriceList->setCurrencies($currencies);
    }

    /**
     * @param ObjectManager $manager
     * @param array $priceListData
     * @param CombinedPriceList $combinedPriceList
     */
    protected function loadCombinedPriceListToAccount(
        ObjectManager $manager,
        array $priceListData,
        CombinedPriceList $combinedPriceList
    ) {
        foreach ($priceListData['priceListsToAccounts'] as $priceListsToAccount) {
            /** @var Account $account */
            $account = $this->getReference($priceListsToAccount['account']);
            /** @var Website $website */
            $website = $this->getReference($priceListsToAccount['website']);

            $priceListToAccount = new CombinedPriceListToAccount();
            $priceListToAccount->setAccount($account);
            $priceListToAccount->setWebsite($website);
            $priceListToAccount->setPriceList($combinedPriceList);
            $manager->persist($priceListToAccount);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param array $priceListData
     * @param CombinedPriceList $combinedPriceList
     */
    protected function loadCombinedPriceListToAccountGroup(
        ObjectManager $manager,
        array $priceListData,
        CombinedPriceList $combinedPriceList
    ) {
        foreach ($priceListData['priceListsToAccountGroups'] as $priceListsToAccountGroup) {
            /** @var AccountGroup $accountGroup */
            $accountGroup = $this->getReference($priceListsToAccountGroup['group']);
            /** @var Website $website */
            $website = $this->getReference($priceListsToAccountGroup['website']);

            $priceListToAccountGroup = new CombinedPriceListToAccountGroup();
            $priceListToAccountGroup->setAccountGroup($accountGroup);
            $priceListToAccountGroup->setWebsite($website);
            $priceListToAccountGroup->setPriceList($combinedPriceList);
            $manager->persist($priceListToAccountGroup);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param array $priceListData
     * @param CombinedPriceList $combinedPriceList
     */
    protected function loadCombinedPriceListToWebsite(
        ObjectManager $manager,
        array $priceListData,
        CombinedPriceList $combinedPriceList
    ) {
        $websiteRepository = $manager->getRepository('OroB2BWebsiteBundle:Website');
        foreach ($priceListData['websites'] as $websiteReference) {
            if ($websiteReference === 'default') {
                /** @var Website $website */
                $website = $websiteRepository->find(1);
            } else {
                /** @var Website $website */
                $website = $this->getReference($websiteReference);
            }

            $priceListToWebsite = new CombinedPriceListToWebsite();
            $priceListToWebsite->setWebsite($website);
            $priceListToWebsite->setPriceList($combinedPriceList);
            $manager->persist($priceListToWebsite);
        }
    }
}
