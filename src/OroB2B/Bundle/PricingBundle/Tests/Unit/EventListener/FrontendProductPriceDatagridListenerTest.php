<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\PricingBundle\EventListener\FrontendProductPriceDatagridListener;

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

    public function setUp()
    {
        $this->numberFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NumberFormatter')
            ->disableOriginalConstructor()->getMock();
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
            $this->numberFormatter
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

        $this->priceListRequestHandler
            ->expects($this->any())
            ->method('getPriceListSelectedCurrencies')
            ->willReturn($priceCurrencies);
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
            'valid currencies' => [
                'priceListId' => 1,
                'priceCurrencies' => ['EUR', 'USD'],
                'expectedConfig' => [
                    'columns' => [
                        'price_column' => [
                            'label' => 'orob2b.pricing.productprice.price_in_EUR.trans',
                        ],
                        'price_unit_column' => [
                            'label' => 'orob2b.product.productunit.entity_label.trans',
                        ],
                    ],
                    'filters' => [
                        'columns' => [
                            'price_column' => [
                                'type' => 'product-price',
                                'data_name' => 'EUR'
                            ],
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            'price_column' => [
                                'data_name' => 'price_column',
                                'type' => PropertyInterface::TYPE_CURRENCY,
                            ],
                        ]
                    ],
                    'source' => [
                        'query' => [
                            'select' => [
                                'min(price_column_table.value) as price_column,'
                                 . ' price_column_table.unit as price_unit_column',
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
                                ],
                            ],
                        ]
                    ]
                ],
            ],
        ];
    }

    /**
     * @param int|null $priceListId
     * @param array $priceCurrencies
     * @param array $sourceResults
     * @param array $expectedResults
     * @dataProvider onResultAfterDataProvider
     */
    public function testOnResultAfter(
        $priceListId = null,
        array $priceCurrencies = [],
        array $sourceResults = [],
        array $expectedResults = []
    ) {
        $currency = reset($priceCurrencies);
        $sourceResultRecords = [];
        foreach ($sourceResults as $sourceResult) {
            $sourceResultRecords[] = new ResultRecord($sourceResult);
        }

        $this->setUpPriceListRequestHandler($priceListId, $priceCurrencies);

        foreach ($sourceResults as $key => $sourceResult) {
            $this->numberFormatter->expects($this->at($key))
                ->method('formatCurrency')
                ->with($sourceResult['price_column'], $currency)
                ->willReturn($currency . $sourceResult['price_column']);
        }

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
            'no request' => [],
            'no price list id' => [
                'priceCurrencies' => ['USD'],
            ],
            'no currencies' => [
                'priceListId' => 1,
            ],
            'valid data' => [
                'priceListId' => 1,
                'priceCurrencies' => ['EUR', 'USD'],
                'sourceResults' => [
                    [
                        'id' => 2,
                        'name' => 'second',
                        'price_column' => 20.000,
                        'price_unit_column' => 'box',
                    ],
                    [
                        'id' => 3,
                        'name' => 'third',
                        'price_column' => 1.000,
                        'price_unit_column' => 'litre',
                    ],
                ],
                'expectedResults' => [
                    [
                        'id' => 2,
                        'name' => 'second',
                        'price_column' => 'EUR20',
                        'price_unit_column' => 'box',
                    ],
                    [
                        'id' => 3,
                        'name' => 'third',
                        'price_column' => 'EUR1',
                        'price_unit_column' => 'litre',
                    ],
                ],
            ],
        ];
    }
}
