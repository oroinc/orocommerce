<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListToProductWithoutPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

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
        $this->initClient();
        $this->loadFixtures(
            [
                LoadProductPrices::class,
                LoadPriceListToProductWithoutPrices::class,
            ]
        );

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroPricingBundle:PriceListToProduct');
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
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroPricingBundle:PriceListToProduct');
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

    public function testCopyRelations()
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(PriceListToProduct::class);
        $targetPriceList = new PriceList();
        $targetPriceList->setName('test price list');
        $em->persist($targetPriceList);
        $em->flush();

        /** @var PriceList $sourcePriceList */
        $sourcePriceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_5);
        $generatedRelation = new PriceListToProduct();
        $generatedRelation->setPriceList($sourcePriceList);
        $generatedRelation->setProduct($product);
        $generatedRelation->setManual(false);
        $em->persist($generatedRelation);
        $em->flush($generatedRelation);

        $insertQueryExecutor = $this->getContainer()->get('oro_entity.orm.insert_from_select_query_executor');
        $this->repository->copyRelations($sourcePriceList, $targetPriceList, $insertQueryExecutor);

        $relations = $this->repository->findBy(['priceList' => $sourcePriceList]);
        $this->assertCount(4, $relations);
        /** @var PriceListToProduct[] $newRelations */
        $newRelations = $this->repository->findBy(['priceList' => $targetPriceList]);
        $this->assertCount(3, $newRelations);
        foreach ($newRelations as $newRelation) {
            $this->assertTrue($newRelation->isManual());
        }
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
