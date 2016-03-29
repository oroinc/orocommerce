<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\PricingBundle\EventListener\FrontendProductPriceDatagridListener;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;

class FrontendProductPriceDatagridListenerTest extends AbstractProductPriceDatagridListenerTest
{
    /**
     * @var FrontendProductPriceDatagridListener
     */
    protected $listener;

    /**
     * @var NumberFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $numberFormatter;

    /**
     * @var ProductUnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $unitFormatter;

    /**
     * @var UserCurrencyProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $currencyProvider;

    public function setUp()
    {
        $this->numberFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NumberFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->unitFormatter = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->currencyProvider = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider')
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    /**
     * @return FrontendProductPriceDatagridListener
     */
    protected function createListener()
    {
        return new FrontendProductPriceDatagridListener(
            $this->translator,
            $this->priceListRequestHandler,
            $this->numberFormatter,
            $this->unitFormatter,
            $this->currencyProvider
        );
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

        $this->currencyProvider
            ->expects($this->any())
            ->method('getUserCurrency')
            ->willReturn(reset($priceCurrencies));
    }

    /**
     * @return array
     */
    public function onBuildBeforeDataProvider()
    {
        return [
            'no currencies' => [
                'priceListId' => 1,
                'priceCurrencies' => [],
            ],
            'valid currencies' => [
                'priceListId' => 1,
                'priceCurrencies' => ['EUR'],
                'expectedConfig' => [
                    'columns' => [
                        'minimum_price' => [
                            'label' => 'orob2b.pricing.productprice.price_in_EUR.trans',
                        ],
                    ],
                    'properties' => [
                        'prices' => ['type' => 'field', 'frontend_type' => 'row_array'],
                        'price_units' => null,
                    ],
                    'filters' => [
                        'columns' => [
                            'minimum_price' => [
                                'type' => 'frontend-product-price',
                                'data_name' => 'EUR'
                            ],
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            'minimum_price' => [
                                'data_name' => 'minimum_price',
                                'type' => PropertyInterface::TYPE_CURRENCY,
                            ],
                        ]
                    ],
                    'source' => [
                        'query' => [
                            'select' => [
                                'GROUP_CONCAT(minimum_price_table.value SEPARATOR \'{sep}\') as prices',
                                'GROUP_CONCAT(minimum_price_table.unit SEPARATOR \'{sep}\') as price_units',
                                'MIN(minimum_price_table.value) as minimum_price',
                            ],
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'OroB2BPricingBundle:ProductPrice',
                                        'alias' => 'minimum_price_table',
                                        'conditionType' => 'WITH',
                                        'condition' => 'minimum_price_table.product = product.id ' .
                                            'AND minimum_price_table.currency = \'EUR\' ' .
                                            'AND minimum_price_table.priceList = 1 ' .
                                            'AND minimum_price_table.quantity = 1',
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
     * @param string $priceCurrency
     * @param int|null $priceListId
     * @param array $sourceResults
     * @param array $expectedResults
     * @dataProvider onResultAfterDataProvider
     */
    public function testOnResultAfter(
        $priceCurrency,
        $priceListId = null,
        array $sourceResults = [],
        array $expectedResults = []
    ) {
        $sourceResultRecords = [];
        foreach ($sourceResults as $sourceResult) {
            $sourceResultRecords[] = new ResultRecord($sourceResult);
        }

        $this->setUpPriceListRequestHandler($priceListId, [$priceCurrency]);

        $this->numberFormatter->expects($this->any())
            ->method('formatCurrency')
            ->willReturnCallback(
                function ($price, $currency) {
                    return $currency . $price;
                }
            );

        $this->unitFormatter->expects($this->any())
            ->method('format')
            ->willReturnCallback(
                function ($unit) {
                    return $unit . '-formatted';
                }
            );


        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new OrmResultAfter($datagrid, $sourceResultRecords);
        $this->listener->onResultAfter($event);
        $actualResults = $event->getRecords();

        $this->assertSameSize($expectedResults, $actualResults);
        foreach ($expectedResults as $key => $expectedResult) {
            $actualResult = $actualResults[$key];
            foreach ($expectedResult as $name => $value) {
                $this->assertEquals($value, $actualResult->getValue($name));
            }
        }
    }

    /**
     * @return array
     */
    public function onResultAfterDataProvider()
    {
        return [
            'no price list id' => [
                'priceCurrency' => 'USD',
            ],
            'with price list' => [
                'priceCurrency' => 'USD',
                'priceListId' => 1,
            ],
            'valid data' => [
                'priceCurrency' => 'EUR',
                'priceListId' => 1,
                'sourceResults' => [
                    [
                        'id' => 2,
                        'prices' => '20.000{sep}21.000',
                        'price_units' => 'item{sep}piece',
                    ],
                    [
                        'id' => 3,
                        'prices' => '1.000{sep}2.000',
                        'price_units' => 'box{sep}liter',
                    ],
                ],
                'expectedResults' => [
                    [
                        'id' => 2,
                        'prices' => [
                            'item' => [
                                'price' => 20,
                                'currency' => 'EUR',
                                'formatted_price' => 'EUR20',
                                'unit' => 'item',
                                'formatted_unit' => 'item-formatted',

                            ],
                            'piece' => [
                                'price' => 21,
                                'currency' => 'EUR',
                                'formatted_price' => 'EUR21',
                                'unit' => 'piece',
                                'formatted_unit' => 'piece-formatted',

                            ],
                        ],
                        'price_units' => null,
                    ],
                    [
                        'id' => 3,
                        'prices' => [
                            'box' => [
                                'price' => 1,
                                'currency' => 'EUR',
                                'formatted_price' => 'EUR1',
                                'unit' => 'box',
                                'formatted_unit' => 'box-formatted',

                            ],
                            'liter' => [
                                'price' => 2,
                                'currency' => 'EUR',
                                'formatted_price' => 'EUR2',
                                'unit' => 'liter',
                                'formatted_unit' => 'liter-formatted',

                            ],
                        ],
                        'price_units' => null,
                    ],
                ],
            ],
        ];
    }
}
