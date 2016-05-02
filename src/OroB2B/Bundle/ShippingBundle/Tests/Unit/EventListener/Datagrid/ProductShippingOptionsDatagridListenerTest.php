<?php

namespace OroB2B\Bundle\ShippingBundle\Bundle\Tests\Unit\EventListener\Datagrid;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\EventListener\Datagrid\ProductShippingOptionsDatagridListener;

class ProductShippingOptionsDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_SHIPPING_OPTIONS_CLASS = 'OroB2B\\Bundle\\ShippingBundle\\Entity\\ProductShippingOptions';

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var DatagridConfiguration
     */
    protected $config;

    /**
     * @var ProductShippingOptionsDatagridListener
     */
    protected $listener;

    public function setUp()
    {
        $this->config = DatagridConfiguration::create([]);

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($id) {
                    return $id . '.trans';
                }
            );

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = $this->createListener();
        $this->listener->setProductShippingOptionsClass(static::PRODUCT_SHIPPING_OPTIONS_CLASS);
    }

    protected function tearDown()
    {
        unset($this->doctrineHelper, $this->translator, $this->listener, $this->config, $this->productShippingOptions);
    }

    public function testSetProductShippingOptionsClass()
    {
        $listener = $this->createListener();
        $this->assertNull($this->getProperty($listener, 'productShippingOptionsClass'));
        $listener->setProductShippingOptionsClass(static::PRODUCT_SHIPPING_OPTIONS_CLASS);
        $this->assertEquals(
            static::PRODUCT_SHIPPING_OPTIONS_CLASS,
            $this->getProperty($listener, 'productShippingOptionsClass')
        );
    }

    /**
     * @param array $expectedConfig
     *
     * @dataProvider onBuildBeforeDataProvider
     */
    public function testOnBuildBefore(array $expectedConfig = [])
    {

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        $this->assertEquals($expectedConfig, $config->toArray());
    }

    /**
     * @param array $sourceResults
     * @param array $expectedResults
     *
     * @dataProvider onResultAfterDataProvider
     */
    public function testOnResultAfter(array $sourceResults = [], array $expectedResults = [])
    {
        $sourceResultRecords = [];
        foreach ($sourceResults as $sourceResult) {
            $sourceResultRecords[] = new ResultRecord($sourceResult);
        }

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->willReturn(new ProductShippingOptions());

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
            'valid data' => [
                'sourceResults' => [
                    [
                        'id' => 2,
                        'product_shipping_options' => '1{sep}2'
                    ],
                ],
                'expectedResults' => [
                    [
                        'id' => 2,
                        'product_shipping_options' => [
                            '1' => new ProductShippingOptions(),
                            '2' => new ProductShippingOptions()
                        ]
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function onBuildBeforeDataProvider()
    {
        return [
            'valid' => [
                'expectedConfig' => [
                    'source' => [
                        'query' => [
                            'select' => [
                                0 => 'GROUP_CONCAT(product_shipping_options_table.id SEPARATOR \'{sep}\') as product_shipping_options',
                            ],
                            'join' => [
                                'left' => [
                                    0 => [
                                        'join' => 'OroB2B\\Bundle\\ShippingBundle\\Entity\\ProductShippingOptions',
                                        'alias' => 'product_shipping_options_table',
                                        'conditionType' => 'WITH',
                                        'condition' => 'product_shipping_options_table.product = product.id',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'columns' => [
                        'product_shipping_options' => [
                            'label' => 'orob2b.shipping.datagrid.shipping_options.column.label.trans',
                            'type' => 'twig',
                            'template' => 'OroB2BShippingBundle:Datagrid:Column/productShippingOptions.html.twig',
                            'frontend_type' => 'html',
                            'renderable' => false,
                        ],
                    ],
                ]
            ]
        ];
    }

    /**
     * @return ProductShippingOptionsDatagridListener
     */
    protected function createListener()
    {
        return new ProductShippingOptionsDatagridListener($this->translator, $this->doctrineHelper);
    }

    /**
     * @param object $object
     * @param string $property
     *
     * @return mixed $value
     */
    protected function getProperty($object, $property)
    {
        $reflection = new \ReflectionProperty(get_class($object), $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
