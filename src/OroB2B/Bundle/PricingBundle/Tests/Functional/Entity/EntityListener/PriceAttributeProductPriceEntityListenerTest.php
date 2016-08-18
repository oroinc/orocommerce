<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\PricingBundle\Async\Topics;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributeProductPrices;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class PriceAttributeProductPriceEntityListenerTest extends WebTestCase
{
    use MessageQueueTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadPriceAttributeProductPrices::class,
            LoadPriceRuleLexemes::class
        ]);
        $this->topic = Topics::CALCULATE_RULE;
        $this->cleanQueueMessageTraces();
    }

    public function testPostPersist()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Product::class);
    
        /** @var PriceAttributePriceList $priceAttribute */
        $priceAttribute = $this->getReference('price_attribute_price_list_1');
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_4);
    
        $price = new PriceAttributeProductPrice();
        $price->setProduct($product)
            ->setPriceList($priceAttribute)
            ->setQuantity(1)
            ->setUnit($this->getReference('product_unit.box'))
            ->setPrice(Price::create(42, 'USD'));
    
        $em->persist($price);
        $em->flush();
    
        $traces = $this->getQueueMessageTraces();
        $this->assertCount(1, $traces);
    
        $trace = $traces[0];
        $productId = $this->getProductIdFromTrace($trace);
        $this->assertNotEmpty($productId);
        $this->assertEquals($product->getId(), $productId);
    
        /** @var PriceList $expectedPriceList */
        $expectedPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $this->assertEquals($expectedPriceList->getId(), $this->getPriceListIdFromTrace($trace));
    }

    public function testPreUpdate()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Product::class);
    
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
    
        /** @var PriceAttributeProductPrice $price */
        $price = $this->getReference('price_attribute_product_price.1');
        $price->setPrice(Price::create(1000, 'USD'));
    
        $em->persist($price);
        $em->flush();
    
        $traces = $this->getQueueMessageTraces();
        $this->assertCount(1, $traces);
    
        $trace = $traces[0];
        $productId = $this->getProductIdFromTrace($trace);
        $this->assertNotEmpty($productId);
        $this->assertEquals($product->getId(), $productId);
    
        /** @var PriceList $expectedPriceList */
        $expectedPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $this->assertEquals($expectedPriceList->getId(), $this->getPriceListIdFromTrace($trace));
    }

    public function testPreRemove()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Product::class);
    
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
    
        /** @var PriceAttributeProductPrice $price */
        $price = $this->getReference('price_attribute_product_price.1');
        $em->remove($price);
        $em->flush();
    
        $traces = $this->getQueueMessageTraces();
        $this->assertCount(1, $traces);
    
        $trace = $traces[0];
        $productId = $this->getProductIdFromTrace($trace);
        $this->assertNotEmpty($productId);
        $this->assertEquals($product->getId(), $productId);
    
        /** @var PriceList $expectedPriceList */
        $expectedPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $this->assertEquals($expectedPriceList->getId(), $this->getPriceListIdFromTrace($trace));
    }
}
