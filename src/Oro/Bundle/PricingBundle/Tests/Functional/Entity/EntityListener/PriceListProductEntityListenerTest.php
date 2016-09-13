<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class PriceListProductEntityListenerTest extends WebTestCase
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

        $this->topic = Topics::CALCULATE_RULE;

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

        $traces = $this->getQueueMessageTraces();
        $this->assertCount(1, $traces);

        $trace = $traces[0];
        $productId = $this->getProductIdFromTrace($trace);
        $this->assertNotEmpty($productId);
        $this->assertEquals($product->getId(), $productId);

        /** @var PriceList $expectedPriceList */
        $expectedAffectedPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        $this->assertEquals($expectedAffectedPriceList->getId(), $this->getPriceListIdFromTrace($trace));

        // Delete created entity
        $em->remove($priceListToProduct);
        $em->flush();
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

        // Edit PriceListToProduct
        $changedProduct = $this->getReference(LoadProductData::PRODUCT_6);
        $priceListToProduct->setProduct($changedProduct);

        $em->persist($priceListToProduct);
        $em->flush();

        $traces = $this->getQueueMessageTraces();
        $this->assertCount(1, $traces);

        $trace = $traces[0];
        $productId = $this->getProductIdFromTrace($trace);
        $this->assertNotEmpty($productId);
        $this->assertEquals($changedProduct->getId(), $productId);

        /** @var PriceList $expectedPriceList */
        $expectedAffectedPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        $this->assertEquals($expectedAffectedPriceList->getId(), $this->getPriceListIdFromTrace($trace));

        // Delete created entity
        $em->remove($priceListToProduct);
        $em->flush();
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

        $traces = $this->getQueueMessageTraces();
        $this->assertCount(1, $traces);

        $trace = $traces[0];
        $productId = $this->getProductIdFromTrace($trace);
        $this->assertNotEmpty($productId);
        $this->assertEquals($product->getId(), $productId);

        /** @var PriceList $expectedPriceList */
        $expectedAffectedPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        $this->assertEquals($expectedAffectedPriceList->getId(), $this->getPriceListIdFromTrace($trace));
    }
}
