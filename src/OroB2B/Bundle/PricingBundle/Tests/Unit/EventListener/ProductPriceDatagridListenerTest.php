<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;

class ProductPriceDatagridListenerTest extends AbstractProductPriceDatagridListenerTest
{
    /**
     * {@inheritDoc}
     */
    protected function getListenerClassName()
    {
        return 'OroB2B\Bundle\PricingBundle\EventListener\ProductPriceDatagridListener';
    }

    /**
     * {@inheritDoc}
     */
    protected function setUpPriceListRequestHandler($priceListId = null, array $priceCurrencies = [])
    {
        $this->priceListRequestHandler
            ->expects($this->any())
            ->method('getPriceList')
            ->willReturn($this->getPriceList($priceListId));

        $this->priceListRequestHandler
            ->expects($this->any())
            ->method('getPriceListSelectedCurrencies')
            ->will(
                $this->returnCallback(
                    function () use ($priceCurrencies) {
                        return array_intersect(['USD', 'EUR'], $priceCurrencies);
                    }
                )
            );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function onBuildBeforeDataProvider()
    {
        return [
            'no request' => [],
            'no price list id' => [
                'priceCurrencies' => ['USD'],
            ],
            'no currencies' => [
                'priceListId' => 1,
            ],
            'invalid currencies' => [
                'priceListId' => 1,
                'priceCurrencies' => ['@#$', '%^&'],
            ],
            'valid currencies' => [
                'priceListId' => 1,
                'priceCurrencies' => ['USD', 'EUR'],
                'expectedConfig' => [
                    'columns' => [
                        'price_column_usd' => [
                            'label' => 'orob2b.pricing.productprice.price_in_USD.trans',
                            'type' => 'twig',
                            'template' => 'OroB2BPricingBundle:Datagrid:Column/productPrice.html.twig',
                            'frontend_type' => 'html',
                            'renderable' => true,
                        ],
                        'price_column_eur' => [
                            'label' => 'orob2b.pricing.productprice.price_in_EUR.trans',
                            'type' => 'twig',
                            'template' => 'OroB2BPricingBundle:Datagrid:Column/productPrice.html.twig',
                            'frontend_type' => 'html',
                            'renderable' => true,
                        ],
                        'price_column_usd_unit1' => [
                            'label' => 'orob2b.pricing.productprice.price_unit1_in_USD.trans',
                            'type' => 'twig',
                            'template' => 'OroB2BPricingBundle:Datagrid:Column/productPrice.html.twig',
                            'frontend_type' => 'html',
                            'renderable' => false,
                        ],
                        'price_column_eur_unit1' => [
                            'label' => 'orob2b.pricing.productprice.price_unit1_in_EUR.trans',
                            'type' => 'twig',
                            'template' => 'OroB2BPricingBundle:Datagrid:Column/productPrice.html.twig',
                            'frontend_type' => 'html',
                            'renderable' => false,
                        ],
                    ],
                    'filters' => [
                        'columns' => [
                            'price_column_usd' => [
                                'type' => 'product-price',
                                'data_name' => 'USD'
                            ],
                            'price_column_eur' => [
                                'type' => 'product-price',
                                'data_name' => 'EUR'
                            ],
                            'price_column_usd_unit1' => [
                                'type' => 'number-range',
                                'data_name' => 'price_column_usd_unit1',
                                'enabled' => false
                            ],
                            'price_column_eur_unit1' => [
                                'type' => 'number-range',
                                'data_name' => 'price_column_eur_unit1',
                                'enabled' => false
                            ],
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            'price_column_usd' => [
                                'data_name' => 'price_column_usd',
                                'type' => PropertyInterface::TYPE_CURRENCY,
                            ],
                            'price_column_eur' => [
                                'data_name' => 'price_column_eur',
                                'type' => PropertyInterface::TYPE_CURRENCY,
                            ],
                            'price_column_usd_unit1' => [
                                'data_name' => 'price_column_usd_unit1',
                                'type' => PropertyInterface::TYPE_CURRENCY,
                            ],
                            'price_column_eur_unit1' => [
                                'data_name' => 'price_column_eur_unit1',
                                'type' => PropertyInterface::TYPE_CURRENCY,
                            ],
                        ]
                    ],
                    'source' => [
                        'query' => [
                            'select' => [
                                0 => 'min(price_column_usd_table.value) as price_column_usd',
                                1 => 'min(price_column_eur_table.value) as price_column_eur',
                                2 => 'price_column_usd_unit1_table.value as price_column_usd_unit1',
                                3 => 'price_column_eur_unit1_table.value as price_column_eur_unit1',
                            ],
                            'join' => [
                                'left' => [
                                    0 => [
                                        'join' => 'OroB2BPricingBundle:ProductPrice',
                                        'alias' => 'price_column_usd_table',
                                        'conditionType' => 'WITH',
                                        'condition' => 'price_column_usd_table.product = product.id ' .
                                            'AND price_column_usd_table.currency = \'USD\' ' .
                                            'AND price_column_usd_table.priceList = 1 ' .
                                            'AND price_column_usd_table.quantity = 1',
                                    ],
                                    1 => [
                                        'join' => 'OroB2BPricingBundle:ProductPrice',
                                        'alias' => 'price_column_eur_table',
                                        'conditionType' => 'WITH',
                                        'condition' => 'price_column_eur_table.product = product.id ' .
                                            'AND price_column_eur_table.currency = \'EUR\' ' .
                                            'AND price_column_eur_table.priceList = 1 ' .
                                            'AND price_column_eur_table.quantity = 1',
                                    ],
                                    2 => [
                                        'join' => 'OroB2BPricingBundle:ProductPrice',
                                        'alias' => 'price_column_usd_unit1_table',
                                        'conditionType' => 'WITH',
                                        'condition' => 'price_column_usd_unit1_table.product = product.id ' .
                                            'AND price_column_usd_unit1_table.currency = \'USD\' ' .
                                            'AND price_column_usd_unit1_table.priceList = 1 ' .
                                            'AND price_column_usd_unit1_table.quantity = 1 ' .
                                            'AND price_column_usd_unit1_table.unit = \'unit1\'' ,
                                    ],
                                    3 => [
                                        'join' => 'OroB2BPricingBundle:ProductPrice',
                                        'alias' => 'price_column_eur_unit1_table',
                                        'conditionType' => 'WITH',
                                        'condition' => 'price_column_eur_unit1_table.product = product.id ' .
                                            'AND price_column_eur_unit1_table.currency = \'EUR\' ' .
                                            'AND price_column_eur_unit1_table.priceList = 1 ' .
                                            'AND price_column_eur_unit1_table.quantity = 1 ' .
                                            'AND price_column_eur_unit1_table.unit = \'unit1\'',
                                    ],
                                ],
                            ],
                        ]
                    ]
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function onResultAfterDataProvider()
    {
        $unit = $this->getUnit('unit1');

        return [
            'no request' => [],
            'no price list id' => [
                'priceCurrencies' => ['USD'],
            ],
            'no currencies' => [
                'priceListId' => 1,
            ],
            'invalid currencies' => [
                'priceListId' => 1,
                'priceCurrencies' => ['@#$', '%^&'],
            ],
            'valid data' => [
                'priceListId' => 1,
                'priceCurrencies' => ['USD', 'EUR'],
                'sourceResults' => [
                    [
                        'id' => 1,
                        'name' => 'first',
                        'price_column_usd_unit1' => 15,
                    ],
                    [
                        'id' => 2,
                        'name' => 'second',
                        'price_column_eur_unit1' => 22,
                    ],
                    [
                        'id' => 3,
                        'name' => 'third',
                    ],
                ],
                'prices' => [
                    $this->createPrice(1, 10, 'USD', $unit),
                    $this->createPrice(1, 11, 'EUR'),
                    $this->createPrice(1, 12, 'EUR', $unit),
                    $this->createPrice(2, 20, 'USD'),
                ],
                'expectedResults' => [
                    [
                        'id' => 1,
                        'name' => 'first',
                        'price_column_usd' => [$this->createPrice(1, 10, 'USD', $unit)],
                        'price_column_eur' => [
                            $this->createPrice(1, 11, 'EUR'),
                            $this->createPrice(1, 12, 'EUR', $unit)
                        ],
                        'price_column_usd_unit1' => [$this->createPrice(1, 10, 'USD', $unit)],
                        'showTierPrices' => true
                    ],
                    [
                        'id' => 2,
                        'name' => 'second',
                        'price_column_usd' => [$this->createPrice(2, 20, 'USD')],
                        'price_column_eur' => [],
                        'price_column_eur_unit1' => [],
                        'showTierPrices' => true
                    ],
                    [
                        'id' => 3,
                        'name' => 'third',
                        'price_column_usd' => [],
                        'price_column_eur' => [],
                        'showTierPrices' => true
                    ],
                ],
            ],
        ];
    }
}
