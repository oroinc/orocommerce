<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributeProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class PriceAttributeProductPriceEntityListenerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadPriceAttributeProductPrices::class,
            LoadPriceRuleLexemes::class
        ]);
        $this->cleanTriggers();
    }

    public function testPostPersist()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Product::class);

        /** @var PriceAttributePriceList $priceAttribute */
        $priceAttribute = $this->getReference('price_attribute_price_list_1');
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_4);

        $price = new PriceAttributeProductPrice();
        $price->setProduct($product)
            ->setPriceList($priceAttribute)
            ->setQuantity(1)
            ->setUnit($this->getReference('product_unit.box'))
            ->setPrice(Price::create(42, 'USD'));

        $em->persist($price);
        $em->flush();

        $triggers = $this->getTriggers();
        $this->assertCount(1, $triggers);

        $trigger = $triggers[0];
        $this->assertNotEmpty($trigger->getProduct());
        $this->assertEquals($product->getId(), $trigger->getProduct()->getId());

        /** @var PriceList $expectedPriceList */
        $expectedPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $this->assertEquals($trigger->getPriceList()->getId(), $expectedPriceList->getId());
    }

    public function testPreUpdate()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Product::class);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        /** @var PriceAttributeProductPrice $price */
        $price = $this->getReference('price_attribute_product_price.1');
        $price->setPrice(Price::create(1000, 'USD'));

        $em->persist($price);
        $em->flush();

        $triggers = $this->getTriggers();
        $this->assertCount(1, $triggers);

        $trigger = $triggers[0];
        $this->assertNotEmpty($trigger->getProduct());
        $this->assertEquals($product->getId(), $trigger->getProduct()->getId());

        /** @var PriceList $expectedPriceList */
        $expectedPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $this->assertEquals($trigger->getPriceList()->getId(), $expectedPriceList->getId());
    }

    public function testPreRemove()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Product::class);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        /** @var PriceAttributeProductPrice $price */
        $price = $this->getReference('price_attribute_product_price.1');
        $em->remove($price);
        $em->flush();

        $triggers = $this->getTriggers();
        $this->assertCount(1, $triggers);

        $trigger = $triggers[0];
        $this->assertNotEmpty($trigger->getProduct());
        $this->assertEquals($product->getId(), $trigger->getProduct()->getId());

        /** @var PriceList $expectedPriceList */
        $expectedPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $this->assertEquals($trigger->getPriceList()->getId(), $expectedPriceList->getId());
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
