<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRules;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PriceRuleEntityListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadPriceRules::class]);
        $this->enableMessageBuffering();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(PriceRule::class);
    }

    public function testPreUpdate()
    {
        /** @var PriceRule $rule */
        $rule = $this->getReference(LoadPriceRules::PRICE_RULE_1);
        $rule->setRuleCondition('product.id > 42');

        $em = $this->getEntityManager();
        $em->persist($rule);
        $em->flush();

        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [$rule->getPriceList()->getId() => []]
            ]
        );
    }

    public function testPreRemove()
    {
        /** @var PriceRule $rule */
        $rule = $this->getReference(LoadPriceRules::PRICE_RULE_1);

        $em = $this->getEntityManager();
        $em->remove($rule);
        $em->flush();

        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [$rule->getPriceList()->getId() => []]
            ]
        );
    }
}
