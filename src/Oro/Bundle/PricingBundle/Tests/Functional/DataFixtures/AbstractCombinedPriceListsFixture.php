<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\WebsiteBundle\Entity\Website;

abstract class AbstractCombinedPriceListsFixture extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $priceListData) {
            $combinedPriceList = $this->createCombinedPriceList($priceListData, $manager);
            $this->loadCombinedPriceListToPriceList($manager, $priceListData, $combinedPriceList);
            $this->loadCombinedPriceListToCustomer($manager, $priceListData, $combinedPriceList);
            $this->loadCombinedPriceListToCustomerGroup($manager, $priceListData, $combinedPriceList);
            $this->loadCombinedPriceListToWebsite($manager, $priceListData, $combinedPriceList);

            $this->setReference($priceListData['name'], $combinedPriceList);
        }

        $manager->flush();
    }

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

    protected function loadCombinedPriceListToWebsite(
        ObjectManager $manager,
        array $priceListData,
        CombinedPriceList $combinedPriceList
    ) {
        $websiteRepository = $manager->getRepository(Website::class);
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

    protected function createCombinedPriceList(array $priceListData, ObjectManager $manager): CombinedPriceList
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $combinedPriceList = new CombinedPriceList();
        $combinedPriceList
            ->setPricesCalculated($priceListData['calculated'])
            ->setName(md5($priceListData['name']))
            ->setCreatedAt($now)
            ->setUpdatedAt($now)
            ->setEnabled($priceListData['enabled']);

        $manager->persist($combinedPriceList);

        return $combinedPriceList;
    }
}
