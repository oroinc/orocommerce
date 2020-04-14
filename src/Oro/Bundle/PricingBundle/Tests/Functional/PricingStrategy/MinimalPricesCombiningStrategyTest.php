<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\PricingStrategy;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\PricingStrategy\MinimalPricesCombiningStrategy;

class MinimalPricesCombiningStrategyTest extends MergePricesCombiningStrategyTest
{
    /** @var MinimalPricesCombiningStrategy */
    protected $resolver;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = $this->getContainer()->get('oro_pricing.pricing_strategy.strategy_register')
            ->get('minimal_prices');
    }

    /**
     * @return array
     */
    public function combinePricesDataProvider()
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
                        '10 USD/10 bottle',
                    ]
                ],
            ],
            [
                '2t_3f_1t',
                [
                    'product-1' => [
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter',
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                        '10 USD/10 bottle',
                    ]
                ],
            ],
            [
                '2f_1t_3t',
                [
                    'product-1' => [
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter',
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
     * @return array
     */
    public function addedCombinePricesDataProvider()
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
                        '15 USD/10 liter',
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
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter',
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                        '42 EUR/1 liter',
                        '10 USD/10 bottle',
                    ]
                ]
            ],
            [
                '2f_1t_3t',
                [
                    'product-1' => [
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter',
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                        '42 EUR/1 liter',
                        '10 USD/10 bottle',
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function updatedCombinePricesDataProvider()
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
                        '15 USD/10 liter',
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                        '22 USD/10 bottle',
                    ]
                ]
            ],
            [
                '2t_3f_1t',
                [
                    'product-1' => [
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter',
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                        '22 USD/10 bottle',
                    ]
                ]
            ],
            [
                '2f_1t_3t',
                [
                    'product-1' => [
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter',
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                        '22 USD/10 bottle',
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function removedCombinePricesDataProvider()
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
                        '15 USD/10 liter',
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
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter',
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
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter',
                    ],
                    'product-2' => [
                        '1 USD/1 bottle',
                    ]
                ]
            ]
        ];
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @param array|ProductPrice[] $prices
     * @return array
     */
    protected function formatPrices(array $prices)
    {
        $actualPrices = [];
        foreach ($prices as $price) {
            $actualPrices[$price->getProduct()->getSku()][] = sprintf(
                '%d %s/%d %s',
                $price->getPrice()->getValue(),
                $price->getPrice()->getCurrency(),
                $price->getQuantity(),
                $price->getProductUnitCode()
            );
        }

        return $actualPrices;
    }
}
