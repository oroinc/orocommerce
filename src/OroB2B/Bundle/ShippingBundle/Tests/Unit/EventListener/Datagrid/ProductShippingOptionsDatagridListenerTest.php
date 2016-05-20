<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\EventListener\Datagrid;

use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\EventListener\Datagrid\ProductShippingOptionsDatagridListener;

class ProductShippingOptionsDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const PRODUCT_SHIPPING_OPTIONS_CLASS = 'OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions';

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var DatagridConfiguration */
    protected $config;

    /** @var ProductShippingOptionsDatagridListener */
    protected $listener;

    public function setUp()
    {
        $this->config = DatagridConfiguration::create([]);

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ProductShippingOptionsDatagridListener($this->doctrineHelper);
    }

    protected function tearDown()
    {
        unset($this->doctrineHelper, $this->listener, $this->config);
    }

    public function testSetProductShippingOptionsClass()
    {
        $this->assertNull($this->getProperty($this->listener, 'productShippingOptionsClass'));

        $this->listener->setProductShippingOptionsClass('TestClass');

        $this->assertEquals('TestClass', $this->getProperty($this->listener, 'productShippingOptionsClass'));
    }

    public function testOnBuildBefore()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');

        $this->listener->setProductShippingOptionsClass(static::PRODUCT_SHIPPING_OPTIONS_CLASS);

        $this->listener->onBuildBefore(new BuildBefore($datagrid, $this->config));

        $this->assertEquals(
            [
                'columns' => [
                    'product_shipping_options' => [
                        'label' => 'orob2b.shipping.datagrid.shipping_options.column.label',
                        'type' => 'twig',
                        'template' => 'OroB2BShippingBundle:Datagrid:Column/productShippingOptions.html.twig',
                        'frontend_type' => 'html',
                        'renderable' => false,
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    /**
     * @param array $sourceResults
     * @param array $expectedResults
     *
     * @dataProvider onResultAfterDataProvider
     */
    public function testOnResultAfter(array $sourceResults = [], array $expectedResults = [])
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectRepository $repository */
        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['product' => [42, 100]], ['productUnit' => 'ASC'])
            ->willReturn(
                [
                    $this->createShippingOptions(10, 42),
                    $this->createShippingOptions(11, 42),
                    $this->createShippingOptions(12, 42),
                    $this->createShippingOptions(13, 42),
                    $this->createShippingOptions(14, 42)
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(self::PRODUCT_SHIPPING_OPTIONS_CLASS)
            ->willReturn($repository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');

        $event = new OrmResultAfter($datagrid, $sourceResults);

        $this->listener->setProductShippingOptionsClass(self::PRODUCT_SHIPPING_OPTIONS_CLASS);
        $this->listener->onResultAfter($event);

        $this->assertEquals($expectedResults, $event->getRecords());
    }

    /**
     * @return array
     */
    public function onResultAfterDataProvider()
    {
        return [
            [
                'sourceResults' => [
                    $this->getResultRecord([['id' => 42]]),
                    $this->getResultRecord([['id' => 100]])
                ],
                'expectedResults' => [
                    $this->getResultRecord([
                        [
                            'id' => 42
                        ],
                        [
                            ProductShippingOptionsDatagridListener::SHIPPING_OPTIONS_COLUMN => [
                                $this->createShippingOptions(10, 42),
                                $this->createShippingOptions(11, 42),
                                $this->createShippingOptions(12, 42),
                                $this->createShippingOptions(13, 42),
                                $this->createShippingOptions(14, 42)
                            ]
                        ]
                    ]),
                    $this->getResultRecord([
                        [
                            'id' => 100
                        ],
                        [
                            ProductShippingOptionsDatagridListener::SHIPPING_OPTIONS_COLUMN => []
                        ]
                    ])
                ],
            ],
        ];
    }

    /**
     * @param int $id
     * @param int $productId
     * @return ProductShippingOptions
     */
    protected function createShippingOptions($id, $productId)
    {
        return $this->getEntity(
            'OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions',
            [
                'id' => $id,
                'product' => $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['id' => $productId])
            ]
        );
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

    /**
     * @param array $data
     * @return ResultRecord
     */
    protected function getResultRecord(array $data = [])
    {
        $resultRecord = new ResultRecord([]);

        foreach ($data as $value) {
            $resultRecord->addData($value);
        }

        return $resultRecord;
    }
}
