<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use OroB2B\Bundle\PricingBundle\Async\Topics;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCategoryPriceRuleLexemes;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class CategoryEntityListenerTest extends WebTestCase
{
        use MessageQueueTrait;

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
                $this->topic = Topics::CALCULATE_RULE;
                $this->cleanQueueMessageTraces();
        }

        public function testOnDelete()
        {
                $em = $this->getContainer()->get('doctrine')->getManager();
                $em->remove($this->getReference(LoadCategoryData::SECOND_LEVEL2));
                $em->flush();

                $actual = $this->getActualScheduledPriceListIds();
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

                $actual = $this->getActualScheduledPriceListIds();
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

                $actual = $this->getActualScheduledPriceListIds();
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
                    $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId(),
                ];
                $traces = $this->getQueueMessageTraces();
                $this->assertCount(2, $traces);
                foreach ($traces as $trace) {
                        $this->assertEquals($product->getId(), $this->getProductIdFromTrace($trace));
                        $this->assertContains($this->getPriceListIdFromTrace($trace), $expectedPriceLists);
                }
        }

        public function testProductRemove()
        {
                $this->cleanQueueMessageTraces();
                $this->assertEquals([], $this->getQueueMessageTraces());

                /** @var Category $category */
                $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
                $product = $category->getProducts()->first();
                $category->removeProduct($product);

                $em = $this->getContainer()->get('doctrine')->getManager();
                $em->flush();

                $expectedPriceLists = [
                    $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId(),
                    $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId(),
                ];
                $traces = $this->getQueueMessageTraces();
                $this->assertCount(2, $traces);
                foreach ($traces as $trace) {
                        $this->assertEquals($product->getId(), $this->getProductIdFromTrace($trace));
                        $this->assertContains($this->getPriceListIdFromTrace($trace), $expectedPriceLists);
                }
        }

        /**
         * @return PriceList[]
         */
        protected function getActualScheduledPriceListIds()
        {
                return array_map(
                    function (array $trace) {
                            return $this->getPriceListIdFromTrace($trace);
                    },
                    $this->getQueueMessageTraces()
                );
        }
}
