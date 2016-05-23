<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\PricingBundle\EventListener\ProductPriceDatagridListener;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductPriceDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PriceListRequestHandler
     */
    protected $priceListRequestHandler;
    
    /**
     * @var ProductPriceDatagridListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    public function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->with($this->isType('string'))
            ->willReturnCallback(
                function ($id, array $params = []) {
                    $id = str_replace(array_keys($params), array_values($params), $id);

                    return $id . '.trans';
                }
            );

        $this->priceListRequestHandler = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = $this->createListener();
    }

    /**
     * @return ProductPriceDatagridListener
     */
    protected function createListener()
    {
        return new ProductPriceDatagridListener(
            $this->translator,
            $this->priceListRequestHandler,
            $this->doctrineHelper
        );
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
            ->willReturn($priceCurrencies);
    }

    /**
     * @param int|null $priceListId
     * @param array $priceCurrencies
     * @param array $expectedConfig
     * @dataProvider onBuildBeforeDataProvider
     */
    public function testOnBuildBefore($priceListId = null, array $priceCurrencies = [], array $expectedConfig = [])
    {
        $this->setUpRepository();
        $this->setUpPriceListRequestHandler($priceListId, $priceCurrencies);

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        $this->assertEquals($expectedConfig, $config->toArray());
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
                            ],
                            'price_column_eur' => [
                                'data_name' => 'price_column_eur',
                            ],
                            'price_column_usd_unit1' => [
                                'data_name' => 'price_column_usd_unit1',
                            ],
                            'price_column_eur_unit1' => [
                                'data_name' => 'price_column_eur_unit1',
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
     * @param int|null $priceListId
     * @param array $priceCurrencies
     * @param array $sourceResults
     * @param ProductPrice[] $prices
     * @param array $expectedResults
     * @dataProvider onResultAfterDataProvider
     */
    public function testOnResultAfter(
        $priceListId = null,
        array $priceCurrencies = [],
        array $sourceResults = [],
        array $prices = [],
        array $expectedResults = []
    ) {
        $sourceResultRecords = [];
        $productIds = [];
        foreach ($sourceResults as $sourceResult) {
            $sourceResultRecords[] = new ResultRecord($sourceResult);
            $productIds[] = $sourceResult['id'];
        }

        $this->setUpPriceListRequestHandler($priceListId, $priceCurrencies);

        if ($priceListId && $priceCurrencies) {
            $this->priceListRequestHandler->expects($this->any())->method('getShowTierPrices')->willReturn(true);

            $this->setUpRepository()
                ->expects($this->any())
                ->method('findByPriceListIdAndProductIds')
                ->with($priceListId, $productIds)
                ->willReturn($prices);
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
        $unit = $this->getUnit('unit1');

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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ProductPriceRepository
     */
    protected function setUpRepository()
    {
        $repository = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())
            ->method('findBy')
            ->willReturn([$this->getUnit('unit1')]);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->withConsecutive(['OroB2BProductBundle:ProductUnit'], ['OroB2BPricingBundle:ProductPrice'])
            ->willReturn($repository);

        return $repository;
    }

    /**
     * @param int $productId
     * @param float $value
     * @param string $currency
     * @param ProductUnit|null $unit
     * @return ProductPrice
     */
    protected function createPrice($productId, $value, $currency, $unit = null)
    {
        $product = new Product();

        $reflection = new \ReflectionProperty(get_class($product), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($product, $productId);

        $price = new ProductPrice();
        $price->setProduct($product)
            ->setPrice(Price::create($value, $currency));
        if ($unit) {
            $price->setUnit($unit);
        }

        return $price;
    }

    /**
     * @param string $unitCode
     * @return ProductUnit
     */
    protected function getUnit($unitCode)
    {
        return (new ProductUnit())->setCode($unitCode);
    }

    /**
     * @param int $id
     * @return PriceList
     */
    protected function getPriceList($id)
    {
        $priceList = new PriceList();
        $reflection = new \ReflectionProperty(get_class($priceList), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($priceList, $id);

        return $priceList;
    }

    /**
     * @param object $object
     * @param string $property
     * @return mixed $value
     */
    protected function getProperty($object, $property)
    {
        $reflection = new \ReflectionProperty(get_class($object), $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
