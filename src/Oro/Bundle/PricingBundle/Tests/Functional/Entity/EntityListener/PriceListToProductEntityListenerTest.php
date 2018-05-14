<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Event\PriceListToProductSaveAfterEvent;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
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
    use MessageQueueTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductPrices::class,
            LoadPriceRuleLexemes::class
        ]);

        $this->cleanScheduledMessages();
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

        $this->sendScheduledMessages();

        // Assert Rules scheduled for rebuild
        self::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                PriceListTriggerFactory::PRODUCT => [
                    $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId() => [
                        $product->getId()
                    ]
                ]
            ]
        );

        // Assert Dependent price lists scheduled for recalculation
        self::assertMessageSent(
            Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
            [
                PriceListTriggerFactory::PRODUCT => [
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
            PriceListToProductSaveAfterEvent::NAME,
            new PriceListToProductSaveAfterEvent($priceListToProduct)
        );

        $this->sendScheduledMessages();

        // Assert Rules scheduled for rebuild
        self::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                PriceListTriggerFactory::PRODUCT => [
                    $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId() => [
                        $product->getId()
                    ]
                ]
            ]
        );

        // Assert Dependent price lists scheduled for recalculation
        self::assertMessageSent(
            Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
            [
                PriceListTriggerFactory::PRODUCT => [
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

        $this->sendScheduledMessages();

        $this->assertMessagesEmpty(Topics::RESOLVE_PRICE_RULES);
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

        $this->cleanScheduledMessages();

        // Edit PriceListToProduct
        $changedProduct = $this->getReference(LoadProductData::PRODUCT_6);
        $priceListToProduct->setProduct($changedProduct);

        $em->persist($priceListToProduct);
        $em->flush();

        $this->sendScheduledMessages();

        self::assertMessagesSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                // Recalculation for old product
                // Recalculation for new product
                [
                    PriceListTriggerFactory::PRODUCT => [
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
            Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
            [
                [
                    PriceListTriggerFactory::PRODUCT => [
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

        $this->cleanScheduledMessages();

        $this->disableListener();

        // Edit PriceListToProduct
        $changedProduct = $this->getReference(LoadProductData::PRODUCT_6);
        $priceListToProduct->setProduct($changedProduct);

        $em->persist($priceListToProduct);
        $em->flush();

        $this->sendScheduledMessages();

        $this->assertMessagesEmpty(Topics::RESOLVE_PRICE_RULES);
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

        $this->cleanScheduledMessages();

        // Remove created PriceListToProduct
        $em->remove($priceListToProduct);
        $em->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                PriceListTriggerFactory::PRODUCT => [
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

        $this->cleanScheduledMessages();

        $this->disableListener();

        // Remove created PriceListToProduct
        $em->remove($priceListToProduct);
        $em->flush();

        $this->sendScheduledMessages();

        $this->assertMessagesEmpty(Topics::RESOLVE_PRICE_RULES);
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

        $this->sendScheduledMessages();

        // Assert Rules scheduled for rebuild
        self::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                PriceListTriggerFactory::PRODUCT => [
                    $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId() => [
                        $product->getId()
                    ]
                ]
            ]
        );

        // Assert Dependent price lists scheduled for recalculation
        self::assertMessageSent(
            Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
            [
                PriceListTriggerFactory::PRODUCT => [
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

        $em = $this->getContainer()->get('doctrine')
            ->getManagerForClass(PriceListToProduct::class);

        $em->persist($priceListToProduct);
        $em->flush();

        $this->sendScheduledMessages();

        $this->assertMessagesEmpty(Topics::RESOLVE_PRICE_RULES);
    }

    protected function disableListener()
    {
        $this->getContainer()->get('oro_pricing.entity_listener.price_list_to_product')->setEnabled(false);
    }

    /**
     * @return ObjectManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass(PriceListToProduct::class);
    }
}
