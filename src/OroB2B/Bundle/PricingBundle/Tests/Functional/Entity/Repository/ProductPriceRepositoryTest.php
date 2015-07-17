<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * @dbIsolation
 */
class ProductPriceRepositoryTest extends WebTestCase
{
    /**
     * @var ProductPriceRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists'
            ]
        );

        $this->repository = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BPricingBundle:ProductPrice');
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
            $this->getPriceIds($this->repository->getPricesByProduct($product))
        );
    }

    /**
     * @return array
     */
    public function getPricesByProductDataProvider()
    {
        return [
            'first product' => [
                'productReference' => 'product.1',
                'priceReferences' => ['product_price.2', 'product_price.7', 'product_price.1', 'product_price.6'],
            ],
            'second product' => [
                'productReference' => 'product.2',
                'priceReferences' => ['product_price.3', 'product_price.5', 'product_price.4'],
            ],
        ];
    }

    /**
     * @param string|null $priceList
     * @param array $products
     * @param array $expectedPrices
     * @param bool $getTierPrices
     *
     * @dataProvider findByPriceListIdAndProductIdsDataProvider
     */
    public function testFindByPriceListIdAndProductIds(
        $priceList,
        array $products,
        array $expectedPrices,
        $getTierPrices = true
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

        $actualPrices = $this->repository->findByPriceListIdAndProductIds($priceListId, $productIds, $getTierPrices);
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
                'priceList' => 'price_list_1',
                'products' => [],
                'expectedPrices' => [],
            ],
            'empty products without tier prices' => [
                'priceList' => 'price_list_1',
                'products' => [],
                'expectedPrices' => [],
            ],
            'not existing price list' => [
                'priceList' => null,
                'products' => ['product.1'],
                'expectedPrices' => [],
            ],
            'not existing price list without tier prices' => [
                'priceList' => null,
                'products' => ['product.1'],
                'expectedPrices' => [],
            ],
            'first valid set' => [
                'priceList' => 'price_list_1',
                'products' => ['product.1'],
                'expectedPrices' => ['product_price.2', 'product_price.1', 'product_price.7'],
            ],
            'first valid set without tier prices' => [
                'priceList' => 'price_list_1',
                'products' => ['product.1'],
                'expectedPrices' => ['product_price.7'],
                'getTierPrices' => false
            ],
            'second valid set' => [
                'priceList' => 'price_list_2',
                'products' => ['product.1', 'product.2'],
                'expectedPrices' => ['product_price.5', 'product_price.4', 'product_price.6', 'product_price.7'],
            ],
            'second valid set without tier prices' => [
                'priceList' => 'price_list_2',
                'products' => ['product.1', 'product.2'],
                'expectedPrices' => ['product_price.7'],
                'getTierPrices' => false
            ],
        ];
    }

    public function testDeleteByProductUnit()
    {
        /** @var Product $product */
        $product = $this->getReference('product.1');
        /** @var Product $notRemovedProduct */
        $notRemovedProduct = $this->getReference('product.2');
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.liter');
        /** @var ProductUnit $unit */
        $notRemovedUnit = $this->getReference('product_unit.bottle');

        $this->repository->deleteByProductUnit($product, $unit);

        $this->assertEmpty(
            $this->repository->findBy(
                [
                    'product' => $product,
                    'unit' => $unit
                ]
            )
        );

        $this->assertNotEmpty(
            $this->repository->findBy(
                [
                    'product' => $notRemovedProduct,
                    'unit' => $unit
                ]
            )
        );

        $this->assertNotEmpty(
            $this->repository->findBy(
                [
                    'product' => $product,
                    'unit' => $notRemovedUnit
                ]
            )
        );
    }

    public function testGetAvailableCurrencies()
    {
        $this->assertEquals(
            ['EUR' => 'EUR', 'USD' => 'USD'],
            $this->repository->getAvailableCurrencies()
        );

        $em = $this->getContainer()->get('doctrine')->getManager();

        $price = new Price();
        $price->setValue(1);
        $price->setCurrency('UAH');

        /** @var Product $product */
        $product = $this->getReference('product.1');

        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.liter');

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $productPrice = new ProductPrice();
        $productPrice
            ->setPrice($price)
            ->setProduct($product)
            ->setQuantity(1)
            ->setUnit($unit)
            ->setPriceList($priceList);

        $em->persist($productPrice);
        $em->flush();

        $this->assertEquals(
            ['EUR' => 'EUR', 'UAH' => 'UAH', 'USD' => 'USD'],
            $this->repository->getAvailableCurrencies()
        );
    }

    public function testCountByPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $this->assertCount(
            $this->repository->countByPriceList($priceList),
            $this->repository->findBy(['priceList' => $priceList->getId()])
        );
    }

    public function testDeleteByPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $this->repository->deleteByPriceList($priceList);

        $this->assertEmpty($this->repository->findBy(['priceList' => $priceList->getId()]));

        /** @var PriceList $priceList2 */
        $priceList2 = $this->getReference('price_list_2');
        $this->assertNotEmpty($this->repository->findBy(['priceList' => $priceList2->getId()]));

        $this->repository->deleteByPriceList($priceList2);
        $this->assertEmpty($this->repository->findBy(['priceList' => $priceList2->getId()]));
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
}
