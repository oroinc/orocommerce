<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCategoryPriceRuleLexemes;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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
        $this->initClient();
        $this->loadFixtures([
            LoadCategoryPriceRuleLexemes::class,
            LoadPriceLists::class,
            LoadProductData::class,
            LoadCategoryProductData::class
        ]);
        $this->cleanScheduledMessages();
    }

    public function testOnDelete()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->remove($this->getReference(LoadCategoryData::SECOND_LEVEL2));
        $em->flush();

        $this->sendScheduledMessages();

        self::assertMessagesSent(
            Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
            [
                [
                    PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId(),
                    PriceListTriggerFactory::PRODUCT => null
                ],
                [
                    PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId(),
                    PriceListTriggerFactory::PRODUCT => null
                ],
                [
                    PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_3)->getId(),
                    PriceListTriggerFactory::PRODUCT => null
                ],
            ]
        );
    }

    public function testOnUpdateCategoryParentChanged()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::THIRD_LEVEL1);
        $category->setParentCategory($this->getReference(LoadCategoryData::FIRST_LEVEL));
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->flush();

        $this->sendScheduledMessages();

        self::assertMessagesSent(
            Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
            [
                [
                    PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId(),
                    PriceListTriggerFactory::PRODUCT => null
                ],
                [
                    PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId(),
                    PriceListTriggerFactory::PRODUCT => null
                ],
                [
                    PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_3)->getId(),
                    PriceListTriggerFactory::PRODUCT => null
                ],
            ]
        );
    }

    public function testOnUpdateCategoryField()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::THIRD_LEVEL1);
        $category->setCreatedAt(new \DateTime());
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->flush();

        $this->sendScheduledMessages();

        self::assertMessagesSent(
            Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
            [
                [
                    PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_3)->getId(),
                    PriceListTriggerFactory::PRODUCT => null
                ],
            ]
        );
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

        $this->sendScheduledMessages();

        self::assertMessagesSent(
            Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
            [
                [
                    PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId(),
                    PriceListTriggerFactory::PRODUCT => $product->getId()
                ],
                [
                    PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId(),
                    PriceListTriggerFactory::PRODUCT => $product->getId()
                ],
            ]
        );
    }

    public function testProductRemove()
    {
        $this->cleanScheduledMessages();

        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $product = $category->getProducts()->first();
        $category->removeProduct($product);

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->flush();

        $this->sendScheduledMessages();

        self::assertMessagesSent(
            Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
            [
                [
                    PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId(),
                    PriceListTriggerFactory::PRODUCT => $product->getId()
                ],
                [
                    PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId(),
                    PriceListTriggerFactory::PRODUCT => $product->getId()
                ],
            ]
        );
    }
}
