<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsite;

class LoadCombinedPriceListWithCustomerRelation extends AbstractFixture implements DependentFixtureInterface
{
    public const DEFAULT_PRICE_LIST = 'default_price_list';

    public function getDependencies(): array
    {
        return [LoadCustomer::class, LoadWebsite::class];
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadCombinedPriceListWithDefaultPriceListAndCustomerRelation($manager);
        $manager->flush();
    }

    private function loadCombinedPriceListWithDefaultPriceListAndCustomerRelation(ObjectManager $manager): void
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer');
        /** @var Website $website */
        $website = $this->getReference('website');
        /** @var PriceList $priceList */
        $priceList = $this->getFirstPriceList($manager);

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $combinedPriceList = new CombinedPriceList();
        $combinedPriceList
            ->setPricesCalculated(false)
            ->setName(md5($priceList->getName()))
            ->setCurrencies($priceList->getCurrencies())
            ->setCreatedAt($now)
            ->setUpdatedAt($now)
            ->setEnabled(true);
        $manager->persist($combinedPriceList);

        $relation = new CombinedPriceListToPriceList();
        $relation->setCombinedPriceList($combinedPriceList);
        $relation->setPriceList($priceList);
        $relation->setMergeAllowed(false);
        $relation->setSortOrder(1);
        $manager->persist($relation);

        $priceListToCustomer = new CombinedPriceListToCustomer();
        $priceListToCustomer->setCustomer($customer);
        $priceListToCustomer->setWebsite($website);
        $priceListToCustomer->setPriceList($combinedPriceList);
        $manager->persist($priceListToCustomer);

        $this->setReference(self::DEFAULT_PRICE_LIST, $priceList);
    }

    private function getFirstPriceList(ObjectManager $manager): PriceList
    {
        return $manager->getRepository(PriceList::class)
            ->createQueryBuilder('p')
            ->orderBy('p.id')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleResult();
    }
}
