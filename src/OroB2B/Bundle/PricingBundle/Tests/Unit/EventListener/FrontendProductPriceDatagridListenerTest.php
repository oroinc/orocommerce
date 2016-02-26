<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;

class FrontendProductPriceDatagridListenerTest extends AbstractProductPriceDatagridListenerTest
{
    /**
     * {@inheritDoc}
     */
    protected function getListenerClassName()
    {
        return 'OroB2B\Bundle\PricingBundle\EventListener\FrontendProductPriceDatagridListener';
    }

    /**
     * {@inheritDoc}
     */
    protected function setUpPriceListRequestHandler($priceListId = null, array $priceCurrencies = [])
    {
        $this->priceListRequestHandler
            ->expects($this->any())
            ->method('getPriceListByAccount')
            ->willReturn($this->getPriceList($priceListId));

        $this->priceListRequestHandler
            ->expects($this->any())
            ->method('getPriceListSelectedCurrencies')
            ->will(
                $this->returnCallback(
                    function () use ($priceCurrencies) {
                        return array_intersect(['EUR'], $priceCurrencies);
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
                'priceCurrencies' => ['EUR'],
                'expectedConfig' => [
                    'columns' => [
                        'price_column' => [
                            'label' => 'orob2b.pricing.productprice.price_in_EUR.trans',
                            'type' => 'twig',
                            'template' => 'OroB2BPricingBundle:Datagrid:Column/Frontend/productPrice.html.twig',
                            'frontend_type' => 'html',
                            'renderable' => true,
                        ],
                        'price_column_unit1' => [
                            'label' => 'orob2b.pricing.productprice.price_unit1_in_EUR.trans',
                            'type' => 'twig',
                            'template' => 'OroB2BPricingBundle:Datagrid:Column/Frontend/productPrice.html.twig',
                            'frontend_type' => 'html',
                            'renderable' => false,
                        ],
                    ],
                    'filters' => [
                        'columns' => [
                            'price_column' => [
                                'type' => 'product-price',
                                'data_name' => 'EUR'
                            ],
                            'price_column_unit1' => [
                                'type' => 'number-range',
                                'data_name' => 'price_column_unit1',
                                'enabled' => false
                            ],
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            'price_column' => [
                                'data_name' => 'price_column',
                                'type' => PropertyInterface::TYPE_CURRENCY,
                            ],
                            'price_column_unit1' => [
                                'data_name' => 'price_column_unit1',
                                'type' => PropertyInterface::TYPE_CURRENCY,
                            ],
                        ]
                    ],
                    'source' => [
                        'query' => [
                            'select' => [
                                'min(price_column_table.value) as price_column',
                                'price_column_unit1_table.value as price_column_unit1',
                            ],
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'OroB2BPricingBundle:ProductPrice',
                                        'alias' => 'price_column_table',
                                        'conditionType' => 'WITH',
                                        'condition' => 'price_column_table.product = product.id ' .
                                            'AND price_column_table.currency = \'EUR\' ' .
                                            'AND price_column_table.priceList = 1 ' .
                                            'AND price_column_table.quantity = 1',
                                    ],
                                    [
                                        'join' => 'OroB2BPricingBundle:ProductPrice',
                                        'alias' => 'price_column_unit1_table',
                                        'conditionType' => 'WITH',
                                        'condition' => 'price_column_unit1_table.product = product.id ' .
                                            'AND price_column_unit1_table.currency = \'EUR\' ' .
                                            'AND price_column_unit1_table.priceList = 1 ' .
                                            'AND price_column_unit1_table.quantity = 1 ' .
                                            'AND price_column_unit1_table.unit = \'unit1\'',
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
                'priceCurrencies' => ['EUR'],
                'sourceResults' => [
                    [
                        'id' => 2,
                        'name' => 'second',
                        'price_column_unit1' => 22,
                    ],
                    [
                        'id' => 3,
                        'name' => 'third',
                    ],
                ],
                'prices' => [
                    $this->createPrice(1, 11, 'EUR'),
                    $this->createPrice(1, 12, 'EUR', $unit),
                ],
                'expectedResults' => [
                    [
                        'id' => 2,
                        'name' => 'second',
                        'price_column' => [],
                        'price_column_unit1' => [],
                        'showTierPrices' => true
                    ],
                    [
                        'id' => 3,
                        'name' => 'third',
                        'price_column' => [],
                        'showTierPrices' => true
                    ],
                ],
            ],
        ];
    }
}
