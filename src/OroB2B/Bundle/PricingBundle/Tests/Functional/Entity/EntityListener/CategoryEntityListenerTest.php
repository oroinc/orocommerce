<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCategoryPriceRuleLexemes;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
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
            LoadProductData::class
        ]);
        $this->removeTriggers();
    }

    public function testOnDelete()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->remove($this->getReference(LoadCategoryData::SECOND_LEVEL2));
        $em->flush();

        $actual = $this->getActualTriggersPriceLists();
        $this->assertCount(3, $this->getActualTriggersPriceLists());
        $this->assertContains($this->getReference(LoadPriceLists::PRICE_LIST_1), $actual);
        $this->assertContains($this->getReference(LoadPriceLists::PRICE_LIST_2), $actual);
        $this->assertContains($this->getReference(LoadPriceLists::PRICE_LIST_3), $actual);
    }

    public function testOnUpdateCategoryParentChanged()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::THIRD_LEVEL1);
        $category->setParentCategory($this->getReference(LoadCategoryData::FIRST_LEVEL));
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->flush();

        $actual = $this->getActualTriggersPriceLists();
        $this->assertCount(3, $actual);
        $this->assertContains($this->getReference(LoadPriceLists::PRICE_LIST_1), $actual);
        $this->assertContains($this->getReference(LoadPriceLists::PRICE_LIST_2), $actual);
        $this->assertContains($this->getReference(LoadPriceLists::PRICE_LIST_3), $actual);
    }

    public function testOnUpdateCategoryField()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::THIRD_LEVEL1);
        $category->setCreatedAt(new \DateTime());
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->flush();

        $actual = $this->getActualTriggersPriceLists();
        $this->assertCount(1, $actual);
        $this->assertContains($this->getReference(LoadPriceLists::PRICE_LIST_3), $actual);
    }

    /**
     * @return PriceList[]
     */
    protected function getActualTriggersPriceLists()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        /** @var CategoryRepository $repository */
        $repository = $em->getRepository(PriceRuleChangeTrigger::class);
        return array_map(
            function (PriceRuleChangeTrigger $trigger) {
                return $trigger->getPriceList();
            },
            $repository->findAll()
        );
    }

    /**
     * @return PriceList[]
     */
    protected function removeTriggers()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        /** @var EntityRepository $repository */
        $repository = $em->getRepository(PriceRuleChangeTrigger::class);
        $repository->createQueryBuilder('t')
            ->delete()
            ->getQuery()
            ->execute();
    }
}
