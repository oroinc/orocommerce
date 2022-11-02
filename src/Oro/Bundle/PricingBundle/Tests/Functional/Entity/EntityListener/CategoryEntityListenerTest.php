<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceListAssignedProductsTopic;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCategoryPriceRuleLexemes;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CategoryEntityListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadCategoryPriceRuleLexemes::class,
            LoadPriceLists::class,
            LoadProductData::class,
            LoadCategoryProductData::class
        ]);
        $this->enableMessageBuffering();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    public function testOnUpdateCategoryParentChanged()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::THIRD_LEVEL1);
        $category->setParentCategory($this->getReference(LoadCategoryData::FIRST_LEVEL));
        $this->getEntityManager()->flush();

        self::assertMessagesSent(
            ResolvePriceListAssignedProductsTopic::getName(),
            [
                [
                    'product' => [
                        $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId() => [],
                        $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId() => [],
                        $this->getReference(LoadPriceLists::PRICE_LIST_3)->getId() => [],
                    ]
                ],
            ]
        );
    }

    public function testOnUpdateCategoryField()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::THIRD_LEVEL1);
        $category->setCreatedAt(new \DateTime());
        $this->getEntityManager()->flush();

        self::assertMessagesSent(
            ResolvePriceListAssignedProductsTopic::getName(),
            [
                [
                    'product' => [
                        $this->getReference(LoadPriceLists::PRICE_LIST_3)->getId() => []
                    ]
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

        $this->getEntityManager()->flush();

        self::assertMessagesSent(
            ResolvePriceListAssignedProductsTopic::getName(),
            [
                [
                    'product' => [
                        $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId() => [
                            $product->getId()
                        ],
                        $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId() => [
                            $product->getId()
                        ]
                    ]
                ],
            ]
        );
    }

    public function testProductRemove()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $product = $category->getProducts()->first();
        $category->removeProduct($product);

        $this->getEntityManager()->flush();

        self::assertMessagesSent(
            ResolvePriceListAssignedProductsTopic::getName(),
            [
                [
                    'product' => [
                        $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId() => [
                            $product->getId()
                        ],
                        $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId() => [
                            $product->getId()
                        ]
                    ]
                ],
            ]
        );
    }

    public function testOnDelete()
    {
        $em = $this->getEntityManager();
        $em->remove($this->getReference(LoadCategoryData::SECOND_LEVEL2));
        $em->flush();

        self::assertMessagesSent(
            ResolvePriceListAssignedProductsTopic::getName(),
            [
                [
                    'product' => [
                        $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId() => [],
                        $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId() => [],
                        $this->getReference(LoadPriceLists::PRICE_LIST_3)->getId() => [],
                    ]
                ],
            ]
        );
    }
}
