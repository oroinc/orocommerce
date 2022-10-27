<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\ORM;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\ORM\MultiInsertShardQueryExecutor;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class MultiInsertShardQueryExecutorTest extends WebTestCase
{
    private const BATCH_SIZE = 1;

    private MultiInsertShardQueryExecutor $insertSelectExecutor;
    private ShardManager $shardManager;
    private NativeQueryExecutorHelper $helper;

    protected function setUp(): void
    {
        $this->initClient();
        $this->insertSelectExecutor = $this->getContainer()->get('oro_pricing.orm.multi_insert_shard_query_executor');
        $this->insertSelectExecutor->setBatchSize(self::BATCH_SIZE);
        $this->shardManager = $this->getContainer()->get('oro_pricing.shard_manager');
        $this->helper = $this->getContainer()->get('oro_entity.orm.native_query_executor_helper');
    }

    public function testInsert()
    {
        $this->loadFixtures([LoadProductPrices::class]);
        $priceListFrom = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceListInto = $this->getReference(LoadPriceLists::PRICE_LIST_6);

        /** @var ProductPriceRepository $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(ProductPrice::class);
        $repository->deleteByPriceList($this->shardManager, $priceListInto);

        $qb = $repository->createQueryBuilder('prices');
        $qb->select([
            'UUID()',
            'IDENTITY(prices.product)',
            'prices.productSku',
            'prices.quantity',
            'IDENTITY(prices.unit)',
            'prices.value',
            (string)$priceListInto->getId(),
            'prices.currency',
        ])
            ->leftJoin(
                ProductPrice::class,
                'p_check',
                'WITH',
                $qb->expr()->andX(
                    'prices.product = p_check.product',
                    'prices.quantity = p_check.quantity',
                    'prices.unit = p_check.unit',
                    'prices.currency = p_check.currency',
                    'p_check.priceList = :targetPriceList'
                )
            )
            ->where('prices.priceList = :priceList')
            ->andWhere('p_check.id IS NULL')
            ->setParameter('priceList', $priceListFrom)
            ->setParameter('targetPriceList', $priceListInto);

        $fields = ['id', 'product', 'productSku', 'quantity', 'unit', 'value', 'priceList', 'currency'];
        $this->insertSelectExecutor->execute(ProductPrice::class, $fields, $qb);

        $originalCount = $repository->countByPriceList($this->shardManager, $priceListFrom);
        $countSaved = $repository->countByPriceList($this->shardManager, $priceListInto);
        $this->assertEquals($originalCount, $countSaved);
    }

    public function testInsertMultipleBatches()
    {
        $this->loadFixtures([
            LoadProductUnitPrecisions::class,
            LoadPriceLists::class
        ]);
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $priceListFrom = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $this->createProductPrices($priceListFrom, $product);
        $priceListInto = $this->getReference(LoadPriceLists::PRICE_LIST_6);

        /** @var ProductPriceRepository $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(ProductPrice::class);
        $repository->deleteByPriceList($this->shardManager, $priceListInto);

        $qb = $this->getInsertQueryBuilder($priceListInto, $priceListFrom, $product);

        $fields = ['id', 'product', 'productSku', 'quantity', 'unit', 'value', 'priceList', 'currency'];
        $this->insertSelectExecutor->execute(ProductPrice::class, $fields, $qb);

        $originalCount = $repository->countByPriceList($this->shardManager, $priceListFrom);
        $countSaved = $repository->countByPriceList($this->shardManager, $priceListInto);
        $this->assertEquals($originalCount, $countSaved);
    }

    public function testInsertMultipleBatchesWithExecuteNative()
    {
        $this->loadFixtures([
            LoadProductUnitPrecisions::class,
            LoadPriceLists::class
        ]);
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $priceListFrom = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $this->createProductPrices($priceListFrom, $product);
        $priceListInto = $this->getReference(LoadPriceLists::PRICE_LIST_6);

        /** @var ProductPriceRepository $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(ProductPrice::class);
        $repository->deleteByPriceList($this->shardManager, $priceListInto);

        $qb = $this->getInsertQueryBuilder($priceListInto, $priceListFrom, $product);

        $fields = ['id', 'product', 'productSku', 'quantity', 'unit', 'value', 'priceList', 'currency'];
        $selectQuery = $qb->getQuery();
        [$params, $types] = $this->helper->processParameterMappings($selectQuery);
        $this->insertSelectExecutor->executeNative(
            'oro_price_product',
            ProductPrice::class,
            $selectQuery->getSQL(),
            $fields,
            $params,
            $types
        );

        $originalCount = $repository->countByPriceList($this->shardManager, $priceListFrom);
        $countSaved = $repository->countByPriceList($this->shardManager, $priceListInto);
        $this->assertEquals($originalCount, $countSaved);
    }

    private function createProductPrices(PriceList $priceListFrom, Product $product): void
    {
        $priceManager = $this->getContainer()->get('oro_pricing.manager.price_manager');
        $unit = $this->getReference('product_unit.liter');
        for ($i = 1; $i <= self::BATCH_SIZE + 1; $i++) {
            $productPrice = new ProductPrice();
            $productPrice
                ->setPriceList($priceListFrom)
                ->setUnit($unit)
                ->setQuantity($i)
                ->setPrice(Price::create($i, 'USD'))
                ->setProduct($product);

            $priceManager->persist($productPrice);
        }
        $priceManager->flush();
    }

    private function getInsertQueryBuilder(
        PriceList $priceListInto,
        PriceList $priceListFrom,
        Product $product
    ): QueryBuilder {
        $repository = self::getContainer()->get('doctrine')->getRepository(ProductPrice::class);
        $existSubQb = $repository->createQueryBuilder('targetPrices');
        $existSubQb
            ->select('targetPrices.id')
            ->where($existSubQb->expr()->eq('targetPrices.priceList', ':targetPriceList'));
        $qb = $repository->createQueryBuilder('prices');
        $qb->select([
            'UUID()',
            'IDENTITY(prices.product)',
            'prices.productSku',
            'prices.quantity',
            'IDENTITY(prices.unit)',
            'prices.value',
            (string)$priceListInto->getId(),
            'prices.currency',
        ])
            ->where('prices.priceList = :priceList')
            ->andWhere('prices.product = :product')
            ->andWhere($qb->expr()->not($qb->expr()->exists($existSubQb->getDQL())))
            ->setParameter('priceList', $priceListFrom)
            ->setParameter('targetPriceList', $priceListInto)
            ->setParameter('product', $product);

        return $qb;
    }
}
