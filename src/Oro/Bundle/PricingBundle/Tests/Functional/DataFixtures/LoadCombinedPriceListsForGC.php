<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadCombinedPriceListsForGC extends AbstractCombinedPriceListsFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    protected $data = [
        [
            'name' => 'cpl_ws_f',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ]
            ],
        ],
        [
            'name' => 'cpl_ws',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [LoadWebsiteData::WEBSITE1 => 'cpl_ws_f'],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ]
            ],
        ],
        [
            'name' => 'cpl_ws_alt',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ]
            ],
        ],
        [
            'name' => 'cpl_cg_f',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ]
            ],
        ],
        [
            'name' => 'cpl_cg',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [LoadGroups::GROUP1 => 'cpl_cg_f'],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ]
            ],
        ],
        [
            'name' => 'cpl_c_f',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ]
            ],
        ],
        [
            'name' => 'cpl_c',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [LoadCustomers::CUSTOMER_LEVEL_1 => 'cpl_c_f'],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ]
            ],
        ],
        [
            'name' => 'cpl_conf_f',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ]
            ],
        ],
        [
            'name' => 'cpl_conf',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ]
            ],
        ],
        [
            'name' => 'cpl_conf_alt',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ]
            ],
        ],
        [
            'name' => 'cpl_unassigned',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ]
            ],
        ],
        [
            'name' => 'cpl_broken_ar_f',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ]
            ],
        ],
        [
            'name' => 'cpl_broken_ar',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ]
            ],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadPriceLists::class,
            LoadWebsiteData::class,
            LoadGroups::class,
            LoadCustomers::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        parent::load($manager);

        $this->createPriceListRelations($manager);
        $this->createActivationRule($manager, 'cpl_ws_f', 'cpl_ws_alt');
        $this->createActivationRule($manager, 'cpl_ws_f', 'cpl_ws');
        $this->createActivationRule($manager, 'cpl_conf_f', 'cpl_conf_alt');
        $this->createActivationRule($manager, 'cpl_conf_f', 'cpl_conf');
        $this->createActivationRule($manager, 'cpl_broken_ar_f', 'cpl_broken_ar');

        $manager->flush();
    }

    protected function loadCombinedPriceListToCustomer(
        ObjectManager $manager,
        array $priceListData,
        CombinedPriceList $combinedPriceList
    ) {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        foreach ($priceListData['priceListsToCustomers'] as $priceListsToCustomer => $fullCplReference) {
            /** @var Customer $customer */
            $customer = $this->getReference($priceListsToCustomer);

            $relation = new CombinedPriceListToCustomer();
            $relation->setCustomer($customer);
            $relation->setWebsite($website);

            /** @var CombinedPriceList $fullCpl */
            $fullCpl = $this->getReference($fullCplReference);
            $relation->setFullChainPriceList($fullCpl);
            $relation->setPriceList($combinedPriceList);

            $manager->persist($relation);
        }
    }

    protected function loadCombinedPriceListToCustomerGroup(
        ObjectManager $manager,
        array $priceListData,
        CombinedPriceList $combinedPriceList
    ) {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        foreach ($priceListData['priceListsToCustomerGroups'] as $priceListsToCustomerGroup => $fullCplReference) {
            /** @var CustomerGroup $customerGroup */
            $customerGroup = $this->getReference($priceListsToCustomerGroup);
            /** @var Website $website */
            $relation = new CombinedPriceListToCustomerGroup();
            $relation->setCustomerGroup($customerGroup);
            $relation->setWebsite($website);

            /** @var CombinedPriceList $fullCpl */
            $fullCpl = $this->getReference($fullCplReference);
            $relation->setFullChainPriceList($fullCpl);
            $relation->setPriceList($combinedPriceList);

            $manager->persist($relation);
        }
    }

    protected function loadCombinedPriceListToWebsite(
        ObjectManager $manager,
        array $priceListData,
        CombinedPriceList $combinedPriceList
    ) {
        foreach ($priceListData['websites'] as $websiteReference => $fullCplReference) {
            /** @var Website $website */
            $website = $this->getReference($websiteReference);

            $relation = new CombinedPriceListToWebsite();
            $relation->setWebsite($website);

            /** @var CombinedPriceList $fullCpl */
            $fullCpl = $this->getReference($fullCplReference);
            $relation->setFullChainPriceList($fullCpl);
            $relation->setPriceList($combinedPriceList);

            $manager->persist($relation);
        }
    }

    private function createActivationRule(ObjectManager $manager, string $fullCplReference, string $cplReference)
    {
        /** @var CombinedPriceList $fullCpl */
        $fullCpl = $this->getReference($fullCplReference);
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getReference($cplReference);
        $ar = new CombinedPriceListActivationRule();
        $ar->setFullChainPriceList($fullCpl);
        $ar->setCombinedPriceList($cpl);

        $manager->persist($ar);
    }

    private function createPriceListRelations(ObjectManager $manager)
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        /** @var Customer $customer */
        $customer = $this->getReference(LoadCustomers::CUSTOMER_LEVEL_1);
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference(LoadGroups::GROUP1);

        $pl2Customer = new PriceListToCustomer();
        $pl2Customer->setPriceList($priceList);
        $pl2Customer->setWebsite($website);
        $pl2Customer->setMergeAllowed(true);
        $pl2Customer->setSortOrder(1);
        $pl2Customer->setCustomer($customer);

        $manager->persist($pl2Customer);

        $pl2CustomerGroup = new PriceListToCustomerGroup();
        $pl2CustomerGroup->setPriceList($priceList);
        $pl2CustomerGroup->setWebsite($website);
        $pl2CustomerGroup->setMergeAllowed(true);
        $pl2CustomerGroup->setSortOrder(1);
        $pl2CustomerGroup->setCustomerGroup($customerGroup);

        $manager->persist($pl2CustomerGroup);

        $pl2Website = new PriceListToWebsite();
        $pl2Website->setPriceList($priceList);
        $pl2Website->setWebsite($website);
        $pl2Website->setMergeAllowed(true);
        $pl2Website->setSortOrder(1);

        $manager->persist($pl2Website);
    }
}
