<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ProductEntityListenerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadProductData::class,
            LoadPriceRuleLexemes::class
        ]);
        $this->cleanTriggers();
    }

    public function testPreUpdate()
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Product::class);

        /** @var PriceList $expectedPriceList */
        $expectedPriceList = $this->getReference('price_list_1');
        /** @var Product $product */
        $product = $this->getReference('product.1');
        $this->assertNotEquals(Product::STATUS_DISABLED, $product->getStatus());
        $product->setStatus(Product::STATUS_DISABLED);
        $em->persist($product);
        $em->flush();

        $triggers = $this->getTriggers();
        $this->assertCount(1, $triggers);

        $trigger = $triggers[0];
        $this->assertNotEmpty($trigger->getProduct());
        $this->assertEquals($product->getId(), $trigger->getProduct()->getId());
        $this->assertEquals($trigger->getPriceList()->getId(), $expectedPriceList->getId());
    }

    public function testPostPersist()
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Product::class);

        $product = new Product();
        $product->setSku('TEST');

        $em->persist($product);
        $em->flush();

        $triggers = $this->getTriggers();
        $this->assertCount(1, $triggers);

        $trigger = $triggers[0];
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $this->assertEquals($priceList->getId(), $trigger->getPriceList()->getId());
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
