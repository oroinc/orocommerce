<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListToProductWithoutPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class PriceListToProductRepositoryTest extends WebTestCase
{
    private ShardManager $shardManager;
    private PriceListToProductRepository $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductPrices::class,
            LoadPriceListToProductWithoutPrices::class,
        ]);

        $this->repository = $this->getContainer()->get('doctrine')
            ->getRepository(PriceListToProduct::class);
        $this->shardManager = $this->getContainer()->get('oro_pricing.shard_manager');
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
            iterator_to_array($this->repository->getProductsWithoutPrices($this->shardManager, $priceList))
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
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(PriceListToProduct::class);
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

    public function testDeleteManualRelations()
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(PriceListToProduct::class);
        $priceList = new PriceList();
        $priceList->setName('test price list');
        $em->persist($priceList);

        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $generatedRelation = $this->createRelation($priceList, $product1, false);
        $em->persist($generatedRelation);

        //not manual relations
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);
        $manualRelation1 = $this->createRelation($priceList, $product2, true);
        $em->persist($manualRelation1);

        /** @var Product $product3 */
        $product3 = $this->getReference(LoadProductData::PRODUCT_3);
        $manualRelation2 = $this->createRelation($priceList, $product3, true);
        $em->persist($manualRelation2);

        $em->flush();

        $actual = $this->repository->findBy(['priceList' => $priceList], ['manual' => 'ASC']);
        $this->assertCount(3, $actual);

        $this->repository->deleteManualRelations($priceList, [$product2]);
        /** @var PriceListToProduct[] $actual */
        $actual = $this->repository->findBy(['priceList' => $priceList], ['manual' => 'ASC']);
        $this->assertCount(2, $actual);
        $this->assertEquals($generatedRelation->getId(), array_shift($actual)->getId());
        $this->assertEquals($manualRelation2->getId(), array_shift($actual)->getId());

        $this->repository->deleteManualRelations($priceList);
        $actual = $this->repository->findBy(['priceList' => $priceList]);
        $this->assertCount(1, $actual);
        $this->assertEquals($generatedRelation->getId(), array_shift($actual)->getId());
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

    public function testCreateRelation()
    {
        $this->assertCount(11, $this->repository->findAll());

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        $priceList = new PriceList();
        $priceList->setName('test price list');
        $em->persist($priceList);
        $em->flush();

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_5);

        $this->assertEquals(1, $this->repository->createRelation($priceList, $product));
        $this->assertCount(12, $this->repository->findAll());

        // try to add relation with duplicated values
        $this->assertEquals(0, $this->repository->createRelation($priceList, $product));
        $this->assertCount(12, $this->repository->findAll());
    }

    private function createRelation(PriceList $priceList, Product $product, bool $isManual): PriceListToProduct
    {
        $manualRelation = new PriceListToProduct();
        $manualRelation->setPriceList($priceList)
            ->setProduct($product)
            ->setManual($isManual);

        return $manualRelation;
    }
}
