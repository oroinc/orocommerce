<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceRuleChangeTriggerRepository;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRules;

/**
 * @dbIsolation
 */
class PriceRuleChangeTriggerRepositoryTest extends WebTestCase
{
    /**
     * @var PriceRuleChangeTriggerRepository
     */
    protected $repository;

    /**
     * @var EntityManager
     */
    protected $manager;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadPriceRules::class]);

        $this->manager = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(PriceRuleChangeTrigger::class);

        $this->repository = $this->manager->getRepository(PriceRuleChangeTrigger::class);
    }

    public function testDeleteAll()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $trigger = new PriceRuleChangeTrigger($priceList);
        $this->manager->persist($trigger);
        $this->manager->flush($trigger);

        $triggers = $this->repository->findAll();
        $this->assertNotEmpty($triggers);
        $this->repository->deleteAll();
        $triggers = $this->repository->findAll();
        $this->assertEmpty($triggers);
    }

    public function testGetTriggersIterator()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $trigger = new PriceRuleChangeTrigger($priceList);
        $this->manager->persist($trigger);
        $this->manager->flush($trigger);

        $qb = $this->repository->createQueryBuilder('trigger');
        $qb->select('COUNT(trigger)');
        $countExpected = $qb->getQuery()->getSingleScalarResult();
        $iterator = $this->repository->getTriggersIterator();

        $this->assertCount((int)$countExpected, $iterator);
    }
}
