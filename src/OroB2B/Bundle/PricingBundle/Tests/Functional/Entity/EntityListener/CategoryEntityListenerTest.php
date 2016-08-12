<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCategoryPriceRuleLexemes;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class CategoryEntityListenerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadCategoryPriceRuleLexemes::class,
            LoadPriceLists::class,
            LoadProductData::class,
            LoadCategoryProductData::class
        ]);
        $this->cleanTriggers();
    }

    public function testOnDelete()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->remove($this->getReference(LoadCategoryData::SECOND_LEVEL2));
        $em->flush();

        $actual = $this->getActualTriggersPriceListIds();
        $this->assertCount(3, $actual);
        $this->assertContains($this->getReference(LoadPriceLists::PRICE_LIST_1)->getId(), $actual);
        $this->assertContains($this->getReference(LoadPriceLists::PRICE_LIST_2)->getId(), $actual);
        $this->assertContains($this->getReference(LoadPriceLists::PRICE_LIST_3)->getId(), $actual);
    }

    public function testOnUpdateCategoryParentChanged()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::THIRD_LEVEL1);
        $category->setParentCategory($this->getReference(LoadCategoryData::FIRST_LEVEL));
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->flush();

        $actual = $this->getActualTriggersPriceListIds();
        $this->assertCount(3, $actual);
        $this->assertContains($this->getReference(LoadPriceLists::PRICE_LIST_1)->getId(), $actual);
        $this->assertContains($this->getReference(LoadPriceLists::PRICE_LIST_2)->getId(), $actual);
        $this->assertContains($this->getReference(LoadPriceLists::PRICE_LIST_3)->getId(), $actual);
    }

    public function testOnUpdateCategoryField()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::THIRD_LEVEL1);
        $category->setCreatedAt(new \DateTime());
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->flush();

        $actual = $this->getActualTriggersPriceListIds();
        $this->assertCount(1, $actual);
        $this->assertContains($this->getReference(LoadPriceLists::PRICE_LIST_3)->getId(), $actual);
    }

    public function testProductAdd()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_5);
        $category->addProduct($product);

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->flush();

        $expectedPriceLists = [
            $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId(),
            $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId()
        ];
        $triggers = $this->getTriggers();
        $this->assertCount(2, $triggers);
        foreach ($triggers as $trigger) {
            $this->assertEquals($product->getId(), $trigger->getProduct()->getId());
            $this->assertContains($trigger->getPriceList()->getId(), $expectedPriceLists);
        }
    }

    public function testProductRemove()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $product = $category->getProducts()->first();
        $category->removeProduct($product);

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->flush();

        $expectedPriceLists = [
            $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId(),
            $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId()
        ];
        $triggers = $this->getTriggers();
        $this->assertCount(2, $triggers);
        foreach ($triggers as $trigger) {
            $this->assertEquals($product->getId(), $trigger->getProduct()->getId());
            $this->assertContains($trigger->getPriceList()->getId(), $expectedPriceLists);
        }
    }

    /**
     * @return PriceList[]
     */
    protected function getActualTriggersPriceListIds()
    {
        return array_map(
            function (PriceRuleChangeTrigger $trigger) {
                return $trigger->getPriceList()->getId();
            },
            $this->getTriggers()
        );
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
