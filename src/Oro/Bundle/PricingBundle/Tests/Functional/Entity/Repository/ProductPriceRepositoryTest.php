<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductPriceRepositoryTest extends WebTestCase
{
    /**
     * @var ProductPriceRepository
     */
    protected $repository;

    /**
     * @var QueryHintResolverInterface
     */
    protected $hintResolver;


    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                LoadProductPrices::class,
            ]
        );

        $this->repository = $this->getContainer()->get('doctrine')
            ->getRepository(ProductPrice::class);

        $this->hintResolver = $this->getContainer()->get('oro_entity.query_hint_resolver');
    }

    /**
     * @dataProvider unitDataProvider
     * @param string $priceList
     * @param string $product
     * @param null|string $currency
     * @param array $expected
     */
    public function testGetProductUnitsByPriceList($priceList, $product, $currency = null, array $expected = [])
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceList);
        /** @var Product $product */
        $product = $this->getReference($product);

        $units = $this->repository->getProductUnitsByPriceList($this->hintResolver, $priceList, $product, $currency);
        $this->assertCount(count($expected), $units);
        foreach ($units as $unit) {
            $this->assertContains($unit->getCode(), $expected);
        }
    }

    /**
     * @return array
     */
    public function unitDataProvider()
    {
        return [
            [
                LoadPriceLists::PRICE_LIST_1,
                'product-1',
                null,
                ['liter', 'bottle']
            ],
            [
                LoadPriceLists::PRICE_LIST_1,
                'product-1',
                'EUR',
                ['bottle']
            ]
        ];
    }

    /**
     * @dataProvider getProductsUnitsByPriceListDataProvider
     * @param string $priceList
     * @param array $products
     * @param null|string $currency
     * @param array $expected
     */
    public function testGetProductsUnitsByPriceList($priceList, array $products, $currency = null, array $expected = [])
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceList);

        $productsCollection = new ArrayCollection();

        foreach ($products as $productName) {
            /** @var Product $product */
            $product = $this->getReference($productName);
            $productsCollection->add($product);
        }

        $actual = $this->repository->getProductsUnitsByPriceList(
            $this->hintResolver,
            $priceList,
            $productsCollection,
            $currency
        );

        $expectedData = [];
        foreach ($expected as $productName => $units) {
            $product = $this->getReference($productName);
            $expectedData[$product->getId()] = $units;
        }

        $this->assertEquals($expectedData, $actual);
    }

    /**
     * @return array
     */
    public function getProductsUnitsByPriceListDataProvider()
    {
        return [
            [
                'priceList' => LoadPriceLists::PRICE_LIST_1,
                'products' => [
                    'product-1',
                    'product-2',
                    'product-3'
                ],
                'currency' => 'USD',
                'expected' => [
                    'product-1' => ['liter'],
                    'product-2' => ['liter'],
                    'product-3' => ['liter'],
                ]
            ],
            [
                'priceList' => LoadPriceLists::PRICE_LIST_1,
                'products' => [
                    'product-1',
                    'product-2',
                    'product-3'
                ],
                'currency' => 'EUR',
                'expected' => [
                    'product-1' => ['bottle'],
                    'product-2' => ['liter']
                ]
            ]
        ];
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

        $this->assertEquals(
            $this->getPriceIds($expectedPrices),
            $this->getPriceIds($this->repository->getPricesByProduct($this->hintResolver, $product))
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
        $priceListId = -1;
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
            $priceEntity = $this->getReference($price);
            $expectedPriceIds[] = $priceEntity->getId();
        }

        $actualPrices = $this->repository->findByPriceListIdAndProductIds(
            $this->hintResolver,
            $priceListId,
            $productIds,
            $getTierPrices,
            $currency,
            null,
            $orderBy
        );

        $actualPriceIds = $this->getPriceIds($actualPrices);

        $this->assertEquals($expectedPriceIds, $actualPriceIds);
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
            $expectedPriceData[] = [
                'id' => $priceEntity->getProduct()->getId(),
                'code' => $priceEntity->getUnit()->getCode(),
                'quantity' => $priceEntity->getQuantity(),
                'value' => $priceEntity->getPrice()->getValue(),
                'currency' => $priceEntity->getPrice()->getCurrency(),
            ];
        }
        $sorter = function ($a, $b) {
            if ($a['id'] === $b['id']) {
                return 0;
            }
            return ($a['id'] < $b['id']) ? -1 : 1;
        };

        $actualPrices = $this->repository->getPricesBatch(
            $this->hintResolver,
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

        $this->repository->deleteByProductUnit($this->hintResolver, $product, $unit);

        $productPrices = $this->repository->getPricesByProduct($this->hintResolver, $product);
        foreach ($productPrices as $price) {
            $this->assertNotEquals($unit, $price->getProductUnitCode());
        }

        $this->assertNotEmpty(
            $this->repository->getPricesByProduct($this->hintResolver, $notRemovedProduct)
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
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);

        $this->assertEquals(
            7,
            $this->repository->countByPriceList($this->hintResolver, $priceList)
        );
    }

    public function testDeleteByPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);

        $this->repository->deleteByPriceList($this->hintResolver, $priceList);

        $this->assertEquals(0, $this->repository->countByPriceList($this->hintResolver, $priceList));

        /** @var PriceList $priceList2 */
        $priceList2 = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        $this->assertEquals(3, $this->repository->countByPriceList($this->hintResolver, $priceList2));

        $this->repository->deleteByPriceList($this->hintResolver, $priceList2);
        $this->assertEquals(0, $this->repository->countByPriceList($this->hintResolver, $priceList2));
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
            $this->hintResolver,
            $priceList,
            ['priceList' => $priceList],
            ['product' => 'ASC', 'quantity' => 'ASC', 'value' => 'ASC']
        );

        $targetPrices = $this->repository->findByPriceList(
            $this->hintResolver,
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
            $this->hintResolver,
            $priceList,
            ['priceList' => $priceList, 'priceRule' => null]
        );

        $rule = $this->createPriceListRule($priceList);
        $productPrice = $this->createProductPrice($priceList, $rule);

        $manager->persist($rule);
        $manager->persist($productPrice);
        $manager->flush();

        $repository->deleteGeneratedPrices($this->hintResolver, $priceList, $productPrice->getProduct());

        $actual = $repository->findByPriceList($this->hintResolver, $priceList, ['priceList' => $priceList]);
        $this->assertEquals($manualPrices, $actual);
    }

    public function testDeleteInvalidPrices()
    {
        $objectRepository = $this->getContainer()->get('doctrine')
            ->getRepository(PriceListToProduct::class);

        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $product = $this->getReference(LoadProductData::PRODUCT_2);

        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.liter');
        $price = Price::create(1, 'USD');

        $productPrice = new ProductPrice();
        $productPrice
            ->setPriceList($priceList)
            ->setUnit($unit)
            ->setQuantity(1)
            ->setPrice($price)
            ->setProduct($product);
        $this->repository->persist($this->hintResolver, $productPrice);

        $objectRepository
            ->createQueryBuilder('productRelation')
            ->delete(PriceListToProduct::class, 'productRelation')
            ->where('productRelation.priceList = :priceList AND productRelation.product = :product')
            ->setParameter('priceList', $priceList)
            ->setParameter('product', $product)
            ->getQuery()
            ->execute();
        $prices = $this->repository->findByPriceList($this->hintResolver, $priceList, ['priceList' => $priceList]);
        $this->assertNotEmpty($prices);

        $this->repository->deleteInvalidPrices($this->hintResolver, $priceList);

        $prices = $this->repository->findByPriceList($this->hintResolver, $priceList, ['priceList' => $priceList]);
        $this->assertEmpty($prices);
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
            ->getRepository('OroPricingBundle:ProductPrice');
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
}
