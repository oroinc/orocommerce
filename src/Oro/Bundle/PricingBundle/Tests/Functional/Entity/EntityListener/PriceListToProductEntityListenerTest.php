<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceListAssignedProductsTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Event\PriceListToProductSaveAfterEvent;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class PriceListToProductEntityListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();

        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.product_price_cpl');
        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.price_list_to_product');

        $this->loadFixtures([
            LoadProductPrices::class,
            LoadPriceRuleLexemes::class
        ]);
        $this->enableMessageBuffering();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(PriceListToProduct::class);
    }

    protected function disableListener()
    {
        $this->getContainer()->get('oro_pricing.entity_listener.price_list_to_product')->setEnabled(false);
    }

    public function testPostPersist()
    {
        $priceListToProduct = new PriceListToProduct();
        $product = $this->getReference(LoadProductData::PRODUCT_8);
        $priceListToProduct->setProduct($product);
        $priceListToProduct->setPriceList($this->getReference(LoadPriceLists::PRICE_LIST_1));

        $em = $this->getEntityManager();
        $em->persist($priceListToProduct);
        $em->flush();

        // Assert Rules scheduled for rebuild
        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [
                    $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId() => [
                        $product->getId()
                    ]
                ]
            ]
        );

        // Assert Dependent price lists scheduled for recalculation
        self::assertMessageSent(
            ResolvePriceListAssignedProductsTopic::getName(),
            [
                'product' => [
                    $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId() => [
                        $product->getId()
                    ]
                ]
            ]
        );
    }

    public function testOnPriceListToProductSave()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_8);

        $priceListToProduct = new PriceListToProduct();
        $priceListToProduct->setProduct($product);
        $priceListToProduct->setPriceList($this->getReference(LoadPriceLists::PRICE_LIST_1));

        $dispatcher = $this->getContainer()->get('event_dispatcher');
        $dispatcher->dispatch(
            new PriceListToProductSaveAfterEvent($priceListToProduct),
            PriceListToProductSaveAfterEvent::NAME
        );

        $this->flushMessagesBuffer();

        // Assert Rules scheduled for rebuild
        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [
                    $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId() => [
                        $product->getId()
                    ]
                ]
            ]
        );

        // Assert Dependent price lists scheduled for recalculation
        self::assertMessageSent(
            ResolvePriceListAssignedProductsTopic::getName(),
            [
                'product' => [
                    $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId() => [
                        $product->getId()
                    ]
                ]
            ]
        );
    }

    public function testPostPersistWidthDisabledListener()
    {
        $this->disableListener();

        $priceListToProduct = new PriceListToProduct();
        $product = $this->getReference(LoadProductData::PRODUCT_8);
        $priceListToProduct->setProduct($product);
        $priceListToProduct->setPriceList($this->getReference(LoadPriceLists::PRICE_LIST_1));

        $em = $this->getEntityManager();
        $em->persist($priceListToProduct);
        $em->flush();

        $this->assertMessagesEmpty(ResolvePriceRulesTopic::getName());
    }

    public function testPreUpdate()
    {
        // Create PriceListToProduct
        $priceListToProduct = new PriceListToProduct();
        $product = $this->getReference(LoadProductData::PRODUCT_7);
        $priceListToProduct->setProduct($product);
        $priceListToProduct->setPriceList($this->getReference(LoadPriceLists::PRICE_LIST_1));

        $em = $this->getEntityManager();
        $em->persist($priceListToProduct);
        $em->flush();

        $this->clearMessageCollector();

        // Edit PriceListToProduct
        $changedProduct = $this->getReference(LoadProductData::PRODUCT_6);
        $priceListToProduct->setProduct($changedProduct);

        $em->persist($priceListToProduct);
        $em->flush();

        self::assertMessagesSent(
            ResolvePriceRulesTopic::getName(),
            [
                // Recalculation for old product
                // Recalculation for new product
                [
                    'product' => [
                        $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId() => [
                            $product->getId(),
                            $changedProduct->getId()
                        ]
                    ]
                ],
            ]
        );

        // Assert Dependent price lists scheduled for recalculation for old product
        self::assertMessagesSent(
            ResolvePriceListAssignedProductsTopic::getName(),
            [
                [
                    'product' => [
                        $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId() => [
                            $product->getId(),
                            $changedProduct->getId()
                        ]
                    ]
                ],
            ]
        );
    }

    public function testPreUpdateWithDisabledListener()
    {
        // Create PriceListToProduct
        $priceListToProduct = new PriceListToProduct();
        $product = $this->getReference(LoadProductData::PRODUCT_7);
        $priceListToProduct->setProduct($product);
        $priceListToProduct->setPriceList($this->getReference(LoadPriceLists::PRICE_LIST_1));

        $em = $this->getEntityManager();
        $em->persist($priceListToProduct);
        $em->flush();

        $this->clearMessageCollector();

        $this->disableListener();

        // Edit PriceListToProduct
        $changedProduct = $this->getReference(LoadProductData::PRODUCT_6);
        $priceListToProduct->setProduct($changedProduct);

        $em->persist($priceListToProduct);
        $em->flush();

        $this->assertMessagesEmpty(ResolvePriceRulesTopic::getName());
    }

    public function testPostRemove()
    {
        // Create PriceListToProduct
        $priceListToProduct = new PriceListToProduct();
        $product = $this->getReference(LoadProductData::PRODUCT_7);
        $priceListToProduct->setProduct($product);
        $priceListToProduct->setPriceList($this->getReference(LoadPriceLists::PRICE_LIST_1));

        $em = $this->getEntityManager();
        $em->persist($priceListToProduct);
        $em->flush();

        $this->clearMessageCollector();

        // Remove created PriceListToProduct
        $em->remove($priceListToProduct);
        $em->flush();

        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [
                    $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId() => [
                        $product->getId()
                    ]
                ]
            ]
        );
    }

    public function testPostRemoveWithDisabledListener()
    {
        // Create PriceListToProduct
        $priceListToProduct = new PriceListToProduct();
        $product = $this->getReference(LoadProductData::PRODUCT_7);
        $priceListToProduct->setProduct($product);
        $priceListToProduct->setPriceList($this->getReference(LoadPriceLists::PRICE_LIST_1));

        $em = $this->getEntityManager();
        $em->persist($priceListToProduct);
        $em->flush();

        $this->clearMessageCollector();

        $this->disableListener();

        // Remove created PriceListToProduct
        $em->remove($priceListToProduct);
        $em->flush();

        $this->assertMessagesEmpty(ResolvePriceRulesTopic::getName());
    }

    public function testOnAssignmentRuleBuilderBuild()
    {
        $priceListToProduct = new PriceListToProduct();
        $product = $this->getReference(LoadProductData::PRODUCT_8);
        $priceListToProduct->setProduct($product);
        $priceListToProduct->setPriceList($this->getReference(LoadPriceLists::PRICE_LIST_1));

        $em = $this->getEntityManager();
        $em->persist($priceListToProduct);
        $em->flush();

        // Assert Rules scheduled for rebuild
        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [
                    $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId() => [
                        $product->getId()
                    ]
                ]
            ]
        );

        // Assert Dependent price lists scheduled for recalculation
        self::assertMessageSent(
            ResolvePriceListAssignedProductsTopic::getName(),
            [
                'product' => [
                    $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId() => [
                        $product->getId()
                    ]
                ]
            ]
        );
    }

    public function testOnAssignmentRuleBuilderBuildWithDisabledListener()
    {
        $this->disableListener();

        $priceListToProduct = new PriceListToProduct();
        $product = $this->getReference(LoadProductData::PRODUCT_8);
        $priceListToProduct->setProduct($product);
        $priceListToProduct->setPriceList($this->getReference(LoadPriceLists::PRICE_LIST_1));

        $em = $this->getEntityManager();
        $em->persist($priceListToProduct);
        $em->flush();

        $this->assertMessagesEmpty(ResolvePriceRulesTopic::getName());
    }
}
