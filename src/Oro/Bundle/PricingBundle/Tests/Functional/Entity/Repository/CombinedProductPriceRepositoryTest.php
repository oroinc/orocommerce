<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorInterface;
use Oro\Bundle\PricingBundle\ORM\TempTableManipulatorInterface;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolationPerTest
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CombinedProductPriceRepositoryTest extends WebTestCase
{
    private ShardManager $shardManager;
    private ShardQueryExecutorInterface $insertFromSelectQueryExecutor;
    private TempTableManipulatorInterface $tempTableManipulator;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadCombinedPriceLists::class,
            LoadProductPrices::class,
            LoadCombinedProductPrices::class,
        ]);
        $this->insertFromSelectQueryExecutor = $this->getContainer()
            ->get('oro_pricing.orm.multi_insert_shard_query_executor');
        $this->shardManager = $this->getContainer()->get('oro_pricing.shard_manager');
        $this->tempTableManipulator = $this->getContainer()->get('oro_pricing.orm.temp_table_manipulator');
    }

    /**
     * @dataProvider copyDataProvider
     */
    public function testCopyPricesByPriceList(array $products = [])
    {
        $products = array_map(function ($reference) {
            return $this->getReference($reference)->getId();
        }, $products);
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference('1f');

        $combinedProductPriceRepository = $this->getCombinedProductPriceRepository();
        $combinedProductPriceRepository->deleteCombinedPrices($combinedPriceList, $products);

        /** @var ProductPriceRepository $priceListRepository */
        $priceListPriceRepository = $this->getContainer()->get('doctrine')->getRepository(ProductPrice::class);

        $combinedProductPriceRepository->copyPricesByPriceList(
            $this->insertFromSelectQueryExecutor,
            $combinedPriceList,
            $priceList,
            false,
            $products
        );

        if ($products) {
            /** @var CombinedProductPrice[] $combinedPrices */
            $combinedPrices = $combinedProductPriceRepository
                ->findBy(['priceList' => $combinedPriceList, 'product' => $products]);
            $prices = $priceListPriceRepository
                ->findByPriceList($this->shardManager, $priceList, ['product' => $products]);
        } else {
            /** @var CombinedProductPrice[] $combinedPrices */
            $combinedPrices = $combinedProductPriceRepository->findBy(['priceList' => $combinedPriceList]);
            $prices = $priceListPriceRepository->findByPriceList($this->shardManager, $priceList, []);
        }

        $this->assertNotEmpty($combinedPrices);
        $this->assertCount(count($prices), $combinedPrices);

        $expectedOriginalIds = array_map(
            static function (ProductPrice $a) {
                return $a->getId();
            },
            $prices
        );
        foreach ($combinedPrices as $combinedPrice) {
            $this->assertContains($combinedPrice->getOriginPriceId(), $expectedOriginalIds);
        }
    }

    public function copyDataProvider(): array
    {
        return [
            'all products' => [[]],
            'product-1' => [['product-1']],
        ];
    }

    /**
     * @dataProvider insertPricesByPriceListDataProvider
     */
    public function testInsertPricesByPriceList(string $combinedPriceList, ?string $product, bool $expectedExists)
    {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceList);
        $products = [];
        $findBy = ['priceList' => $combinedPriceList];
        if ($product) {
            /** @var Product $product */
            $product = $this->getReference($product);
            $products = [$product];
            $findBy['product'] = $product;
        }

        $repository = $this->getCombinedPriceListToPriceListRepository();
        $combinedPriceListRelations = $repository->getPriceListRelations($combinedPriceList, $products);

        $combinedProductPriceRepository = $this->getCombinedProductPriceRepository();

        $combinedProductPriceRepository->deleteCombinedPrices($combinedPriceList, $products);
        $prices = $combinedProductPriceRepository->findBy($findBy);
        $this->assertEmpty($prices);
        foreach ($combinedPriceListRelations as $combinedPriceListRelation) {
            $combinedProductPriceRepository->insertPricesByPriceList(
                $this->insertFromSelectQueryExecutor,
                $combinedPriceList,
                $combinedPriceListRelation->getPriceList(),
                $combinedPriceListRelation->isMergeAllowed(),
                $products
            );
        }
        /** @var CombinedProductPrice[] $prices */
        $prices = $combinedProductPriceRepository->findBy($findBy);
        if ($expectedExists) {
            $this->assertNotEmpty($prices);
            /** @var CombinedProductPrice $firstPrice */
            $firstPrice = reset($prices);
            $this->assertNotEmpty($firstPrice->getOriginPriceId());
        } else {
            $this->assertEmpty($prices);
        }
    }

    /**
     * @dataProvider insertPricesByPriceListDataProvider
     */
    public function testInsertPricesByPriceListWithTempTable(
        string $combinedPriceList,
        ?string $product,
        bool $expectedExists
    ) {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceList);

        $products = [];
        $findBy = ['priceList' => $combinedPriceList];
        if ($product) {
            /** @var Product $product */
            $product = $this->getReference($product);
            $products = [$product];
            $findBy['product'] = $product;
        }

        $repository = $this->getCombinedPriceListToPriceListRepository();
        $combinedPriceListRelations = $repository->getPriceListRelations($combinedPriceList, $products);

        $combinedProductPriceRepository = $this->getCombinedProductPriceRepository();

        $combinedProductPriceRepository->deleteCombinedPrices($combinedPriceList, $products);
        $prices = $combinedProductPriceRepository->findBy($findBy);
        $this->assertEmpty($prices);

        $this->tempTableManipulator->createTempTableForEntity(CombinedProductPrice::class, $combinedPriceList->getId());
        foreach ($combinedPriceListRelations as $combinedPriceListRelation) {
            $combinedProductPriceRepository->insertPricesByPriceListWithTempTable(
                $this->tempTableManipulator,
                $combinedPriceList,
                $combinedPriceListRelation->getPriceList(),
                $combinedPriceListRelation->isMergeAllowed(),
                $products
            );
        }

        // Move prices from temp to persistent CPL table
        $this->tempTableManipulator->moveDataFromTemplateTableToEntityTable(
            CombinedProductPrice::class,
            $combinedPriceList->getId(),
            [
                'product',
                'unit',
                'priceList',
                'productSku',
                'quantity',
                'value',
                'currency',
                'mergeAllowed',
                'originPriceId',
                'id',
            ]
        );

        /** @var CombinedProductPrice[] $prices */
        $prices = $combinedProductPriceRepository->findBy($findBy);
        if ($expectedExists) {
            $this->assertNotEmpty($prices);
            /** @var CombinedProductPrice $firstPrice */
            $firstPrice = reset($prices);
            $this->assertNotEmpty($firstPrice->getOriginPriceId());
        } else {
            $this->assertEmpty($prices);
        }
        $this->tempTableManipulator->dropTempTableForEntity(CombinedProductPrice::class, $combinedPriceList->getId());
    }

    public function insertPricesByPriceListDataProvider(): array
    {
        return [
            'test getting price lists 1' => [
                'combinedPriceList' => '1t_2t_3t',
                'product' => 'product-1',
                'expectedExists' => true,
            ],
            'test getting price lists 2' => [
                'combinedPriceList' => '1t_2t_3t',
                'product' => 'product-2',
                'expectedExists' => true,
            ],
            'test getting price lists 3' => [
                'combinedPriceList' => '2t_3f_1t',
                'product' => 'продукт-7',
                'expectedExists' => false,
            ],
            'test getting price list 1f' => [
                'combinedPriceList' => '1f',
                'product' => null,
                'expectedExists' => true,
            ],
        ];
    }

    private function getCombinedProductPriceRepository(): CombinedProductPriceRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(CombinedProductPrice::class);
    }

    private function getCombinedPriceListToPriceListRepository(): CombinedPriceListToPriceListRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(CombinedPriceListToPriceList::class);
    }

    public function testFindMinByWebsiteForFilter()
    {
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $actual = $this->getCombinedProductPriceRepository()
            ->findMinByWebsiteForFilter(
                $website->getId(),
                [$product1],
                $this->getReference('1f')
            );
        $actual = iterator_to_array($actual);
        $expected = [
            [
                'product' => (string)$product1->getId(),
                'value' => '1.1000',
                'currency' => 'USD',
                'unit' => 'bottle',
                'cpl' => $this->getReference('1t_2t_3t')->getId(),
            ],
            [
                'product' => (string)$product1->getId(),
                'value' => '1.2000',
                'currency' => 'USD',
                'unit' => 'liter',
                'cpl' => $this->getReference('1t_2t_3t')->getId(),
            ],
            [
                'product' => (string)$product1->getId(),
                'value' => '12.2000',
                'currency' => 'EUR',
                'unit' => 'bottle',
                'cpl' => $this->getReference('1f')->getId(),
            ],
            [
                'product' => (string)$product1->getId(),
                'value' => '13.1000',
                'currency' => 'USD',
                'unit' => 'bottle',
                'cpl' => $this->getReference('1f')->getId(),
            ],
            [
                'product' => (string)$product1->getId(),
                'value' => '10.0000',
                'currency' => 'USD',
                'unit' => 'liter',
                'cpl' => $this->getReference('1f')->getId(),
            ],
            [
                'product' => (string)$product1->getId(),
                'value' => '0.0000',
                'currency' => 'USD',
                'unit' => 'milliliter',
                'cpl' => $this->getReference('1f')->getId(),
            ],
        ];
        usort($expected, [$this, 'sort']);
        usort($actual, [$this, 'sort']);

        $this->assertEquals($expected, $actual);
    }

    public function testFindMinByWebsiteForSort()
    {
        $this->markTestSkipped('BB-20684');
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $actual = $this->getCombinedProductPriceRepository()
            ->findMinByWebsiteForSort(
                $website->getId(),
                [$product1],
                $this->getReference('1f')->getId()
            );
        $expected = [
            [
                'product' => (string)$product1->getId(),
                'value' => '1.1000',
                'currency' => 'USD',
                'cpl' => $this->getReference('1t_2t_3t')->getId(),
            ],
            [
                'product' => (string)$product1->getId(),
                'value' => '12.2000',
                'currency' => 'EUR',
                'cpl' => $this->getReference('1f')->getId(),
            ],
            [
                'product' => (string)$product1->getId(),
                'value' => '0.0000',
                'currency' => 'USD',
                'cpl' => $this->getReference('1f')->getId(),
            ],
        ];
        usort($expected, [$this, 'sort']);
        usort($actual, [$this, 'sort']);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider insertMinimalPricesByPriceListDataProvider
     */
    public function testInsertMinimalPricesByPriceList(
        string $combinedPriceList,
        string $product,
        array $expectedPrices
    ) {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceList);
        $products = [];
        if ($product) {
            /** @var Product $product */
            $product = $this->getReference($product);
            $products = [$product];
        }

        $repository = $this->getCombinedPriceListToPriceListRepository();
        $combinedPriceListRelations = $repository->getPriceListRelations($combinedPriceList, $products);

        $combinedProductPriceRepository = $this->getCombinedProductPriceRepository();

        $combinedProductPriceRepository->deleteCombinedPrices($combinedPriceList, $products);
        $prices = $combinedProductPriceRepository->findBy(
            [
                'priceList' => $combinedPriceList,
                'product' => $product,
            ]
        );
        $this->assertEmpty($prices);
        foreach ($combinedPriceListRelations as $combinedPriceListRelation) {
            $combinedProductPriceRepository->insertMinimalPricesByPriceList(
                $this->shardManager,
                $this->insertFromSelectQueryExecutor,
                $combinedPriceList,
                $combinedPriceListRelation->getPriceList(),
                $products
            );
        }
        $prices = $combinedProductPriceRepository->createQueryBuilder('prices')
            ->select('prices.productSku, prices.quantity, prices.value, prices.currency, IDENTITY(prices.unit) as unit')
            ->where('prices.priceList = :priceList AND prices.product = :product')
            ->setParameters(['priceList' => $combinedPriceList, 'product' => $product])
            ->orderBy('prices.currency, prices.quantity, prices.value')
            ->getQuery()
            ->getArrayResult();
        $this->assertEquals($expectedPrices, $prices);
    }

    /**
     * @dataProvider insertMinimalPricesByPriceListDataProvider
     */
    public function testInsertMinimalPricesByPriceLists(
        string $combinedPriceList,
        string $product,
        array $expectedPrices
    ) {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceList);
        $products = [];
        if ($product) {
            /** @var Product $product */
            $product = $this->getReference($product);
            $products = [$product];
        }

        $repository = $this->getCombinedPriceListToPriceListRepository();
        $combinedPriceListRelations = $repository->getPriceListRelations($combinedPriceList, $products);

        $combinedProductPriceRepository = $this->getCombinedProductPriceRepository();

        $combinedProductPriceRepository->deleteCombinedPrices($combinedPriceList, $products);
        $prices = $combinedProductPriceRepository->findBy(
            [
                'priceList' => $combinedPriceList,
                'product' => $product,
            ]
        );
        $this->assertEmpty($prices);
        $combinedProductPriceRepository->insertMinimalPricesByPriceLists(
            $this->insertFromSelectQueryExecutor,
            $combinedPriceList,
            array_map(static function (CombinedPriceListToPriceList $relation) {
                return $relation->getPriceList();
            }, $combinedPriceListRelations),
            $products
        );

        $prices = $combinedProductPriceRepository->createQueryBuilder('prices')
            ->select('prices.productSku, prices.quantity, prices.value, prices.currency, IDENTITY(prices.unit) as unit')
            ->where('prices.priceList = :priceList AND prices.product = :product')
            ->setParameters(['priceList' => $combinedPriceList, 'product' => $product])
            ->orderBy('prices.currency, prices.quantity, prices.value')
            ->getQuery()
            ->getArrayResult();
        $this->assertEquals($expectedPrices, $prices);
    }

    public function testDeleteByProductUnit()
    {
        /** @var Product $product1 */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $productUnit = $this->getReference(LoadProductUnits::LITER);

        $repo = $this->getContainer()
            ->get('doctrine')
            ->getRepository(CombinedProductPrice::class);

        $result = $repo->findBy(['product' => $product, 'unit' => $productUnit]);
        $this->assertCount(6, $result);

        $shardManager = $this->getContainer()->get('oro_pricing.shard_manager');
        $repo->deleteByProductUnit($shardManager, $product, $productUnit);

        $result = $repo->findBy(['product' => $product, 'unit' => $productUnit]);
        $this->assertCount(0, $result);
    }

    public function insertMinimalPricesByPriceListDataProvider(): array
    {
        return [
            'test getting price lists 1' => [
                'combinedPriceList' => '1t_2t_3t',
                'product' => 'product-1',
                'prices' => [
                    [
                        'productSku' => 'product-1',
                        'quantity' => 1.0,
                        'value' => '12.2000',
                        'currency' => 'EUR',
                        'unit' => 'bottle',
                    ],
                    [
                        'productSku' => 'product-1',
                        'quantity' => 11.0,
                        'value' => '12.2000',
                        'currency' => 'EUR',
                        'unit' => 'bottle',
                    ],
                    [
                        'productSku' => 'product-1',
                        'quantity' => 1.0,
                        'value' => '10.0000',
                        'currency' => 'USD',
                        'unit' => 'liter',
                    ],
                    [
                        'productSku' => 'product-1',
                        'quantity' => 10.0,
                        'value' => '12.2000',
                        'currency' => 'USD',
                        'unit' => 'liter',
                    ],
                    [
                        'productSku' => 'product-1',
                        'quantity' => 15.0,
                        'value' => '12.2000',
                        'currency' => 'USD',
                        'unit' => 'liter',
                    ],
                ],
            ],
            'test getting price lists 2' => [
                'combinedPriceList' => '1t_2t_3t',
                'product' => 'product-2',
                'prices' => [
                    [
                        'productSku' => 'product-2',
                        'quantity' => 14.0,
                        'value' => '16.5000',
                        'currency' => 'EUR',
                        'unit' => 'liter',
                    ],
                    [
                        'productSku' => 'product-2',
                        'quantity' => 24.0,
                        'value' => '16.5000',
                        'currency' => 'EUR',
                        'unit' => 'bottle',
                    ],
                    [
                        'productSku' => 'product-2',
                        'quantity' => 1.0,
                        'value' => '20.0000',
                        'currency' => 'USD',
                        'unit' => 'liter',
                    ],
                    [
                        'productSku' => 'product-2',
                        'quantity' => 12.0,
                        'value' => '12.2000',
                        'currency' => 'USD',
                        'unit' => 'liter',
                    ],
                    [
                        'productSku' => 'product-2',
                        'quantity' => 13.0,
                        'value' => '12.2000',
                        'currency' => 'USD',
                        'unit' => 'liter',
                    ],
                    [
                        'productSku' => 'product-2',
                        'quantity' => 14.0,
                        'value' => '12.2000',
                        'currency' => 'USD',
                        'unit' => 'bottle',
                    ],
                ],
            ],
            'test getting price lists 3' => [
                'combinedPriceList' => '2t_3f_1t',
                'product' => 'продукт-7',
                'prices' => [],
            ],
        ];
    }

    public function testInsertPricesByCombinedPriceList()
    {
        $combinedPriceList = $this->getReference('1t_2t_3t');
        $sourceCpl = $this->getReference('2t_3t');
        $product = $this->getReference('product-1');

        /** @var CombinedProductPriceRepository $repo */
        $repo = $this->getContainer()
            ->get('doctrine')
            ->getRepository(CombinedProductPrice::class);

        $repo->insertPricesByCombinedPriceList(
            $this->insertFromSelectQueryExecutor,
            $combinedPriceList,
            $sourceCpl
        );

        $prices = $repo->createQueryBuilder('prices')
            ->select(
                'prices.productSku',
                'prices.quantity',
                'prices.value',
                'prices.currency',
                'IDENTITY(prices.unit) as unit'
            )
            ->where('prices.priceList = :priceList AND prices.product = :product')
            ->setParameters(['priceList' => $combinedPriceList, 'product' => $product])
            ->orderBy('prices.currency, prices.quantity, prices.value')
            ->getQuery()
            ->getArrayResult();

        $expected = [
            [
                'productSku' => 'product-1',
                'quantity' => 1.0,
                'value' => '0.1000',
                'currency' => 'USD',
                'unit' => 'liter',
            ],
            [
                'productSku' => 'product-1',
                'quantity' => 1.0,
                'value' => '1.1000',
                'currency' => 'USD',
                'unit' => 'bottle',
            ],
            [
                'productSku' => 'product-1',
                'quantity' => 10.0,
                'value' => '1.2000',
                'currency' => 'USD',
                'unit' => 'liter',
            ],
        ];

        $this->assertEquals($expected, $prices);
    }

    public function testInsertMinimalPricesByCombinedPriceLists()
    {
        $combinedPriceList = $this->getReference('1t_2t_3t');
        $sourceCpl = $this->getReference('2t_3t');
        $product = $this->getReference('product-1');

        /** @var CombinedProductPriceRepository $repo */
        $repo = $this->getContainer()
            ->get('doctrine')
            ->getRepository(CombinedProductPrice::class);

        $repo->insertMinimalPricesByCombinedPriceList(
            $this->insertFromSelectQueryExecutor,
            $combinedPriceList,
            $sourceCpl
        );

        $prices = $repo->createQueryBuilder('prices')
            ->select('prices.productSku, prices.quantity, prices.value, prices.currency, IDENTITY(prices.unit) as unit')
            ->where('prices.priceList = :priceList AND prices.product = :product')
            ->setParameters(['priceList' => $combinedPriceList, 'product' => $product])
            ->orderBy('prices.currency, prices.quantity, prices.value')
            ->getQuery()
            ->getArrayResult();

        $expected = [
            [
                'productSku' => 'product-1',
                'quantity' => 1.0,
                'value' => '0.1000',
                'currency' => 'USD',
                'unit' => 'liter',
            ],
            [
                'productSku' => 'product-1',
                'quantity' => 1.0,
                'value' => '1.1000',
                'currency' => 'USD',
                'unit' => 'bottle',
            ],
            [
                'productSku' => 'product-1',
                'quantity' => 10.0,
                'value' => '1.0100',
                'currency' => 'USD',
                'unit' => 'liter',
            ],
        ];

        $this->assertEquals($expected, $prices);
    }

    private function sort(array $a, array $b): int
    {
        if ($a['cpl'] === $b['cpl'] && $a['currency'] === $b['currency']) {
            return $a['unit'] > $b['unit'] ? 1 : 0;
        }
        if ($a['cpl'] === $b['cpl']) {
            return $a['currency'] > $b['currency'] ? 1 : 0;
        }

        return $a['cpl'] > $b['cpl'] ? 1 : 0;
    }

    public function testHasDuplicatePricesNoDuplicates()
    {
        /** @var CombinedProductPriceRepository $repo */
        $repo = $this->getContainer()
            ->get('doctrine')
            ->getRepository(CombinedProductPrice::class);

        $this->assertFalse($repo->hasDuplicatePrices());
    }

    public function testHasDuplicatePrices()
    {
        $this->prepareDuplicatedPrices();

        /** @var CombinedProductPriceRepository $repo */
        $repo = $this->getContainer()->get('doctrine')->getRepository(CombinedProductPrice::class);

        $this->assertTrue($repo->hasDuplicatePrices());
    }

    public function testDeleteDuplicatePricesNoDuplicates()
    {
        /** @var CombinedProductPriceRepository $repo */
        $repo = $this->getContainer()->get('doctrine')->getRepository(CombinedProductPrice::class);
        $this->assertEquals(0, $repo->deleteDuplicatePrices());
    }

    public function testDeleteDuplicatePrices()
    {
        $this->prepareDuplicatedPrices();

        /** @var CombinedProductPriceRepository $repo */
        $repo = $this->getContainer()->get('doctrine')->getRepository(CombinedProductPrice::class);
        $this->assertGreaterThan(0, $repo->deleteDuplicatePrices());
    }

    private function prepareDuplicatedPrices(): void
    {
        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
        /** @var Connection $connection */
        $connection = $doctrine->getConnection();
        $connection->executeQuery(
            <<<'SQL'
INSERT INTO oro_price_product_combined 
    (id, unit_code, product_id, combined_price_list_id, origin_price_id, product_sku, 
     quantity, value, currency, merge_allowed)
SELECT uuid_generate_v4(), unit_code, product_id, combined_price_list_id, origin_price_id, product_sku, 
       quantity, value, currency, merge_allowed 
FROM oro_price_product_combined
SQL
        );
    }
}
