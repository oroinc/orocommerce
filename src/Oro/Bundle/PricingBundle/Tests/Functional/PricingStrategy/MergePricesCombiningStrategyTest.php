<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\PricingStrategy;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\PricingStrategy\MergePricesCombiningStrategy;

/**
 * @dbIsolationPerTest
 */
class MergePricesCombiningStrategyTest extends AbstractPricesCombiningStrategyTest
{
    protected function getPricingStrategyName(): string
    {
        return MergePricesCombiningStrategy::NAME;
    }

    public function testEmptyPriceLists()
    {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference('1e');
        $this->pricingStrategy->combinePrices($combinedPriceList);

        $this->assertCombinedPriceListContainsPrices($combinedPriceList, []);
    }

    /**
     * @dataProvider combinePricesDataProvider
     */
    public function testCombinePrices(string $combinedPriceList, array $expectedPrices)
    {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceList);
        $this->pricingStrategy->combinePrices($combinedPriceList);

        $this->assertTrue($combinedPriceList->isPricesCalculated());
        $this->assertNotEmpty($this->getCombinedPrices($combinedPriceList));
        $this->assertCombinedPriceListContainsPrices($combinedPriceList, $expectedPrices);
    }

    /**
     * @return array
     */
    public function combinePricesDataProvider(): array
    {
        return [
            [
                '1t_2t_3t',
                [
                    'product-1' => [
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter'
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                        '10 USD/10 bottle'
                    ]
                ],
            ],
            [
                '2t_3f_1t',
                [
                    'product-1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                    ],
                    'product-2' => [
                        '10 USD/10 bottle'
                    ]
                ],
            ],
            [
                '2f_1t_3t',
                [
                    'product-1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                        '10 USD/10 bottle'
                    ]
                ],
            ]
        ];
    }

    /**
     * @dataProvider addedCombinePricesDataProvider
     */
    public function testCombinePricesByProductPriceAdded(string $combinedPriceList, array $expectedPrices)
    {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceList);
        $this->pricingStrategy->combinePrices($combinedPriceList);
        self::getMessageCollector()->clear();

        $price = $this->addProductPrice('price_list_2', 'product-2', 1, 'product_unit.liter', Price::create(42, 'EUR'));

        $product = $price->getProduct();
        $this->pricingStrategy->combinePrices($combinedPriceList, [$product->getId()]);

        $this->assertCombinedPriceListContainsPrices($combinedPriceList, $expectedPrices);
    }

    /**
     * @return array
     */
    public function addedCombinePricesDataProvider(): array
    {
        return [
            [
                '1t_2t_3t',
                [
                    'product-1' => [
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter'
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                        '42 EUR/1 liter',
                        '10 USD/10 bottle',
                    ]
                ]
            ],
            [
                '2t_3f_1t',
                [
                    'product-1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                        '42 EUR/1 liter',
                    ]
                ]
            ],
            [
                '2f_1t_3t',
                [
                    'product-1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                    ],
                    'product-2' => [
                        '42 EUR/1 liter',
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider updatedCombinePricesDataProvider
     */
    public function testCombinePricesByProductPriceUpdate(string $combinedPriceList, array $expectedPrices)
    {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceList);
        $this->pricingStrategy->combinePrices($combinedPriceList);
        self::getMessageCollector()->clear();

        /** @var ProductPrice $price */
        $price = $this->getPriceByReference('product_price.7');
        $price->getPrice()->setValue(22);
        $this->saveProductPrice($price);
        $product = $price->getProduct();

        $this->pricingStrategy->combinePrices($combinedPriceList, [$product->getId()]);

        $this->assertCombinedPriceListContainsPrices($combinedPriceList, $expectedPrices);
    }

    /**
     * @return array
     */
    public function updatedCombinePricesDataProvider(): array
    {
        return [
            [
                '1t_2t_3t',
                [
                    'product-1' => [
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter'
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                        '22 USD/10 bottle'
                    ]
                ]
            ],
            [
                '2t_3f_1t',
                [
                    'product-1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                    ],
                    'product-2' => [
                        '22 USD/10 bottle'
                    ]
                ]
            ],
            [
                '2f_1t_3t',
                [
                    'product-1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                        '22 USD/10 bottle'
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider removedCombinePricesDataProvider
     */
    public function testCombinePricesByProductPriceRemoved(string $combinedPriceList, array $expectedPrices)
    {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceList);
        $this->pricingStrategy->combinePrices($combinedPriceList);
        self::getMessageCollector()->clear();

        /** @var ProductPrice $price */
        $price = $this->getPriceByReference('product_price.7');
        $product = $price->getProduct();

        $this->removeProductPrice($price);

        $this->pricingStrategy->combinePrices($combinedPriceList, [$product->getId()]);

        $this->assertCombinedPriceListContainsPrices($combinedPriceList, $expectedPrices);
    }

    /**
     * @return array
     */
    public function removedCombinePricesDataProvider(): array
    {
        return [
            [
                '1t_2t_3t',
                [
                    'product-1' => [
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter'
                    ],
                    'product-2' => [
                        '1 USD/1 bottle'
                    ]
                ]
            ],
            [
                '2t_3f_1t',
                [
                    'product-1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                    ],
                    'product-2' => [
                        '1 USD/1 bottle'
                    ]
                ]
            ],
            [
                '2f_1t_3t',
                [
                    'product-1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                    ]
                ]
            ]
        ];
    }
}
