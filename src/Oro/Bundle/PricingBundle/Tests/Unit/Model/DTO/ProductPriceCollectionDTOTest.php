<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;

class ProductPriceCollectionDTOTest extends TestCase
{
    private const USD = 'USD';

    /**
     * @dataProvider getMatchingByCriteriaDataProvider
     */
    public function testGetMatchingByCriteria(array $productPrices, array $criteria, array $expected): void
    {
        $productPriceCollection = new ProductPriceCollectionDTO($productPrices);

        self::assertEquals(
            $expected,
            iterator_to_array($productPriceCollection->getMatchingByCriteria(...$criteria), false)
        );
    }

    public function getMatchingByCriteriaDataProvider(): array
    {
        $product1 = (new ProductStub())->setId(10);
        $product2 = (new ProductStub())->setId(20);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productUnitSet = (new ProductUnit())->setCode('set');

        $product1Price1 = new ProductPriceDTO($product1, Price::create(1.2345, self::USD), 1, $productUnitItem);
        $product1Price2 = new ProductPriceDTO($product1, Price::create(0.2345, self::USD), 5, $productUnitItem);
        $product1Price3 = new ProductPriceDTO($product1, Price::create(2.2345, self::USD), 1, $productUnitSet);
        $product2Price1 = new ProductPriceDTO($product2, Price::create(10.1234, self::USD), 1, $productUnitItem);
        $product2Price2 = new ProductPriceDTO($product2, Price::create(8.9012, self::USD), 1, $productUnitSet);
        $product2Price3 = new ProductPriceDTO($product2, Price::create(4.5678, self::USD), 5, $productUnitSet);

        $productPrices = [
            $product1Price1,
            $product1Price2,
            $product1Price3,
            $product2Price1,
            $product2Price2,
            $product2Price3,
        ];

        return [
            'no product prices' => [
                'productPrices' => [],
                'criteria' => [42],
                'expected' => [],
            ],
            'empty criteria' => [
                'productPrices' => $productPrices,
                'criteria' => [],
                'expected' => $productPrices,
            ],
            'with product id' => [
                'productPrices' => $productPrices,
                'criteria' => [$product2->getId()],
                'expected' => [
                    $product2Price1,
                    $product2Price2,
                    $product2Price3,
                ],
            ],
            'with missing product id' => [
                'productPrices' => $productPrices,
                'criteria' => [42],
                'expected' => [],
            ],
            'with unit code' => [
                'productPrices' => $productPrices,
                'criteria' => [$product2->getId(), $productUnitItem->getCode()],
                'expected' => [
                    $product2Price1,
                ],
            ],
            'with missing unit code' => [
                'productPrices' => $productPrices,
                'criteria' => [$product2->getId(), 'missing'],
                'expected' => [],
            ],
            'with currency' => [
                'productPrices' => $productPrices,
                'criteria' => [$product2->getId(), $productUnitSet->getCode(), self::USD],
                'expected' => [
                    $product2Price2,
                    $product2Price3,
                ],
            ],
            'with missing currency' => [
                'productPrices' => $productPrices,
                'criteria' => [$product2->getId(), $productUnitSet->getCode(), 'GBP'],
                'expected' => [],
            ],
        ];
    }

    public function testGetProductPricesMap(): void
    {
        $product1 = (new ProductStub())->setId(10);
        $product2 = (new ProductStub())->setId(20);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productUnitSet = (new ProductUnit())->setCode('set');

        $product1Price1 = new ProductPriceDTO($product1, Price::create(1.2345, self::USD), 1, $productUnitItem);
        $product1Price2 = new ProductPriceDTO($product1, Price::create(0.2345, self::USD), 5, $productUnitItem);
        $product1Price3 = new ProductPriceDTO($product1, Price::create(2.2345, self::USD), 1, $productUnitSet);
        $product2Price1 = new ProductPriceDTO($product2, Price::create(10.1234, self::USD), 1, $productUnitItem);
        $product2Price2 = new ProductPriceDTO($product2, Price::create(8.9012, self::USD), 1, $productUnitSet);
        $product2Price3 = new ProductPriceDTO($product2, Price::create(4.5678, self::USD), 5, $productUnitSet);

        $productPrices = [
            $product1Price1,
            $product1Price2,
            $product1Price3,
            $product2Price1,
            $product2Price2,
            $product2Price3,
        ];

        $productPriceCollection = new ProductPriceCollectionDTO($productPrices);

        self::assertEquals(
            [
                $product1->getId() => [
                    $productUnitItem->getCode() => [
                        self::USD => [
                            $product1Price1,
                            $product1Price2,
                        ],
                    ],
                    $productUnitSet->getCode() => [
                        self::USD => [
                            $product1Price3,
                        ],
                    ],
                ],
                $product2->getId() => [
                    $productUnitItem->getCode() => [
                        self::USD => [
                            $product2Price1,
                        ],
                    ],
                    $productUnitSet->getCode() => [
                        self::USD => [
                            $product2Price2,
                            $product2Price3,
                        ],
                    ],
                ],
            ],
            $productPriceCollection->getProductPricesMap()
        );
    }

    public function testWithCustomInitCallback(): void
    {
        $product1 = (new ProductStub())->setId(10);
        $product2 = (new ProductStub())->setId(20);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productUnitSet = (new ProductUnit())->setCode('set');

        $product1Price1 = new ProductPriceDTO($product1, Price::create(1.2345, self::USD), 1, $productUnitItem);
        $product1Price2 = new ProductPriceDTO($product1, Price::create(0.2345, self::USD), 5, $productUnitItem);
        $product1Price3 = new ProductPriceDTO($product1, Price::create(2.2345, self::USD), 1, $productUnitSet);
        $product2Price1 = new ProductPriceDTO($product2, Price::create(10.1234, self::USD), 1, $productUnitItem);
        $product2Price2 = new ProductPriceDTO($product2, Price::create(8.9012, self::USD), 1, $productUnitSet);
        $product2Price3 = new ProductPriceDTO($product2, Price::create(4.5678, self::USD), 5, $productUnitSet);

        $productPrices = [
            $product1Price1,
            $product1Price2,
            $product1Price3,
            $product2Price1,
            $product2Price2,
            $product2Price3,
        ];

        $productPriceCollection = new ProductPriceCollectionDTO($productPrices);
        $productPriceCollection->setProductPricesMapInitCallback(static function (array $productPrices) {
            $productPricesMap = [];
            foreach ($productPrices as $productPrice) {
                $product = $productPrice->getProduct();
                $unit = $productPrice->getUnit();
                $price = $productPrice->getPrice();
                $productPricesMap[$price->getCurrency()][$product->getId()][$unit->getCode()][] =
                    $productPrice;
            }

            return $productPricesMap;
        });

        self::assertEquals(
            [
                self::USD => [
                    $product1->getId() => [
                        $productUnitItem->getCode() => [
                            $product1Price1,
                            $product1Price2,
                        ],
                        $productUnitSet->getCode() => [
                            $product1Price3,
                        ],
                    ],
                    $product2->getId() => [
                        $productUnitItem->getCode() => [
                            $product2Price1,
                        ],
                        $productUnitSet->getCode() => [
                            $product2Price2,
                            $product2Price3,
                        ],
                    ],
                ],
            ],
            $productPriceCollection->getProductPricesMap()
        );

        self::assertEquals(
            [
                $product1Price1,
                $product1Price2,
                $product1Price3,
            ],
            iterator_to_array($productPriceCollection->getMatchingByCriteria(self::USD, $product1->getId()), false)
        );
    }
}
