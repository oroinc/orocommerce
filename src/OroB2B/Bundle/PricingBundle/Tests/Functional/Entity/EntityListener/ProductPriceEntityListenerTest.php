<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\ChangedProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * @dbIsolation
 */
class ProductPriceEntityListenerTest extends WebTestCase
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
    }

    public function testOnCreate()
    {
        $this->clearTable();

        $productUnit = $this->getProductUnit();

        $price1 = Price::create(10, 'USD');
        $productPrice1 = new ProductPrice();
        $productPrice1
            ->setQuantity(10)
            ->setUnit($productUnit)
            ->setProduct($this->testProduct)
            ->setPriceList($this->testPriceList)
            ->setPrice($price1);
        $this->getProductPriceManager()->persist($productPrice1);

        $price2 = Price::create(5, 'USD');
        $productPrice2 = new ProductPrice();
        $productPrice2
            ->setQuantity(100)
            ->setUnit($productUnit)
            ->setProduct($this->testProduct)
            ->setPriceList($this->testPriceList)
            ->setPrice($price2);
        $this->getProductPriceManager()->persist($productPrice2);

        $this->getProductPriceManager()->flush();
        $actual = $this->getChangedProductPriceRepository()->findBy([
            'product' => $this->testProduct,
            'priceList' => $this->testPriceList,
        ]);

        $this->assertCount(1, $actual);
    }

    /**
     * @depends testOnCreate
     */
    public function testOnUpdate()
    {
        $this->clearTable();
        $this->markTestSkipped();//todo fix

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
        $actual = $this->getChangedProductPriceRepository()->findBy([
            'product' => $this->testProduct,
            'priceList' => $this->testPriceList,
        ]);

        $this->assertCount(1, $actual);
    }

    /**
     * @depends testOnCreate
     */
    public function testOnDelete()
    {
        $this->clearTable();

        $productPrices = $this->getProductPriceRepository()->findBy([
            'product' => $this->testProduct,
            'priceList' => $this->testPriceList,
        ]);

        foreach ($productPrices as $productPrice) {
            $this->getProductPriceManager()->remove($productPrice);
        }

        $this->getProductPriceManager()->flush();
        $actual = $this->getChangedProductPriceRepository()->findBy([
            'product' => $this->testProduct,
            'priceList' => $this->testPriceList,
        ]);

        $this->assertCount(1, $actual);
    }

    protected function clearTable()
    {
        $this->getChangedProductPriceRepository()
            ->createQueryBuilder('cpp')
            ->delete()
            ->getQuery()
            ->execute();
    }

    /**
     * @return Product
     */
    protected function getProduct()
    {
        $productClassName = $this->getContainer()->getParameter('orob2b_product.product.class');

        $manager = $this->getContainer()->get('doctrine')->getManagerForClass($productClassName);
        /** @var ObjectRepository $repository */
        $repository = $manager->getRepository('OroB2BProductBundle:Product');

        return $repository->findOneBy([]);
    }

    /**
     * @return PriceList
     */
    protected function getPriceList()
    {
        $priceListClassName = $this->getContainer()->getParameter('orob2b_pricing.entity.price_list.class');

        $manager = $this->getContainer()->get('doctrine')->getManagerForClass($priceListClassName);
        $repository = $manager->getRepository('OroB2BPricingBundle:PriceList');

        return $repository->findOneBy([]);
    }

    /**
     * @return ProductUnit
     */
    protected function getProductUnit()
    {
        $productUnitClassName = $this->getContainer()->getParameter('orob2b_product.product_unit.class');

        $manager = $this->getContainer()->get('doctrine')->getManagerForClass($productUnitClassName);
        $repository = $manager->getRepository('OroB2BProductBundle:ProductUnit');

        return $repository->findOneBy([]);
    }

    /**
     * @return ObjectManager
     */
    protected function getProductPriceManager()
    {
        $productPriceClassName = $this->getContainer()->getParameter('orob2b_pricing.entity.product_price.class');

        return $this->getContainer()->get('doctrine')->getManagerForClass($productPriceClassName);
    }

    /**
     * @return ObjectRepository
     */
    protected function getProductPriceRepository()
    {
        return $this->getProductPriceManager()->getRepository('OroB2BPricingBundle:ProductPrice');
    }

    /**
     * @return ProductPrice
     */
    protected function getProductPrice()
    {
        return $this->getProductPriceRepository()->findOneBy([]);
    }

    /**
     * @return ObjectManager
     */
    protected function getChangedProductPriceManager()
    {
        $changedProductPriceClassName = $this->getContainer()
            ->getParameter('orob2b_pricing.entity.changed_product_price.class');

        return $this->getContainer()->get('doctrine')->getManagerForClass($changedProductPriceClassName);
    }

    /**
     * @return EntityRepository
     */
    protected function getChangedProductPriceRepository()
    {
        return $this->getChangedProductPriceManager()->getRepository('OroB2BPricingBundle:ChangedProductPrice');
    }
}
