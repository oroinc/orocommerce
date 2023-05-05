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
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadDuplicateCombinedProductPrices;
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
    }

    /**
     * @dataProvider cplRelationsDataProvider
     */
    public function testGetUnusedPriceListsIdsForRelation(string $relationClass, bool $hasFullChain = false)
    {
        $this->loadFixtures([
            LoadWebsiteData::class,
            LoadCustomers::class,
            LoadGroups::class
        ]);

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

        $helper = $this->getContainer()->get('oro_entity.orm.native_query_executor_helper');
        $combinedPriceListRepository->scheduleUnusedPriceListsRemoval($helper);
        $requestedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $priceListsForDelete = $combinedPriceListRepository->getPriceListsScheduledForRemoval($helper, $requestedAt);
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

    public function cplRelationsDataProvider(): \Generator
    {
        $relations = [
            CombinedPriceListToWebsite::class,
            CombinedPriceListToCustomerGroup::class,
            CombinedPriceListToCustomer::class
        ];

        foreach ($relations as $relation) {
            foreach ($this->trueFalseDataProvider() as $value) {
                yield [$relation, $value[0]];
            }
        }
    }

    /**
     * @dataProvider trueFalseDataProvider
     */
    public function testGetUnusedPriceListsIdsForActivationRule(bool $hasFullChain = false)
    {
        $this->loadFixtures([
            LoadWebsiteData::class,
            LoadCustomers::class,
            LoadGroups::class
        ]);

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

        $helper = $this->getContainer()->get('oro_entity.orm.native_query_executor_helper');
        $combinedPriceListRepository->scheduleUnusedPriceListsRemoval($helper);
        $requestedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $priceListsForDelete = $combinedPriceListRepository->getPriceListsScheduledForRemoval($helper, $requestedAt);
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

    public function testRemoveDuplicatePrices()
    {
        $this->loadFixtures([LoadDuplicateCombinedProductPrices::class]);
        $doctrine = $this->getContainer()->get('doctrine');

        /**
         * Check initial number of prices
         * @var CombinedProductPriceRepository $priceRepo
         */
        $priceRepo = $doctrine->getRepository(CombinedProductPrice::class);
        $this->assertCount(5, $priceRepo->findAll());
        $priceRepo->deleteDuplicatePrices();

        // Check that two duplicate records were removed
        $this->assertCount(3, $priceRepo->findAll());

        // Check that price for second product wasn't removed
        /** @var CombinedProductPrice $price2 */
        $price2 = $this->getReference('cpl_price.2');
        $this->assertNotNull($priceRepo->findOneBy(['id' => $price2->getId()]));

        // Check that price for another CPL wasn't removed
        /** @var CombinedProductPrice $price3 */
        $price3 = $this->getReference('cpl_price.3');
        $this->assertNotNull($priceRepo->findOneBy(['id' => $price3->getId()]));
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

    protected function createCombinedPriceList(string $name): CombinedPriceList
    {
        $cpl = new CombinedPriceList();
        $cpl->setEnabled(true);
        $cpl->setName($name);

        return $cpl;
    }
}
