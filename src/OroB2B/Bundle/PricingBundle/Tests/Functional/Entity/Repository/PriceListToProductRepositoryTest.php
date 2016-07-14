<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToProduct;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListToProductWithoutPrices;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class PriceListToProductRepositoryTest extends WebTestCase
{
    /**
     * @var PriceListToProductRepository
     */
    protected $repository;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                LoadProductPrices::class,
                LoadPriceListToProductWithoutPrices::class,
            ]
        );

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BPricingBundle:PriceListToProduct');
    }
    
    public function testGetProductsWithoutPrices()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_2);

        // Assert that there are 4 assigned products for given price list
        $this->assertCount(4, $this->repository->findBy(['priceList' => $priceList]));
        $actual = array_map(
            function (Product $product) {
                return $product->getId();
            },
            iterator_to_array($this->repository->getProductsWithoutPrices($priceList))
        );

        // Check that 2 products does not have prices
        $expected = [
            $this->getReference(LoadProductData::PRODUCT_3)->getId(),
            $this->getReference(LoadProductData::PRODUCT_4)->getId(),
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testDeleteGeneratedRelations()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);

        // delete relation for price lists
        $this->repository->createQueryBuilder('pltp')
            ->delete()
            ->where('pltp.priceList = :pl')
            ->setParameter('pl', $priceList)
            ->getQuery()
            ->execute();

        // CREATE TEST DATA
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BPricingBundle:PriceListToProduct');
        // manual relation
        $manualRelation = new PriceListToProduct();
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $manualRelation->setPriceList($priceList)
            ->setProduct($product1)
            ->setManual(true);
        $em->persist($manualRelation);
        //not manual relations
        $nmRelation1 = new PriceListToProduct();
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);
        $nmRelation1->setPriceList($priceList)
            ->setProduct($product2)
            ->setManual(false);
        $em->persist($nmRelation1);
        $nmRelation2 = new PriceListToProduct();
        /** @var Product $product3 */
        $product3 = $this->getReference(LoadProductData::PRODUCT_3);
        $nmRelation2->setPriceList($priceList)
            ->setProduct($product3)
            ->setManual(false);
        $em->persist($nmRelation2);
        $em->flush();


        $this->repository->deleteGeneratedRelations($priceList);
        $actual = $this->repository->findBy(['priceList' => $priceList]);
        $this->assertCount(1, $actual);
        $this->assertSame($manualRelation, $actual[0]);
    }
}
