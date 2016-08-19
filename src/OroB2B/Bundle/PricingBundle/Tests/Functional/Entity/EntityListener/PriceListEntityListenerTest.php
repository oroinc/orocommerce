<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;

/**
 * @dbIsolation
 */
class PriceListEntityListenerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadProductPrices::class
        ]);
    }

    public function testPreRemove()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repository = $em->getRepository('OroB2BPricingBundle:PriceListChangeTrigger');

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $em->remove($priceList);
        $em->flush();

        $actual = $repository->findBy(['force' => true]);

        $this->assertCount(1, $actual);
    }

    public function testPreUpdate()
    {
        $this->cleanTriggers();

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_2');
        $priceList->setProductAssignmentRule('product.id > 10');
        $em->persist($priceList);
        $em->flush();

        /** @var PriceRuleChangeTrigger[] $triggers */
        $triggers = $em->getRepository(PriceRuleChangeTrigger::class)->findAll();
        $this->assertCount(1, $triggers);
        $this->assertEquals($priceList->getId(), $triggers[0]->getPriceList()->getId());
    }

    public function testPreUpdateAssignmentNotChanged()
    {
        $this->cleanTriggers();

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_2');
        $priceList->setName('TEST123');
        $em->persist($priceList);
        $em->flush();

        /** @var PriceRuleChangeTrigger[] $triggers */
        $triggers = $em->getRepository(PriceRuleChangeTrigger::class)->findAll();
        $this->assertEmpty($triggers);
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
}
