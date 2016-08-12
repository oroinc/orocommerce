<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRules;

/**
 * @dbIsolation
 */
class PriceRuleEntityListenerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadPriceRules::class
        ]);
        $this->cleanTriggers();
    }

    public function testPreUpdate()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(PriceRule::class);

        /** @var PriceRule $rule */
        $rule = $this->getReference(LoadPriceRules::PRICE_RULE_1);
        $rule->setRuleCondition('product.id > 42');
        $em->persist($rule);
        $em->flush();

        $triggers = $this->getTriggers();
        $this->assertCount(1, $triggers);

        $trigger = $triggers[0];
        $this->assertEquals($trigger->getPriceList()->getId(), $rule->getPriceList()->getId());
    }

    public function testPreRemove()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(PriceRule::class);

        /** @var PriceRule $rule */
        $rule = $this->getReference(LoadPriceRules::PRICE_RULE_1);
        $em->remove($rule);
        $em->flush();

        $triggers = $this->getTriggers();
        $this->assertCount(1, $triggers);

        $trigger = $triggers[0];
        $this->assertEquals($trigger->getPriceList()->getId(), $rule->getPriceList()->getId());
    }

    protected function cleanTriggers()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(PriceRuleChangeTrigger::class);
        $em->createQueryBuilder()
            ->delete(PriceRuleChangeTrigger::class)
            ->getQuery()
            ->execute();
    }

    /**
     * @return PriceRuleChangeTrigger[]
     */
    protected function getTriggers()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass(PriceRuleChangeTrigger::class)
            ->getRepository(PriceRuleChangeTrigger::class)
            ->findAll();
    }
}
