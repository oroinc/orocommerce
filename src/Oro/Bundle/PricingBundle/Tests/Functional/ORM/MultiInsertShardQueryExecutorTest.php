<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\ORM;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
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

    /**
     * @var MultiInsertShardQueryExecutor
     */
    protected $insertSelectExecutor;

    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @var EntityManager
     */
    protected $em;

    protected function setUp(): void
    {
        $this->initClient();
        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass(ProductPrice::class);
        $this->insertSelectExecutor = $this->getContainer()->get('oro_pricing.orm.multi_insert_shard_query_executor');
        $this->insertSelectExecutor->setBatchSize(self::BATCH_SIZE);
        $this->shardManager = $this->getContainer()->get('oro_pricing.shard_manager');
    }

    public function testInsert()
    {
        $this->loadFixtures([LoadProductPrices::class]);
        $priceListFrom = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceListInto = $this->getReference(LoadPriceLists::PRICE_LIST_6);

        /** @var ProductPriceRepository $repository */
        $repository = $this->em->getRepository(ProductPrice::class);
        $repository->deleteByPriceList($this->shardManager, $priceListInto);

        $qb = $this->em->createQueryBuilder();
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
            ->from(ProductPrice::class, 'prices')
            ->leftJoin(
                'OroPricingBundle:ProductPrice',
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

        $fields = ['id','product', 'productSku', 'quantity', 'unit', 'value', 'priceList', 'currency'];
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
        $repository = $this->em->getRepository(ProductPrice::class);
        $repository->deleteByPriceList($this->shardManager, $priceListInto);

        $existSubQb = $this->em->createQueryBuilder();
        $existSubQb->from(ProductPrice::class, 'targetPrices')
            ->select('targetPrices.id')
            ->where($existSubQb->expr()->eq('targetPrices.priceList', ':targetPriceList'));
        $qb = $this->em->createQueryBuilder();
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
            ->from(ProductPrice::class, 'prices')
            ->where('prices.priceList = :priceList')
            ->andWhere('prices.product = :product')
            ->andWhere(
                $qb->expr()->not(
                    $qb->expr()->exists(
                        $existSubQb->getDQL()
                    )
                )
            )
            ->setParameter('priceList', $priceListFrom)
            ->setParameter('targetPriceList', $priceListInto)
            ->setParameter('product', $product);

        $fields = ['id','product', 'productSku', 'quantity', 'unit', 'value', 'priceList', 'currency'];
        $this->insertSelectExecutor->execute(ProductPrice::class, $fields, $qb);

        $originalCount = $repository->countByPriceList($this->shardManager, $priceListFrom);
        $countSaved = $repository->countByPriceList($this->shardManager, $priceListInto);
        $this->assertEquals($originalCount, $countSaved);
    }

    /**
     * @param PriceList $priceListFrom
     * @param Product $product
     */
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
}
