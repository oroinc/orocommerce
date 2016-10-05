<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\PricingBundle\Datagrid\Provider\CombinedProductPriceProviderInterface;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\PricingBundle\EventListener\FrontendProductPriceDatagridListener;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;

class FrontendProductPriceDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var FrontendProductPriceDatagridListener
     */
    private $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PriceListRequestHandler
     */
    private $priceListRequestHandler;

    /**
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $currencyManager;

    /**
     * @var CombinedProductPriceProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $combinedProductPriceProvider;

    public function setUp()
    {
        $this->priceListRequestHandler = $this
            ->getMockBuilder(PriceListRequestHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->currencyManager = $this->getMockBuilder(UserCurrencyManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->combinedProductPriceProvider = $this->getMockBuilder(CombinedProductPriceProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new FrontendProductPriceDatagridListener(
            $this->priceListRequestHandler,
            $this->currencyManager,
            $this->combinedProductPriceProvider
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
            ->willReturn(
                $this->getEntity(CombinedPriceList::class, ['id' => $priceListId])
            );

        $this->currencyManager
            ->expects($this->any())
            ->method('getUserCurrency')
            ->willReturn(reset($priceCurrencies));
    }

    /**
     * @param int|null $priceListId
     * @param array    $priceCurrencies
     * @param array    $expectedConfig
     * @dataProvider onBuildBeforeDataProvider
     */
    public function testOnBuildBefore($priceListId = null, array $priceCurrencies = [], array $expectedConfig = [])
    {
        $this->setUpPriceListRequestHandler($priceListId, $priceCurrencies);

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock(DatagridInterface::class);
        $config   = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        $this->assertEquals($expectedConfig, $config->toArray());
    }

    /**
     * @return array
     */
    public function onBuildBeforeDataProvider()
    {
        return [
            'no currencies'    => [
                'priceListId'     => 1,
                'priceCurrencies' => [],
            ],
            'valid currencies' => [
                'priceListId'     => 1,
                'priceCurrencies' => ['EUR'],
                'expectedConfig'  => [
                    'properties' => [
                        'prices' => ['type' => 'field', 'frontend_type' => 'row_array'],
                    ],
                ],
            ],
        ];
    }

    public function testOnResultAfterNoRecords()
    {
        $this->currencyManager->expects($this->never())
            ->method($this->anything());

        /** @var SearchQueryInterface $query */
        $query = $this->getMockBuilder(SearchQueryInterface::class)->getMock();
        /** @var DatagridInterface $datagrid */
        $datagrid = $this->getMock(DatagridInterface::class);
        $event    = new SearchResultAfter($datagrid, $query, []);
        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfterNoPriceList()
    {
        $this->currencyManager->expects($this->never())
            ->method($this->anything());
        $this->priceListRequestHandler->expects($this->once())
            ->method('getPriceListByAccount');

        /** @var SearchQueryInterface $query */
        $query = $this->getMockBuilder(SearchQueryInterface::class)->getMock();
        /** @var DatagridInterface $datagrid */
        $datagrid = $this->getMock(DatagridInterface::class);
        $event    = new SearchResultAfter($datagrid, $query, [new ResultRecord([])]);
        $this->listener->onResultAfter($event);
    }

    /**
     * @dataProvider onResultWithCombinedPricesProvider
     * @param array $products
     * @param array $combinedProductPrices
     * @param array $expected
     */
    public function testOnResultWithCombinedPrices($products, $combinedProductPrices, $expected)
    {
        $this->combinedProductPriceProvider->expects($this->once())
            ->method('getCombinedPricesForProductsByPriceList')
            ->will($this->returnValue($combinedProductPrices));

        $this->priceListRequestHandler->expects($this->once())
            ->method('getPriceListByAccount')
            ->will($this->returnValue(new CombinedPriceList()));

        /** @var SearchQueryInterface $query */
        $query = $this->getMockBuilder(SearchQueryInterface::class)->getMock();
        /** @var DatagridInterface $datagrid */
        $datagrid = $this->getMock(DatagridInterface::class);
        $event    = new SearchResultAfter($datagrid, $query, [new ResultRecord($products)]);
        $this->listener->onResultAfter($event);

        $actualResults = $event->getRecords();

        $this->assertSameSize($expected, $actualResults);
        foreach ($expected as $key => $expectedResult) {
            $actualResult = $actualResults[$key];
            foreach ($expectedResult as $name => $value) {
                $this->assertEquals($value, $actualResult->getValue($name));
            }
        }
    }


    /**
     * @return array
     */
    public function onResultWithCombinedPricesProvider()
    {
        return [
            'valid data' => [
                'sourceResults'   => [
                    'id' => 2
                ],
                [
                    2 => [
                        'item_1' => [
                            'price'              => 20,
                            'currency'           => 'EUR',
                            'formatted_price'    => 'EUR20',
                            'unit'               => 'item',
                            'formatted_unit'     => 'item-formatted',
                            'quantity'           => 1,
                            'quantity_with_unit' => '1-item-formatted',
                        ],
                        'item_2' => [
                            'price'              => 21,
                            'currency'           => 'EUR',
                            'formatted_price'    => 'EUR21',
                            'unit'               => 'item',
                            'formatted_unit'     => 'item-formatted',
                            'quantity'           => 2,
                            'quantity_with_unit' => '2-item-formatted',
                        ],
                    ],
                ],
                'expectedResults' => [
                    [
                        'id'               => 2,
                        'prices'           => [
                            'item_1' => [
                                'price'              => 20,
                                'currency'           => 'EUR',
                                'formatted_price'    => 'EUR20',
                                'unit'               => 'item',
                                'formatted_unit'     => 'item-formatted',
                                'quantity'           => 1,
                                'quantity_with_unit' => '1-item-formatted',
                            ],
                            'item_2' => [
                                'price'              => 21,
                                'currency'           => 'EUR',
                                'formatted_price'    => 'EUR21',
                                'unit'               => 'item',
                                'formatted_unit'     => 'item-formatted',
                                'quantity'           => 2,
                                'quantity_with_unit' => '2-item-formatted',
                            ],
                        ],
                        'price_units'      => null,
                        'price_quantities' => null,
                    ]
                ],
            ],
        ];
    }
}
