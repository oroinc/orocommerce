<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\QueryTracker;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ProductPriceEntityListenerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadProductPrices::class,
        ]);

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getRepository('OroB2BPricingBundle:ProductPriceChangeTrigger')
            ->createQueryBuilder('cpp')
            ->delete()
            ->getQuery()
            ->execute();

        $em->getRepository('OroB2BPricingBundle:PriceListToProduct')
            ->createQueryBuilder('pltp')
            ->delete()
            ->getQuery()
            ->execute();
    }

    public function testOnCreate()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        // create two prices with same product and priceList
        // to ensure that duplicate triggers won't be flushed
        $em->persist(
            $this->getProductPrice(
                LoadProductData::PRODUCT_5,
                LoadPriceLists::PRICE_LIST_1,
                Price::create(10, 'USD'),
                10
            )
        );
        $em->persist(
            $this->getProductPrice(
                LoadProductData::PRODUCT_5,
                LoadPriceLists::PRICE_LIST_1,
                Price::create(100, 'USD'),
                100
            )
        );

        $queryTracker = new QueryTracker($em);
        $queryTracker->start();
        $em->flush();

        $queries = $queryTracker->getExecutedQueries();
        $this->assertCount(4, $queries);

        foreach ($queries as $query) {
            $this->assertRegExp('/^INSERT INTO/', $query);
        }

        // assert that needed triggers where created
        $actualChangeTriggers = $em->getRepository('OroB2BPricingBundle:ProductPriceChangeTrigger')->findBy([
            'product' => $this->getReference(LoadProductData::PRODUCT_5),
            'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_1)
        ]);
        $this->assertCount(1, $actualChangeTriggers);

        // assert that needed productPriceRelationCreated
        $plToProductRelations = $em->getRepository('OroB2BPricingBundle:PriceListToProduct')->findBy([
            'product' => $this->getReference(LoadProductData::PRODUCT_5),
            'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_1)
        ]);
        $this->assertCount(1, $plToProductRelations);

        $queryTracker->stop();
    }

    public function testOnUpdateChangeTriggerCreated()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        /** @var ProductPrice $productPrice */
        $productPrice = $this->getReference(LoadProductPrices::PRODUCT_PRICE_4);
        $productPrice->setPrice(Price::create(1000, 'EUR'));
        $em->persist($productPrice);
        $em->flush();

        $actual = $em->getRepository('OroB2BPricingBundle:ProductPriceChangeTrigger')->findBy([
            'product' => $this->getReference(LoadProductData::PRODUCT_2),
            'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_2)
        ]);

        $this->assertCount(1, $actual);
    }

    public function testOnUpdatePriceToProductRelation()
    {
        /** @var ProductPrice $productPrice1 */
        $productPrice1 = $this->getReference(LoadProductPrices::PRODUCT_PRICE_1);
        /** @var ProductPrice $productPrice2 */
        $productPrice2 = $this->getReference(LoadProductPrices::PRODUCT_PRICE_4);
        /** @var ProductPrice $productPrice3 */
        $productPrice3 = $this->getReference(LoadProductPrices::PRODUCT_PRICE_6);

        /** @var Product $newProduct */
        $newProduct = $this->getReference(LoadProductData::PRODUCT_2);
        $productPrice1->setProduct($newProduct);

        /** @var PriceList $newPriceList */
        $newPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_5);
        $productPrice2->setPriceList($newPriceList);

        $productPrice3->setQuantity(10000);

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->flush();
        $repository = $em->getRepository('OroB2BPricingBundle:PriceListToProduct');

        // new relation should be created when new product specified
        $this->assertCount(1, $repository->findBy([
            'product' => $this->getReference(LoadProductData::PRODUCT_2),
            'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_1)
        ]));

        // new relation should be created when new price list specified
        $this->assertCount(1, $repository->findBy([
            'product' => $this->getReference(LoadProductData::PRODUCT_2),
            'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_5)
        ]));

        $this->assertCount(2, $repository->findBy([]));
    }

    public function testOnDelete()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->remove($this->getReference(LoadProductPrices::PRODUCT_PRICE_1));
        $em->remove($this->getReference(LoadProductPrices::PRODUCT_PRICE_2));
        $em->flush();

        $actual = $em->getRepository('OroB2BPricingBundle:ProductPriceChangeTrigger')->findBy([
            'product' => $this->getReference(LoadProductData::PRODUCT_1),
            'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_1),
        ]);

        $this->assertCount(1, $actual);
    }

    /**
     * @param string $productReference
     * @param string $priceListReference
     * @param Price $price
     * @param integer $quantity
     * @return ProductPrice
     */
    protected function getProductPrice($productReference, $priceListReference, Price $price, $quantity)
    {
        /** @var ProductUnit $productUnit */
        $productUnit = $this->getReference('product_unit.liter');
        /** @var Product $product */
        $product = $this->getReference($productReference);
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceListReference);

        $productPrice = new ProductPrice();
        $productPrice
            ->setQuantity($quantity)
            ->setUnit($productUnit)
            ->setProduct($product)
            ->setPriceList($priceList)
            ->setPrice($price);

        return $productPrice;
    }
}
