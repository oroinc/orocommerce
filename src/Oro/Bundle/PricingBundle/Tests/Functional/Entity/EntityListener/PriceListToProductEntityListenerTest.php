<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

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

        $em = $this->getContainer()->get('doctrine')
            ->getManagerForClass(PriceListToProduct::class);

        $em->persist($priceListToProduct);
        $em->flush();

        $this->sendScheduledMessages();

        // Assert Rules scheduled for rebuild
        self::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId(),
                PriceListTriggerFactory::PRODUCT => $product->getId()
            ]
        );

        // Assert Dependent price lists scheduled for recalculation
        self::assertMessageSent(
            Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
            [
                PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId(),
                PriceListTriggerFactory::PRODUCT => $product->getId()
            ]
        );
    }

    public function testPreUpdate()
    {
        // Create PriceListToProduct
        $priceListToProduct = new PriceListToProduct();
        $product = $this->getReference(LoadProductData::PRODUCT_7);
        $priceListToProduct->setProduct($product);
        $priceListToProduct->setPriceList($this->getReference(LoadPriceLists::PRICE_LIST_1));

        $em = $this->getContainer()->get('doctrine')
            ->getManagerForClass(PriceListToProduct::class);

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
                [
                    PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId(),
                    PriceListTriggerFactory::PRODUCT => $product->getId()
                ],
                // Recalculation for new product
                [
                    PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId(),
                    PriceListTriggerFactory::PRODUCT => $changedProduct->getId()
                ],
            ]
        );

        // Assert Dependent price lists scheduled for recalculation for old product
        self::assertMessagesSent(
            Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
            [
                [
                    PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId(),
                    PriceListTriggerFactory::PRODUCT => $product->getId()
                ],
                [
                    PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId(),
                    PriceListTriggerFactory::PRODUCT => $changedProduct->getId()
                ],
            ]
        );
    }

    public function testPostRemove()
    {
        // Create PriceListToProduct
        $priceListToProduct = new PriceListToProduct();
        $product = $this->getReference(LoadProductData::PRODUCT_7);
        $priceListToProduct->setProduct($product);
        $priceListToProduct->setPriceList($this->getReference(LoadPriceLists::PRICE_LIST_1));

        $em = $this->getContainer()->get('doctrine')
            ->getManagerForClass(PriceListToProduct::class);

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
                PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId(),
                PriceListTriggerFactory::PRODUCT => $product->getId()
            ]
        );
    }

    public function testOnAssignmentRuleBuilderBuild()
    {
        $priceListToProduct = new PriceListToProduct();
        $product = $this->getReference(LoadProductData::PRODUCT_8);
        $priceListToProduct->setProduct($product);
        $priceListToProduct->setPriceList($this->getReference(LoadPriceLists::PRICE_LIST_1));

        $em = $this->getContainer()->get('doctrine')
            ->getManagerForClass(PriceListToProduct::class);

        $em->persist($priceListToProduct);
        $em->flush();

        $this->sendScheduledMessages();

        // Assert Rules scheduled for rebuild
        self::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId(),
                PriceListTriggerFactory::PRODUCT => $product->getId()
            ]
        );

        // Assert Dependent price lists scheduled for recalculation
        self::assertMessageSent(
            Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
            [
                PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_2)->getId(),
                PriceListTriggerFactory::PRODUCT => $product->getId()
            ]
        );
    }
}
