<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\ProductPriceReference;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductPriceRepositoryTest extends WebTestCase
{
    use ProductPriceReference;

    /**
     * @var ProductPriceRepository
     */
    protected $repository;

    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures(
            [
                LoadProductPrices::class,
                LoadPriceListRelations::class
            ]
        );

        $this->repository = $this->getContainer()->get('doctrine')
            ->getRepository(ProductPrice::class);

        $this->shardManager = $this->getContainer()->get('oro_pricing.shard_manager');
    }

    public function testFindMinByWebsiteForFilter()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $actual = $this->repository->findMinByWebsiteForFilter(
            $website,
            [$product1],
            $this->getReference('price_list_1'),
            'customer'
        );
        $expected = [
            [
                'product_id' => (string)$product1->getId(),
                'value' => '10.0000',
                'currency' => 'USD',
                'unit' => 'liter',
                'price_list_id' => $this->getReference('price_list_1')->getId(),
            ],
            [
                'product_id' => (string)$product1->getId(),
                'value' => '12.2000',
                'currency' => 'EUR',
                'unit' => 'bottle',
                'price_list_id' => $this->getReference('price_list_1')->getId(),
            ],
            [
                'product_id' => (string)$product1->getId(),
                'value' => '12.2000',
                'currency' => 'USD',
                'unit' => 'liter',
                'price_list_id' => $this->getReference('price_list_2')->getId(),
            ],
        ];
        usort($expected, [$this, 'sort']);
        usort($actual, [$this, 'sort']);

        $this->assertEquals($expected, $actual);
    }

    public function testFindMinByWebsiteForSort()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $actual = $this->repository->findMinByWebsiteForSort(
            $website,
            [$product1],
            $this->getReference('price_list_1'),
            'customer'
        );
        $expected = [
            [
                'product_id' => (string)$product1->getId(),
                'value' => '10.0000',
                'currency' => 'USD',
                'price_list_id' => $this->getReference('price_list_1')->getId(),
            ],
            [
                'product_id' => (string)$product1->getId(),
                'value' => '12.2000',
                'currency' => 'EUR',
                'price_list_id' => $this->getReference('price_list_1')->getId(),
            ],
            [
                'product_id' => (string)$product1->getId(),
                'value' => '12.2000',
                'currency' => 'USD',
                'price_list_id' => $this->getReference('price_list_2')->getId(),
            ],
        ];
        usort($expected, [$this, 'sort']);
        usort($actual, [$this, 'sort']);

        $this->assertEquals($expected, $actual);
    }

    public function testGetProductsByPriceListAndVersion()
    {
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $product3 = $this->getReference(LoadProductData::PRODUCT_3);

        $update = $this->repository->createQueryBuilder('pp')
            ->update(ProductPrice::class, 'pp')
            ->set('pp.version', 100)
            ->where('pp.priceList = :priceList')
            ->andWhere('pp.product IN(:products)')
            ->setParameters([
                'priceList' => $priceList,
                'products' => [$product1, $product3]
            ]);
        $update->getQuery()->execute();

        $productIdBatches = iterator_to_array(
            $this->repository->getProductsByPriceListAndVersion($this->shardManager, $priceList, 100, 2)
        );
        $this->assertCount(1, $productIdBatches);
        $productIds = reset($productIdBatches);
        static::assertContainsEquals($product1->getId(), $productIds, \var_export($productIds, true));
        static::assertContainsEquals($product3->getId(), $productIds, \var_export($productIds, true));
    }

    /**
     * @param string $productReference
     * @param array $priceReferences
     * @dataProvider getPricesByProductDataProvider
     */
    public function testGetPricesByProduct($productReference, array $priceReferences)
    {
        /** @var Product $product */
        $product = $this->getReference($productReference);

        $expectedPrices = [];
        foreach ($priceReferences as $priceReference) {
            $expectedPrices[] = $this->getReference($priceReference);
        }
        $exppectedResult = $this->getPriceIds($expectedPrices);
        $result = $this->getPriceIds($this->repository->getPricesByProduct($this->shardManager, $product));
        $this->assertEquals(
            sort($exppectedResult),
            sort($result)
        );
    }

    /**
     * @return array
     */
    public function getPricesByProductDataProvider()
    {
        return [
            'first product' => [
                'productReference' => 'product-1',
                'priceReferences' => [
                    'product_price.10',
                    'product_price.2',
                    'product_price.7',
                    'product_price.1',
                    'product_price.6',
                ],
            ],
            'second product' => [
                'productReference' => 'product-2',
                'priceReferences' => [
                    'product_price.13',
                    'product_price.11',
                    'product_price.8',
                    'product_price.3',
                    'product_price.12',
                    'product_price.5',
                    'product_price.4',
                    'product_price.16',
                    'product_price.15',
                ],
            ],
        ];
    }

    /**
     * @param string|null $priceList
     * @param array $products
     * @param array $expectedPrices
     * @param bool $getTierPrices
     * @param string $currency
     * @param array $orderBy
     *
     * @dataProvider findByPriceListIdAndProductIdsDataProvider
     */
    public function testFindByPriceListIdAndProductIds(
        $priceList,
        array $products,
        array $expectedPrices,
        $getTierPrices = true,
        $currency = null,
        array $orderBy = ['unit' => 'ASC', 'quantity' => 'ASC']
    ) {
        $priceListId = 1;
        if ($priceList) {
            /** @var PriceList $priceListEntity */
            $priceListEntity = $this->getReference($priceList);
            $priceListId = $priceListEntity->getId();
        }

        $productIds = [];
        foreach ($products as $product) {
            /** @var Product $productEntity */
            $productEntity = $this->getReference($product);
            $productIds[] = $productEntity->getId();
        }

        $expectedPriceIds = [];
        foreach ($expectedPrices as $price) {
            /** @var ProductPrice $priceEntity */
            $priceEntity = $this->getPriceByReference($price);
            $expectedPriceIds[] = $priceEntity->getId();
        }

        $actualPrices = $this->repository->findByPriceListIdAndProductIds(
            $this->shardManager,
            $priceListId,
            $productIds,
            $getTierPrices,
            $currency,
            null,
            $orderBy
        );

        $actualPriceIds = $this->getPriceIds($actualPrices);

        $this->assertEquals(sort($expectedPriceIds), sort($actualPriceIds));
    }

    /**
     * @return array
     */
    public function findByPriceListIdAndProductIdsDataProvider()
    {
        return [
            'empty products' => [
                'priceList' => LoadPriceLists::PRICE_LIST_1,
                'products' => [],
                'expectedPrices' => [],
            ],
            'empty products without tier prices' => [
                'priceList' => LoadPriceLists::PRICE_LIST_1,
                'products' => [],
                'expectedPrices' => [],
            ],
            'not existing price list' => [
                'priceList' => null,
                'products' => ['product-1'],
                'expectedPrices' => [],
            ],
            'not existing price list without tier prices' => [
                'priceList' => null,
                'products' => ['product-1'],
                'expectedPrices' => [],
            ],
            'first valid set' => [
                'priceList' => LoadPriceLists::PRICE_LIST_1,
                'products' => ['product-1'],
                'expectedPrices' => ['product_price.10', 'product_price.2', 'product_price.7', 'product_price.1'],
            ],
            'first valid set without tier prices' => [
                'priceList' => LoadPriceLists::PRICE_LIST_1,
                'products' => ['product-1'],
                'expectedPrices' => ['product_price.10', 'product_price.7'],
                'getTierPrices' => false
            ],
            'first valid set without tier prices with currency' => [
                'priceList' => LoadPriceLists::PRICE_LIST_1,
                'products' => ['product-1'],
                'expectedPrices' => ['product_price.10'],
                'getTierPrices' => false,
                'currency' => 'EUR'
            ],
            'second valid set' => [
                'priceList' => LoadPriceLists::PRICE_LIST_2,
                'products' => ['product-1', 'product-2'],
                'expectedPrices' => ['product_price.5', 'product_price.12', 'product_price.4', 'product_price.6'],
            ],
            'second valid set without tier prices' => [
                'priceList' => LoadPriceLists::PRICE_LIST_2,
                'products' => ['product-1', 'product-2'],
                'expectedPrices' => [],
                'getTierPrices' => false
            ],
            'second valid set with currency' => [
                'priceList' => LoadPriceLists::PRICE_LIST_2,
                'products' => ['product-1', 'product-2'],
                'expectedPrices' => ['product_price.5', 'product_price.4', 'product_price.6'],
                'getTierPrices' => true,
                'currency' => 'USD'
            ],
            'first valid set with order by currency, unit and quantity' => [
                'priceList' => LoadPriceLists::PRICE_LIST_2,
                'products' => ['product-1', 'product-2'],
                'expectedPrices' => ['product_price.5', 'product_price.4', 'product_price.6', 'product_price.12'],
                'getTierPrices' => true,
                'currency' => null,
                ['currency' => 'DESC', 'unit' => 'ASC', 'quantity' => 'ASC']
            ],
        ];
    }

    /**
     * @dataProvider getPricesBatchDataProvider
     *
     * @param string $priceList
     * @param array $products
     * @param array $productUnits
     * @param array $expectedPrices
     * @param array $currencies
     */
    public function testGetPricesBatch(
        $priceList,
        array $products,
        array $productUnits,
        array $expectedPrices,
        array $currencies = []
    ) {
        /** @var PriceList $priceListEntity */
        $priceListEntity = $this->getReference($priceList);
        $priceListId = $priceListEntity->getId();

        $productIds = [];
        foreach ($products as $product) {
            /** @var Product $productEntity */
            $productEntity = $this->getReference($product);
            $productIds[] = $productEntity->getId();
        }

        $productUnitCodes = [];
        foreach ($productUnits as $productUnit) {
            /** @var ProductUnit $productUnit */
            $productUnitEntity = $this->getReference($productUnit);
            $productUnitCodes[] = $productUnitEntity->getCode();
        }

        $expectedPriceData = [];
        foreach ($expectedPrices as $price) {
            /** @var ProductPrice $priceEntity */
            $priceEntity = $this->getReference($price);
            $expectedPriceData[] = new ProductPriceDTO(
                $priceEntity->getProduct(),
                $priceEntity->getPrice(),
                $priceEntity->getQuantity(),
                $priceEntity->getUnit()
            );
        }
        $sorter = function (ProductPriceDTO $a, ProductPriceDTO $b) {
            if ($a->getProduct()->getId() === $b->getProduct()->getId()) {
                return 0;
            }

            return ($a->getProduct()->getId() < $b->getProduct()->getId()) ? -1 : 1;
        };

        $actualPrices = $this->repository->getPricesBatch(
            $this->shardManager,
            $priceListId,
            $productIds,
            $productUnitCodes,
            $currencies
        );

        $expectedPriceData = usort($expectedPriceData, $sorter);
        $actualPrices = usort($actualPrices, $sorter);

        $this->assertEquals($expectedPriceData, $actualPrices);
    }

    /**
     * @return array
     */
    public function getPricesBatchDataProvider()
    {
        return [
            'empty' => [
                'priceList' => LoadPriceLists::PRICE_LIST_1,
                'products' => [],
                'productUnits' => [],
                'expectedPrices' => [],
            ],
            'first valid set' => [
                'priceList' => LoadPriceLists::PRICE_LIST_1,
                'products' => ['product-1', 'product-2'],
                'productUnits' => ['product_unit.liter'],
                'expectedPrices' => [
                    'product_price.7',
                    'product_price.8',
                    'product_price.1',
                    'product_price.3',
                    'product_price.11'
                ],
            ],
            'first valid set with currency' => [
                'priceList' => LoadPriceLists::PRICE_LIST_1,
                'products' => ['product-1', 'product-2'],
                'productUnits' => ['product_unit.liter'],
                'expectedPrices' => ['product_price.11'],
                'currencies' => ['EUR']
            ],
            'second valid set' => [
                'priceList' => LoadPriceLists::PRICE_LIST_2,
                'products' => ['product-2'],
                'productUnits' => ['product_unit.bottle'],
                'expectedPrices' => ['product_price.5', 'product_price.12'],
            ],
            'second valid set with currency' => [
                'priceList' => LoadPriceLists::PRICE_LIST_2,
                'products' => ['product-2'],
                'productUnits' => ['product_unit.bottle'],
                'expectedPrices' => ['product_price.5'],
                'currencies' => ['USD']
            ],
        ];
    }

    public function testDeleteByProductUnit()
    {
        /** @var Product $product */
        $product = $this->getReference('product-1');
        /** @var Product $notRemovedProduct */
        $notRemovedProduct = $this->getReference('product-2');
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.liter');

        $this->repository->deleteByProductUnit($this->shardManager, $product, $unit);

        $productPrices = $this->repository->getPricesByProduct($this->shardManager, $product);
        foreach ($productPrices as $price) {
            $this->assertNotEquals($unit, $price->getProductUnitCode());
        }

        $this->assertNotEmpty(
            $this->repository->getPricesByProduct($this->shardManager, $notRemovedProduct)
        );
    }

    public function testGetAvailableCurrencies()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $price = new Price();
        $price->setValue(1);
        $price->setCurrency('UAH');

        /** @var Product $product */
        $product = $this->getReference('product-1');

        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.liter');

        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);

        $productPrice = new ProductPrice();
        $productPrice
            ->setPrice($price)
            ->setProduct($product)
            ->setQuantity(1)
            ->setUnit($unit)
            ->setPriceList($priceList);

        $em->persist($productPrice);
        $em->flush();
    }

    public function testCountByPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_6);

        $this->assertEquals(
            2,
            $this->repository->countByPriceList($this->shardManager, $priceList)
        );
    }

    public function testDeleteByPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);

        $this->repository->deleteByPriceList($this->shardManager, $priceList);

        $this->assertEquals(0, $this->repository->countByPriceList($this->shardManager, $priceList));

        /** @var PriceList $priceList2 */
        $priceList2 = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        $this->assertEquals(3, $this->repository->countByPriceList($this->shardManager, $priceList2));

        $this->repository->deleteByPriceList($this->shardManager, $priceList2);
        $this->assertEquals(0, $this->repository->countByPriceList($this->shardManager, $priceList2));
    }

    public function testCopyPrices()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $newPriceList = new PriceList();
        $newPriceList->setName('test');

        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroPricingBundle:ProductPrice');
        $em->persist($newPriceList);
        $em->flush();

        $this->repository->copyPrices(
            $priceList,
            $newPriceList,
            $this->getContainer()->get('oro_pricing.orm.insert_from_select_query_executor')
        );

        $sourcePrices = $this->repository->findByPriceList(
            $this->shardManager,
            $priceList,
            ['priceList' => $priceList],
            ['product' => 'ASC', 'quantity' => 'ASC', 'value' => 'ASC']
        );

        $targetPrices = $this->repository->findByPriceList(
            $this->shardManager,
            $priceList,
            ['priceList' => $newPriceList],
            ['product' => 'ASC', 'quantity' => 'ASC', 'value' => 'ASC']
        );

        $priceToArrayCallback = function (BaseProductPrice $price) {
            return [
                'product' => $price->getProduct()->getId(),
                'productSku' => $price->getProductSku(),
                'quantity' => $price->getQuantity(),
                'unit' => $price->getProductUnitCode(),
                'value' => $price->getPrice()->getValue(),
                'currency' => $price->getPrice()->getCurrency(),
            ];
        };

        $sourcePricesArray = array_map($priceToArrayCallback, $sourcePrices);
        $targetPricesArray = array_map($priceToArrayCallback, $targetPrices);

        $this->assertSame($sourcePricesArray, $targetPricesArray);
    }

    public function testDeleteGeneratedPrices()
    {
        $registry = $this->getContainer()->get('doctrine');
        $manager = $registry->getManagerForClass(PriceRule::class);

        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        /** @var ProductPriceRepository $repository */
        $repository = $manager->getRepository('OroPricingBundle:ProductPrice');
        $manualPrices = $repository->findByPriceList(
            $this->shardManager,
            $priceList,
            ['priceList' => $priceList, 'priceRule' => null]
        );

        $rule = $this->createPriceListRule($priceList);
        $productPrice = $this->createProductPrice($priceList, $rule);

        $manager->persist($rule);
        $manager->persist($productPrice);
        $manager->flush();

        $repository->deleteGeneratedPrices($this->shardManager, $priceList, [$productPrice->getProduct()->getId()]);

        $actual = $repository->findByPriceList($this->shardManager, $priceList, ['priceList' => $priceList]);
        $this->assertEquals($manualPrices, $actual);
    }

    public function testDeleteInvalidPricesByProducts()
    {
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.liter');
        $price = Price::create(2, 'USD');

        $this->prepareDetachedPrices($priceList, $product1, $unit, $price);
        $this->prepareDetachedPrices($priceList, $product2, $unit, $price);
        $prices = $this->repository->findByPriceList($this->shardManager, $priceList, []);
        $this->assertCount(2, $prices);

        $this->repository->deleteInvalidPricesByProducts($this->shardManager, $priceList, [$product1->getId()]);

        $prices = $this->repository->findByPriceList($this->shardManager, $priceList, []);
        $this->assertCount(1, $prices);
    }

    public function testDeleteInvalidPrices()
    {
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.liter');
        $price = Price::create(1, 'USD');

        $this->prepareDetachedPrices($priceList, $product1, $unit, $price);
        $this->prepareDetachedPrices($priceList, $product2, $unit, $price);
        $prices = $this->repository->findByPriceList($this->shardManager, $priceList, []);
        $this->assertCount(2, $prices);

        $this->repository->deleteInvalidPrices($this->shardManager, $priceList);

        $prices = $this->repository->findByPriceList($this->shardManager, $priceList, []);
        $this->assertEmpty($prices);
    }

    public function testSave()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $unit = new ProductUnit();
        $unit->setCode('item');
        $priceValue = new Price();
        $priceValue->setCurrency('USD');
        $priceValue->setValue(1);

        $price = new ProductPrice();
        $price->setVersion(100);
        $price->setPriceList($priceList);
        $price->setProduct($product);
        $price->setQuantity(111);
        $price->setUnit($unit);
        $price->setPrice($priceValue);
        $this->repository->save($this->shardManager, $price);

        $this->assertNotNull($price->getId());

        $priceFromDb = $this->repository->findByPriceList($this->shardManager, $priceList, ['id' => $price->getId()]);
        $this->assertCount(1, $priceFromDb);

        /** @var ProductPrice $firstPriceFromDb */
        $firstPriceFromDb = reset($priceFromDb);
        $this->assertEquals($price->getVersion(), $firstPriceFromDb->getVersion());
        $this->assertEquals($price->getPriceList(), $firstPriceFromDb->getPriceList());
        $this->assertEquals($price->getProduct(), $firstPriceFromDb->getProduct());
        $this->assertEquals($price->getQuantity(), $firstPriceFromDb->getQuantity());
        $this->assertEquals($price->getPrice(), $firstPriceFromDb->getPrice());
        $this->assertEquals($price->getPriceRule(), $firstPriceFromDb->getPriceRule());
        $this->assertEquals($price->getUnit()->getCode(), $firstPriceFromDb->getUnit()->getCode());
    }

    public function testRemove()
    {
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $prices = $this->repository->findByPriceList($this->shardManager, $priceList, ['product' => $product]);
        $this->assertNotEmpty($prices);
        foreach ($prices as $price) {
            $this->repository->remove($this->shardManager, $price);
            $result = $this->repository->findByPriceList($this->shardManager, $priceList, ['id' => $price->getId()]);
            $this->assertEmpty($result);
        }
    }

    /**
     * @param array $a
     * @param array $b
     * @return bool
     */
    protected function sort(array $a, array $b)
    {
        if (!empty($a['unit']) && $a['price_list_id'] === $b['price_list_id'] && $a['currency'] === $b['currency']) {
            return $a['unit'] > $b['unit'] ? 1 : 0;
        } elseif ($a['price_list_id'] === $b['price_list_id']) {
            return $a['currency'] > $b['currency'] ? 1 : 0;
        }

        return $a['price_list_id'] > $b['price_list_id'] ? 1 : 0;
    }

    /**
     * @param ProductPrice[] $prices
     * @return array
     */
    protected function getPriceIds(array $prices)
    {
        $priceIds = [];
        foreach ($prices as $price) {
            $priceIds[] = $price->getId();
        }

        return $priceIds;
    }

    /**
     * @return int
     */
    protected function getPricesCount()
    {
        $repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository(ProductPrice::class);

        return (int)$repository->createQueryBuilder('pp')
            ->select('COUNT(pp.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param PriceList $priceList
     * @return PriceRule
     */
    protected function createPriceListRule(PriceList $priceList)
    {
        $rule = new PriceRule();
        $rule->setRule('10')
            ->setPriority(1)
            ->setQuantity(1)
            ->setPriceList($priceList)
            ->setCurrency('USD');

        return $rule;
    }

    /**
     * @param PriceList $priceList
     * @param PriceRule $rule
     * @param string $currency
     * @return ProductPrice
     */
    protected function createProductPrice(PriceList $priceList, PriceRule $rule, $currency = 'USD')
    {
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.box');
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $productPrice = new ProductPrice();
        $productPrice->setPriceList($priceList)
            ->setPrice(Price::create(1, $currency))
            ->setQuantity(1)
            ->setPriceRule($rule)
            ->setUnit($unit)
            ->setProduct($product);

        return $productPrice;
    }

    /**
     * @param PriceList $priceList
     * @param Product $product
     * @param string $unit
     * @param Price $price
     */
    protected function prepareDetachedPrices(PriceList $priceList, Product $product, $unit, Price $price)
    {
        $objectRepository = $this->getContainer()->get('doctrine')
            ->getRepository(PriceListToProduct::class);

        $productPrice = new ProductPrice();
        $productPrice->setPriceList($priceList)
            ->setUnit($unit)
            ->setQuantity(1)
            ->setPrice($price)
            ->setProduct($product);
        $this->repository->save($this->shardManager, $productPrice);

        $objectRepository->createQueryBuilder('productRelation')
            ->delete(PriceListToProduct::class, 'productRelation')
            ->where('productRelation.priceList = :priceList AND productRelation.product = :product')
            ->setParameter('priceList', $priceList)
            ->setParameter('product', $product)
            ->getQuery()
            ->execute();
    }
}
