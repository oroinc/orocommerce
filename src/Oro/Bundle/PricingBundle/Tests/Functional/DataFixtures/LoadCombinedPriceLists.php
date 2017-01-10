<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadCombinedPriceLists extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'name' => '1t_2t_3t',
            'enabled' => true,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [
                [
                    'group' => 'customer_group.group1',
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
            'priceListsToCustomers' => [
                [
                    'customer' => 'customer.level_1.2',
                    'website' => LoadWebsiteData::WEBSITE1,
                ]
            ],
            'priceListsToCustomerGroups' => [],
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
            'priceListsToCustomers' => [
                [
                    'customer' => 'customer.level_1.2',
                    'website' => LoadWebsiteData::WEBSITE2,
                ]
            ],
            'priceListsToCustomerGroups' => [],
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
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
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
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
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
            $this->loadCombinedPriceListToCustomer($manager, $priceListData, $combinedPriceList);
            $this->loadCombinedPriceListToCustomerGroup($manager, $priceListData, $combinedPriceList);
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
            'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists',
            'Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
            'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers',
            'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups',
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
    protected function loadCombinedPriceListToCustomer(
        ObjectManager $manager,
        array $priceListData,
        CombinedPriceList $combinedPriceList
    ) {
        foreach ($priceListData['priceListsToCustomers'] as $priceListsToCustomer) {
            /** @var Customer $customer */
            $customer = $this->getReference($priceListsToCustomer['customer']);
            /** @var Website $website */
            $website = $this->getReference($priceListsToCustomer['website']);

            $priceListToCustomer = new CombinedPriceListToCustomer();
            $priceListToCustomer->setCustomer($customer);
            $priceListToCustomer->setWebsite($website);
            $priceListToCustomer->setPriceList($combinedPriceList);
            $manager->persist($priceListToCustomer);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param array $priceListData
     * @param CombinedPriceList $combinedPriceList
     */
    protected function loadCombinedPriceListToCustomerGroup(
        ObjectManager $manager,
        array $priceListData,
        CombinedPriceList $combinedPriceList
    ) {
        foreach ($priceListData['priceListsToCustomerGroups'] as $priceListsToCustomerGroup) {
            /** @var CustomerGroup $customerGroup */
            $customerGroup = $this->getReference($priceListsToCustomerGroup['group']);
            /** @var Website $website */
            $website = $this->getReference($priceListsToCustomerGroup['website']);

            $priceListToCustomerGroup = new CombinedPriceListToCustomerGroup();
            $priceListToCustomerGroup->setCustomerGroup($customerGroup);
            $priceListToCustomerGroup->setWebsite($website);
            $priceListToCustomerGroup->setPriceList($combinedPriceList);
            $manager->persist($priceListToCustomerGroup);
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
        $websiteRepository = $manager->getRepository('OroWebsiteBundle:Website');
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
