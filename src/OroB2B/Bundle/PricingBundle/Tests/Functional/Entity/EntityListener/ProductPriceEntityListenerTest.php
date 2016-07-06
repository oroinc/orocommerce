<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use Oro\Component\Testing\QueryTracker;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 */
class ProductPriceEntityListenerTest extends AbstractChangedProductPriceTest
{
    /**
     * @var Product
     */
    protected $testProduct;

    /**
     * @var PriceList
     */
    protected $testPriceList;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
        ]);
        $this->testProduct = $this->getProduct();
        $this->testPriceList = $this->getPriceList();
        $this->clearTable();
    }

    public function testOnCreate()
    {
        $productUnit = $this->getProductUnit();

        $price1 = Price::create(10, 'USD');
        $productPrice1 = new ProductPrice();
        $productPrice1
            ->setQuantity(10)
            ->setUnit($productUnit)
            ->setProduct($this->testProduct)
            ->setPriceList($this->testPriceList)
            ->setPrice($price1);
        $em = $this->getProductPriceManager();
        $em->persist($productPrice1);

        $price2 = Price::create(5, 'USD');
        $productPrice2 = new ProductPrice();
        $productPrice2
            ->setQuantity(100)
            ->setUnit($productUnit)
            ->setProduct($this->testProduct)
            ->setPriceList($this->testPriceList)
            ->setPrice($price2);
        $em->persist($productPrice2);

        $queryTracker = new QueryTracker($em);
        $queryTracker->start();
        $em->flush();

        $queries = $queryTracker->getExecutedQueries();
        $this->assertCount(4, $queries);

        foreach ($queries as $query) {
            $this->assertRegExp('/^INSERT INTO/', $query);
        }

        // assert that needed triggers where created
        $actualChangeTriggers = $this->getProductPriceChangeTriggerRepository()->findBy([
            'product' => $this->testProduct,
            'priceList' => $this->testPriceList,
        ]);
        $this->assertCount(1, $actualChangeTriggers);

        $queryTracker->stop();
    }

    /**
     * @depends testOnCreate
     */
    public function testOnUpdate()
    {
        /** @var ProductPrice[] $productPrices */
        $productPrices = $this->getProductPriceRepository()->findBy([
            'product' => $this->testProduct,
            'priceList' => $this->testPriceList,
        ]);

        foreach ($productPrices as $productPrice) {
            $oldPrice = $productPrice->getPrice();
            $price = Price::create($oldPrice->getValue(), 'EUR');
            $productPrice->setPrice($price);
            $this->getProductPriceManager()->persist($productPrice);
        }

        $this->getProductPriceManager()->flush();
        $actual = $this->getProductPriceChangeTriggerRepository()->findBy([
            'product' => $this->testProduct,
            'priceList' => $this->testPriceList,
        ]);

        $this->assertCount(1, $actual);
    }

    /**
     * @depends testOnUpdate
     */
    public function testOnDelete()
    {
        $productPrices = $this->getProductPriceRepository()->findBy([
            'product' => $this->testProduct,
            'priceList' => $this->testPriceList,
        ]);

        foreach ($productPrices as $productPrice) {
            $this->getProductPriceManager()->remove($productPrice);
        }

        $this->getProductPriceManager()->flush();
        $actual = $this->getProductPriceChangeTriggerRepository()->findBy([
            'product' => $this->testProduct,
            'priceList' => $this->testPriceList,
        ]);

        $this->assertCount(1, $actual);
    }
}
