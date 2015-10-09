<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;

use OroB2B\Bundle\PricingBundle\Model\AbstractPriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\PricingBundle\EventListener\ProductPriceDatagridListener;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductPriceDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductPriceDatagridListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AbstractPriceListRequestHandler
     */
    protected $priceListRequestHandler;

    protected function setUp()
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

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceListRequestHandler = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\AbstractPriceListRequestHandler')
            ->setMethods(['getShowTierPrices'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->listener = new ProductPriceDatagridListener(
            $this->translator,
            $this->doctrineHelper,
            $this->priceListRequestHandler
        );

        $this->listener->setProductPriceClass('OroB2BPricingBundle:ProductPrice');
        $this->listener->setProductUnitClass('OroB2BProductBundle:ProductUnit');
    }

    protected function tearDown()
    {
        unset($this->doctrineHelper, $this->translator, $this->priceListRequestHandler, $this->listener);
    }

    public function testSetProductPriceClass()
    {
        $listener = new ProductPriceDatagridListener(
            $this->translator,
            $this->doctrineHelper,
            $this->priceListRequestHandler
        );
        $this->assertNull($this->getProperty($listener, 'productPriceClass'));
        $listener->setProductPriceClass('OroB2BPricingBundle:ProductPrice');
        $this->assertEquals(
            'OroB2BPricingBundle:ProductPrice',
            $this->getProperty($listener, 'productPriceClass')
        );
    }

    public function testSetProductUnitClass()
    {
        $listener = new ProductPriceDatagridListener(
            $this->translator,
            $this->doctrineHelper,
            $this->priceListRequestHandler
        );
        $this->assertNull($this->getProperty($listener, 'productUnitClass'));
        $listener->setProductUnitClass('OroB2BProductBundle:ProductUnit');
        $this->assertEquals(
            'OroB2BProductBundle:ProductUnit',
            $this->getProperty($listener, 'productUnitClass')
        );
    }

    /**
     * @param int|null $priceListId
     * @param array $priceCurrencies
     * @param array $expectedConfig
     * @dataProvider onBuildBeforeDataProvider
     */
    public function testOnBuildBefore($priceListId = null, array $priceCurrencies = [], array $expectedConfig = [])
    {
        $this->getRepository();

        if ($priceListId && $priceCurrencies) {
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

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

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
                        ],
                        'price_column_eur' => [
                            'label' => 'orob2b.pricing.productprice.price_in_EUR.trans',
                            'type' => 'twig',
                            'template' => 'OroB2BPricingBundle:Datagrid:Column/productPrice.html.twig',
                            'frontend_type' => 'html',
                        ],
                        'price_column_usd_unit1_value' => [
                            'label' => 'orob2b.pricing.productprice.price_unit1_in_USD.trans',
                            'type' => 'twig',
                            'template' => 'OroB2BPricingBundle:Datagrid:Column/productUnitPrice.html.twig',
                            'frontend_type' => 'html',
                        ],
                        'price_column_eur_unit1_value' => [
                            'label' => 'orob2b.pricing.productprice.price_unit1_in_EUR.trans',
                            'type' => 'twig',
                            'template' => 'OroB2BPricingBundle:Datagrid:Column/productUnitPrice.html.twig',
                            'frontend_type' => 'html',
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
                            'price_column_usd_unit1_value' => [
                                'type' => 'product-price',
                                'data_name' => 'price_column_usd_unit1_value'
                            ],
                            'price_column_eur_unit1_value' => [
                                'type' => 'product-price',
                                'data_name' => 'price_column_eur_unit1_value'
                            ],
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            'price_column_usd_unit1_value' => [
                                'data_name' => 'price_column_usd_unit1_value'
                            ],
                            'price_column_eur_unit1_value' => [
                                'data_name' => 'price_column_eur_unit1_value'
                            ],
                        ]
                    ],
                    'source' => [
                        'query' => [
                            'select' => [
                                0 => 'price_column_usd_unit1.value as price_column_usd_unit1_value',
                                1 => 'price_column_eur_unit1.value as price_column_eur_unit1_value',
                            ],
                            'groupBy' => 'product.id',
                            'join' => [
                                'left' => [
                                    0 => [
                                        'join' => 'OroB2BPricingBundle:ProductPrice',
                                        'alias' => 'price_column_usd_unit1',
                                        'conditionType' => 'WITH',
                                        'condition' => 'price_column_usd_unit1.product = product.id ' .
                                            'AND price_column_usd_unit1.currency = \'USD\' ' .
                                            'AND price_column_usd_unit1.unit = \'unit1\' ' .
                                            'AND price_column_usd_unit1.priceList = 1',
                                    ],
                                    1 => [
                                        'join' => 'OroB2BPricingBundle:ProductPrice',
                                        'alias' => 'price_column_eur_unit1',
                                        'conditionType' => 'WITH',
                                        'condition' => 'price_column_eur_unit1.product = product.id ' .
                                            'AND price_column_eur_unit1.currency = \'EUR\' ' .
                                            'AND price_column_eur_unit1.unit = \'unit1\' ' .
                                            'AND price_column_eur_unit1.priceList = 1',
                                    ]
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

        if ($priceListId && $priceCurrencies) {
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

            $this->priceListRequestHandler->expects($this->any())->method('getShowTierPrices')->willReturn(true);

            $this->getRepository()
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
                        'price_column_usd_unit1_value' => 15,
                    ],
                    [
                        'id' => 2,
                        'name' => 'second',
                        'price_column_eur_unit1_value' => 22,
                    ],
                    [
                        'id' => 3,
                        'name' => 'third',
                    ],
                ],
                'prices' => [
                    $this->createPrice(1, 10, 'USD'),
                    $this->createPrice(1, 11, 'EUR'),
                    $this->createPrice(1, 12, 'EUR'),
                    $this->createPrice(2, 20, 'USD'),
                ],
                'expectedResults' => [
                    [
                        'id' => 1,
                        'name' => 'first',
                        'price_column_usd' => [$this->createPrice(1, 10, 'USD')],
                        'price_column_eur' => [$this->createPrice(1, 11, 'EUR'), $this->createPrice(1, 12, 'EUR')],
                        'price_column_usd_unit1_value' => Price::create(15, 'USD'),
                        'showTierPrices' => true
                    ],
                    [
                        'id' => 2,
                        'name' => 'second',
                        'price_column_usd' => [$this->createPrice(2, 20, 'USD')],
                        'price_column_eur' => [],
                        'price_column_eur_unit1_value' => Price::create(22, 'EUR'),
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
     * @param int $productId
     * @param float $value
     * @param string $currency
     * @return ProductPrice
     */
    protected function createPrice($productId, $value, $currency)
    {
        $product = new Product();

        $reflection = new \ReflectionProperty(get_class($product), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($product, $productId);

        $price = new ProductPrice();
        $price->setProduct($product)
            ->setPrice(Price::create($value, $currency));

        return $price;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ProductPriceRepository
     */
    protected function getRepository()
    {
        $unit1 = (new ProductUnit())->setCode('unit1');
        $repository = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())
            ->method('findBy')
            ->willReturn([$unit1]);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->withConsecutive(['OroB2BProductBundle:ProductUnit'], ['OroB2BPricingBundle:ProductPrice'])
            ->willReturn($repository);

        return $repository;
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
