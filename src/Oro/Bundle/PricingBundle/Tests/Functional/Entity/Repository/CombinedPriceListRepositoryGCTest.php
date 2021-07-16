<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\PricingBundle\Entity\BaseCombinedPriceListRelation;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolationPerTest
 */
class CombinedPriceListRepositoryGCTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadWebsiteData::class,
            LoadCustomers::class,
            LoadGroups::class
        ]);
    }

    /**
     * @dataProvider cplRelationsDataProvider
     * @param string $relationClass
     * @param bool $hasFullChain
     */
    public function testGetUnusedPriceListsIdsForRelation($relationClass, $hasFullChain = false)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')
            ->getManagerForClass(CombinedPriceList::class);

        $relation = $this->createCombinedPriceListRelation($relationClass);
        $em->persist($relation);

        $notAssignedCPL = $this->createCombinedPriceList('not_assigned');
        $em->persist($notAssignedCPL);

        $assignedCPL = $this->createCombinedPriceList('assigned');
        $relation->setPriceList($assignedCPL);
        $em->persist($assignedCPL);

        $fullChainCPL = null;
        if ($hasFullChain) {
            $fullChainCPL = $this->createCombinedPriceList('full_chain');
            $relation->setFullChainPriceList($fullChainCPL);
            $em->persist($fullChainCPL);
        }
        $em->flush();

        /** @var CombinedPriceListRepository $combinedPriceListRepository */
        $combinedPriceListRepository = $em->getRepository(CombinedPriceList::class);

        $priceListsForDelete = $combinedPriceListRepository->getUnusedPriceListsIds();
        static::assertContainsEquals(
            $notAssignedCPL->getId(),
            $priceListsForDelete,
            \var_export($priceListsForDelete, true)
        );
        static::assertNotContainsEquals(
            $assignedCPL->getId(),
            $priceListsForDelete,
            \var_export($priceListsForDelete, true)
        );
        if ($fullChainCPL) {
            static::assertNotContainsEquals(
                $fullChainCPL->getId(),
                $priceListsForDelete,
                \var_export($priceListsForDelete, true)
            );
        }
    }

    /**
     * @return \Generator
     */
    public function cplRelationsDataProvider()
    {
        $relations = [
            CombinedPriceListToWebsite::class,
            CombinedPriceListToCustomerGroup::class,
            CombinedPriceListToCustomer::class
        ];

        foreach ($relations as $relation) {
            foreach ($this->trueFalseDataProvider() as $value) {
                yield [$relation, $value];
            }
        }
    }

    /**
     * @dataProvider trueFalseDataProvider
     * @param bool $hasFullChain
     */
    public function testGetUnusedPriceListsIdsForActivationRule($hasFullChain = false)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')
            ->getManagerForClass(CombinedPriceList::class);

        $activationRule = new CombinedPriceListActivationRule();
        $activationRule->setActive(true);
        $em->persist($activationRule);

        $notAssignedCPL = $this->createCombinedPriceList('not_assigned');
        $em->persist($notAssignedCPL);

        $assignedCPL = $this->createCombinedPriceList('assigned');
        $activationRule->setCombinedPriceList($assignedCPL);
        $em->persist($assignedCPL);

        $fullChainCPL = null;
        if ($hasFullChain) {
            $fullChainCPL = $this->createCombinedPriceList('full_chain');
            $activationRule->setFullChainPriceList($fullChainCPL);
            $em->persist($fullChainCPL);
        }
        $em->flush();

        /** @var CombinedPriceListRepository $combinedPriceListRepository */
        $combinedPriceListRepository = $em->getRepository(CombinedPriceList::class);

        $priceListsForDelete = $combinedPriceListRepository->getUnusedPriceListsIds();
        static::assertContainsEquals(
            $notAssignedCPL->getId(),
            $priceListsForDelete,
            \var_export($priceListsForDelete, true)
        );
        static::assertNotContainsEquals(
            $assignedCPL->getId(),
            $priceListsForDelete,
            \var_export($priceListsForDelete, true)
        );
        if ($fullChainCPL) {
            static::assertNotContainsEquals(
                $fullChainCPL->getId(),
                $priceListsForDelete,
                \var_export($priceListsForDelete, true)
            );
        }
    }

    public function trueFalseDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * @param string $relationClass
     * @return BaseCombinedPriceListRelation
     */
    protected function createCombinedPriceListRelation($relationClass): BaseCombinedPriceListRelation
    {
        /** @var BaseCombinedPriceListRelation $relation */
        $relation = new $relationClass();
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $relation->setWebsite($website);
        if ($relation instanceof CombinedPriceListToCustomer) {
            /** @var Customer $customer */
            $customer = $this->getReference(LoadCustomers::CUSTOMER_LEVEL_1_1);
            $relation->setCustomer($customer);
        }
        if ($relation instanceof CombinedPriceListToCustomerGroup) {
            /** @var CustomerGroup $group */
            $group = $this->getReference(LoadGroups::GROUP1);
            $relation->setCustomerGroup($group);
        }

        return $relation;
    }

    /**
     * @param string $name
     * @return CombinedPriceList
     */
    protected function createCombinedPriceList($name): CombinedPriceList
    {
        $cpl = new CombinedPriceList();
        $cpl->setEnabled(true);
        $cpl->setName($name);

        return $cpl;
    }
}
