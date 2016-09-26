<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceList;
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
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadProductPrices::class,
            LoadPriceRuleLexemes::class
        ]);

        $this->cleanQueueMessageTraces();
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

        // Assert Rules scheduled for rebuild
        $traces = $this->getQueueMessageTraces(Topics::RESOLVE_PRICE_RULES);
        $this->assertCount(1, $traces);
        $this->assertMessageContainsProductAndPriceList(
            $traces[0],
            $product,
            $this->getReference(LoadPriceLists::PRICE_LIST_1)
        );

        // Assert Dependent price lists scheduled for recalculation
        $traces = $this->getQueueMessageTraces(Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS);
        $this->assertCount(1, $traces);
        $this->assertMessageContainsProductAndPriceList(
            $traces[0],
            $product,
            $this->getReference(LoadPriceLists::PRICE_LIST_2)
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

        $this->cleanQueueMessageTraces();
        $this->assertEmpty($this->getQueueMessageTraces());

        // Edit PriceListToProduct
        $changedProduct = $this->getReference(LoadProductData::PRODUCT_6);
        $priceListToProduct->setProduct($changedProduct);

        $em->persist($priceListToProduct);
        $em->flush();

        $traces = $this->getQueueMessageTraces(Topics::RESOLVE_PRICE_RULES);
        $this->assertCount(2, $traces);

        // Recalculation for old product
        $this->assertMessageContainsProductAndPriceList(
            $traces[0],
            $product,
            $this->getReference(LoadPriceLists::PRICE_LIST_1)
        );

        // Recalculation for new product
        $this->assertMessageContainsProductAndPriceList(
            $traces[1],
            $changedProduct,
            $this->getReference(LoadPriceLists::PRICE_LIST_1)
        );

        // Assert Dependent price lists scheduled for recalculation for old product
        $traces = $this->getQueueMessageTraces(Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS);
        $this->assertCount(2, $traces);

        $this->assertMessageContainsProductAndPriceList(
            $traces[0],
            $product,
            $expectedAffectedPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_2)
        );

        $this->assertMessageContainsProductAndPriceList(
            $traces[1],
            $changedProduct,
            $expectedAffectedPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_2)
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

        $this->cleanQueueMessageTraces();

        // Remove created PriceListToProduct
        $em->remove($priceListToProduct);
        $em->flush();

        $traces = $this->getQueueMessageTraces(Topics::RESOLVE_PRICE_RULES);
        $this->assertCount(1, $traces);

        $this->assertMessageContainsProductAndPriceList(
            $traces[0],
            $product,
            $this->getReference(LoadPriceLists::PRICE_LIST_1)
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

        // Assert Rules scheduled for rebuild
        $traces = $this->getQueueMessageTraces(Topics::RESOLVE_PRICE_RULES);
        $this->assertCount(1, $traces);
        $this->assertMessageContainsProductAndPriceList(
            $traces[0],
            $product,
            $this->getReference(LoadPriceLists::PRICE_LIST_1)
        );

        // Assert Dependent price lists scheduled for recalculation
        $traces = $this->getQueueMessageTraces(Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS);
        $this->assertCount(1, $traces);
        $this->assertMessageContainsProductAndPriceList(
            $traces[0],
            $product,
            $this->getReference(LoadPriceLists::PRICE_LIST_2)
        );
    }

    /**
     * @param array $trace
     * @param Product $product
     * @param PriceList $priceList
     */
    protected function assertMessageContainsProductAndPriceList(array $trace, Product $product, PriceList $priceList)
    {
        $productId = $this->getProductIdFromTrace($trace);
        $this->assertNotEmpty($productId);
        $this->assertEquals($product->getId(), $productId);

        /** @var PriceList $expectedPriceList */
        $this->assertEquals($priceList->getId(), $this->getPriceListIdFromTrace($trace));
    }
}
