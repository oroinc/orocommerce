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
     * {@inheritdoc}
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
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BPricingBundle:PriceListToProduct');
        $priceList = new PriceList();
        $priceList->setName('test price list');
        $em->persist($priceList);

        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $manualRelation = $this->createRelation($priceList, $product1, true);
        $em->persist($manualRelation);

        //not manual relations
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);
        $nmRelation1 = $this->createRelation($priceList, $product2, false);
        $em->persist($nmRelation1);

        /** @var Product $product3 */
        $product3 = $this->getReference(LoadProductData::PRODUCT_3);
        $nmRelation3 = $this->createRelation($priceList, $product3, false);
        $em->persist($nmRelation3);

        $em->flush();

        $this->repository->deleteGeneratedRelations($priceList);
        /** @var PriceListToProduct[] $actual */
        $actual = $this->repository->findBy(['priceList' => $priceList]);
        $this->assertCount(1, $actual);
        $this->assertEquals($manualRelation->getId(), $actual[0]->getId());
    }

    /**
     * @param PriceList $priceList
     * @param Product $product
     * @param bool $isManual
     * @return PriceListToProduct
     */
    protected function createRelation($priceList, $product, $isManual)
    {
        $manualRelation = new PriceListToProduct();
        $manualRelation->setPriceList($priceList)
            ->setProduct($product)
            ->setManual($isManual);

        return $manualRelation;
    }
}
